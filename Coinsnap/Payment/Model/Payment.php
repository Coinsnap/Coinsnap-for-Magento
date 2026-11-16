<?php
namespace Coinsnap\Payment\Model;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Registry;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Coinsnap\Payment\Model\Webhook\InvoiceDecision;
use Coinsnap\Payment\Model\Webhook\Signature;
include_once(__DIR__ . DIRECTORY_SEPARATOR .'/library/loader.php');

if (!defined('COINSNAP_SERVER_PATH')){ define( 'COINSNAP_SERVER_PATH', 'stores' ); }
if (!defined('COINSNAP_REFERRAL_CODE')){ define( 'COINSNAP_REFERRAL_CODE', 'D13456' ); }
if(!defined('COINSNAP_CURRENCIES')){define( 'COINSNAP_CURRENCIES', array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB") );}
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}

class Payment extends \Magento\Payment\Model\Method\AbstractMethod {

    const COINSNAP_PAYMENT_CODE = 'coinsnap_payment';
    public const COINSNAP_WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];
    public const BTCPAY_WEBHOOK_EVENTS = ['InvoiceCreated','InvoiceExpired','InvoiceSettled','InvoiceProcessing'];

    // Invoices this plugin created for an order: invoice id => [amount, currency] as sent to the API.
    private const INVOICES_INFO_KEY = 'coinsnap_invoices';
    private const SECURITY_LINES_PER_MINUTE = 10;
    private const OPEN_ORDER_STATES = [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT];

    protected $_code = self::COINSNAP_PAYMENT_CODE;

    protected $_urlBuilder;
    protected $_checkoutSession;
    protected $_order;
    protected $_orderFactory;
    protected $endpoint;

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $invoiceService;
    protected $transaction;
    protected $registry;
    protected $invoiceRepository;
    protected $invoiceSender;
    protected $_logger;
    protected $Psrlogger;
    protected $response;
    protected $messageManager;
    protected $_store;
    protected $resolver;
    protected $configWriter;
    protected $helper;
    protected $orderSender;
    private $reinitableConfig;
    private $cache;
    private $lockManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        Registry $registry,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Coinsnap\Payment\Helper\Payment $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Session $checkoutSession,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Framework\DB\Transaction $transaction,
        \Psr\Log\LoggerInterface $Psrlogger,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Framework\Locale\Resolver $resolver,
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig,
        CacheInterface $cache,
        LockManagerInterface $lockManager
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->registry = $registry;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceSender = $invoiceSender;
        $this->_logger = $Psrlogger;
        $this->response = $response;
        $this->_store = $store;
        $this->messageManager = $messageManager;
        $this->resolver = $resolver;
        $this->configWriter = $configWriter;
        $this->reinitableConfig = $reinitableConfig;
        $this->cache = $cache;
        $this->lockManager = $lockManager;
    }

    protected function _getOrder(){
        if (!$this->_order) {
            $incrementId =  $this->_checkoutSession->getLastRealOrderId();
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }
        return $this->_order;
    }

    public function getRedirectUrl() {
        return $this->helper->getUrl($this->getConfigData('redirect_url'));
    }

    public function getWebhookUrl() {
        return $this->helper->getUrl($this->getConfigData('webhook_url'));
    }

    private function getPaymentProvider() {
        return ($this->getConfigData( 'provider') === 'btcpay')? 'btcpay' : 'coinsnap';
    }

    private function isBtcpay(): bool {
        return $this->getPaymentProvider() === 'btcpay';
    }

    public function getStoreId() {
        return $this->isBtcpay() ? $this->getConfigData('btcpay_store_id') : $this->getConfigData('store_id');
    }

    public function getApiKey() {
        return $this->isBtcpay() ? $this->getConfigData('btcpay_api_key') : $this->getConfigData('api_key');
    }

    public function getApiUrl() {
        return $this->isBtcpay() ? rtrim((string) $this->getConfigData('btcpay_server_url'), '/') : COINSNAP_SERVER_URL;
    }

    public function getExpiredStatus() {
        return $this->getConfigData('expired_status');
    }

    public function getSettledStatus() {
        return $this->getConfigData('settled_status');
    }

    public function getProcessingStatus() {
        return $this->getConfigData('processing_status');
    }

    // Saves a setting and reloads the config, so the next read in this request sees it.
    public function setConfigData($field, $value){
        $path = 'payment/' . $this->_code . '/' . $field;
        $this->configWriter->save($path, $value);
        $this->reinitableConfig->reinit();
    }

    private function storedWebhook(): array {
        $stored = json_decode((string) $this->getConfigData('coinsnap_webhook'), true);
        return is_array($stored) ? $stored : [];
    }

    public function updateWebhookUrl(){
        try {
            if ($this->webhookExists()) {
                $this->setConfigData('webhook_status', 'Yes');
                return;
            }
        }
        catch (\Throwable $e) {
            // An API error is not "no webhook": registering now would replace a working secret.
            $this->_logger->warning('Coinsnap: webhook check failed, keeping the current webhook');
            return;
        }

        if ($this->registerWebhook()) {
            $this->setConfigData('webhook_status', 'Yes');
        }
    }

    public function checkAmount($amount, $currency)
    {
        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $store = new \Coinsnap\Client\Store($this->getApiUrl(), $this->getApiKey());
        $checkInvoice = [];

        try {
            if ($this->isBtcpay()) {
                try {
                    $storePaymentMethods = $store->getStorePaymentMethods($this->getStoreId());

                    if ($storePaymentMethods['code'] === 200) {
                        if (!$storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']) {
                            $errorMessage = 'No payment method is configured on BTCPay server';
                            $checkInvoice = array('result' => false,'error' => $errorMessage);
                        }
                    } else {
                        $errorMessage = 'Error store loading. Wrong or empty Store ID';
                        $checkInvoice = array('result' => false,'error' => $errorMessage);
                    }

                    if ($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']) {
                        $checkInvoice = $client->checkPaymentData((float)$amount, strtoupper($currency), 'bitcoin');
                    }
                    elseif ($storePaymentMethods['result']['lightning']) {
                        $checkInvoice = $client->checkPaymentData((float)$amount, strtoupper($currency), 'lightning');
                    }
                }
                catch (\Throwable $e) {
                    $errorMessage = 'API connection is not established';
                    $checkInvoice = array('result' => false,'error' => $errorMessage);
                }
            } else {
                $checkInvoice = $client->checkPaymentData((float)$amount, strtoupper($currency));
            }
        } catch (\Throwable $e) {
            $errorMessage = 'API connection is not established';
            $checkInvoice = array('result' => false,'error' => $errorMessage);
        }
        return $checkInvoice;
    }

    // Creates the invoice for the last order and points the response at its checkout page.
    public function getCoinsnapUrl() {
        $order = $this->_getOrder();
        $billing = $order->getBillingAddress();
        $order_id = (string) $order->getEntityId();

        $amount = number_format(($order->getGrandTotal()), 2,'.', '');
        $currency = $order->getBaseCurrencyCode();
        $this->updateWebhookUrl();

        $checkInvoice = $this->checkAmount($amount,strtoupper( $currency ));
        if (($checkInvoice['result'] ?? false) !== true) {
            $error = $checkInvoice['error'] ?? '';
            if ($error === 'currencyError') {
                $errorText = sprintf('Currency %1$s is not supported by Coinsnap', strtoupper( $currency ));
            }
            elseif ($error === 'amountError') {
                $errorText = sprintf( 'Invoice amount cannot be less than %1$s %2$s', $checkInvoice['min_value'], strtoupper( $currency ));
            }
            else {
                $errorText = 'The payment provider cannot be reached. Please try again later.';
            }
            $this->failCheckout($order, $errorText);
            return;
        }

        try {
            $redirectUrl = (!empty($this->getConfigData('returnurl')))? $this->getConfigData('returnurl') : $this->helper->getUrl("checkout/onepage/success");
            $buyerName = $billing->getFirstname().' '.$billing->getLastname();
            $buyerEmail = $order->getCustomerEmail();

            $metadata = ['orderNumber' => $order_id, 'orderId' => $order_id, 'customerName' => $buyerName];

            if ($this->isBtcpay() && $currency !== 'BTC') {
                $store = new \Coinsnap\Client\Store($this->getApiUrl(), $this->getApiKey());
                $btcpayCurrencies = $store->getStoreCurrenciesRates($this->getStoreId(), array($currency));
                $isCurrency = !isset($btcpayCurrencies['result']['error'])
                    && count($btcpayCurrencies['result']['currencies']) > 0
                    && isset($btcpayCurrencies['result']['currencies']['BTC_'.$currency]);

                // Currencies BTCPay Server does not support are charged in BTC at the current rate.
                if (!$isCurrency) {
                    $currency = 'BTC';
                    $rate = 1/$checkInvoice['rate'];
                    $amount = bcdiv(strval($amount), strval($rate), 8);
                }
            }

            $camount = ($currency === 'BTC')? \Coinsnap\Util\PreciseNumber::parseFloat((float) $amount,8) : \Coinsnap\Util\PreciseNumber::parseFloat((float) $amount,2);
            $redirectAutomatically = ($this->getConfigData( 'autoredirect') > 0);

            $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
            $csinvoice = $client->createInvoice(
                $this->getStoreId(),
                $currency,
                $camount,
                $order_id,
                $buyerEmail,
                $buyerName,
                $redirectUrl,
                COINSNAP_REFERRAL_CODE,
                $metadata,
                $redirectAutomatically,
                ''
            );
            $invoiceData = $csinvoice->getData();
        }
        catch (\Throwable $e) {
            $this->_logger->error('Coinsnap: invoice creation failed for order '.$order_id.': '.$e->getMessage());
            $this->failCheckout($order, 'The payment could not be started. Please try again later.');
            return;
        }

        if (empty($invoiceData['checkoutLink']) || empty($invoiceData['id'])) {
            $this->failCheckout($order, 'The payment could not be started. Please try again later.');
            return;
        }

        $this->rememberInvoice($order, (string) $invoiceData['id'], $camount->__toString(), $currency);
        $this->response->setRedirect($invoiceData['checkoutLink']);
    }

    private function failCheckout(Order $order, string $errorText): void {
        $order->cancel()->save();
        $this->_checkoutSession->restoreQuote();
        $this->messageManager->addErrorMessage($errorText);
        $this->response->setRedirect($this->helper->getUrl('checkout/cart'));
    }

    // Only invoices recorded here can pay this order, and only for the amount and currency sent.
    private function rememberInvoice(Order $order, string $invoiceId, string $amount, string $currency): void {
        $payment = $order->getPayment();
        $invoices = $payment->getAdditionalInformation(self::INVOICES_INFO_KEY);
        $invoices = is_array($invoices) ? $invoices : [];
        $invoices[$invoiceId] = ['amount' => $amount, 'currency' => $currency];
        $payment->setAdditionalInformation(self::INVOICES_INFO_KEY, $invoices);
        $payment->save();
    }

    // Handles a webhook; returns [HTTP status, body]. Only the invoice id is taken from the request.
    public function processWebhook(string $rawBody, string $coinsnapSignature, string $btcpaySignature): array {
        $secret = (string) ($this->storedWebhook()['secret'] ?? '');
        $problem = $this->isBtcpay()
            ? Signature::btcpayProblem($btcpaySignature, $rawBody, $secret)
            : Signature::coinsnapProblem($coinsnapSignature, $rawBody, $secret, time());
        if ($problem !== null) {
            $this->logSecurity($problem);
            return [401, 'Unauthorized'];
        }

        $body = json_decode($rawBody, true);
        $invoiceId = is_array($body) && is_string($body['invoiceId'] ?? null) ? $body['invoiceId'] : '';
        if ($invoiceId === '') {
            $this->logInfo('no invoice id');
            return [200, 'OK'];
        }
        $claimsPaid = in_array($body['type'] ?? '', ['Settled', 'InvoiceSettled'], true);

        try {
            $invoice = (new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey()))->getInvoice($this->getStoreId(), $invoiceId)->getData();
        }
        catch (\Coinsnap\Exception\RequestException $e) {
            if (in_array($e->getCode(), [400, 404], true)) {
                // The dashboard's "Send test webhook" button lands here.
                $this->logInfo('unknown invoice', $invoiceId);
                return [200, 'OK'];
            }
            $this->logInfo('retry: API unavailable', $invoiceId);
            return [503, 'Retry'];
        }
        catch (\Throwable $e) {
            $this->logInfo('retry: API unavailable', $invoiceId);
            return [503, 'Retry'];
        }

        $order = $this->findOrder($invoice);
        if ($order === null) {
            $this->logInfo('not ours', $invoiceId);
            return [200, 'OK'];
        }
        $expected = $this->expectedPayment($order, $invoiceId);
        if ($expected === null) {
            $this->logSecurity('invoice not created for this order', $invoiceId);
            return [200, 'OK'];
        }

        $decision = InvoiceDecision::decide($invoice, $this->isBtcpay(), $expected['amount'], $expected['currency']);
        if ($decision !== InvoiceDecision::PAID && ($claimsPaid || $decision === InvoiceDecision::MISMATCH)) {
            $this->logSecurity($claimsPaid ? 'forged status' : 'amount mismatch', $invoiceId);
            return [200, 'OK'];
        }

        $this->applyDecision($decision, (int) $order->getId(), $invoiceId);
        return [200, 'OK'];
    }

    // The order this plugin created the invoice for, or null when the invoice belongs to something else.
    private function findOrder(array $invoice): ?Order {
        $metadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $orderId = $this->isBtcpay() ? ($metadata['orderId'] ?? '') : ($invoice['orderId'] ?? $metadata['orderId'] ?? '');
        if (!is_scalar($orderId) || !ctype_digit((string) $orderId)) {
            return null;
        }

        $order = $this->_orderFactory->create()->load((int) $orderId);
        if (!$order->getId() || $order->getPayment() === null || $order->getPayment()->getMethod() !== self::COINSNAP_PAYMENT_CODE) {
            return null;
        }

        return $order;
    }

    // Amount and currency sent when the invoice was created; null when this order never had that invoice.
    private function expectedPayment(Order $order, string $invoiceId): ?array {
        $invoices = $order->getPayment()->getAdditionalInformation(self::INVOICES_INFO_KEY);
        if (is_array($invoices)) {
            return $invoices[$invoiceId] ?? null;
        }

        // Orders placed before 1.2.0 have no record; check against what was sent then.
        return ['amount' => number_format((float) $order->getGrandTotal(), 2, '.', ''), 'currency' => (string) $order->getBaseCurrencyCode()];
    }

    // Moves an order forward only: paid, cancelled or closed orders never change from a webhook.
    private function applyDecision(string $decision, int $orderId, string $invoiceId): void {
        if ($decision === InvoiceDecision::NONE) {
            $this->logInfo('no change', $invoiceId);
            return;
        }

        $lock = 'coinsnap_order_'.$orderId;
        if (!$this->lockManager->lock($lock, 10)) {
            $this->logInfo('busy', $invoiceId);
            return;
        }
        try {
            // Reload inside the lock, so a parallel webhook's changes are seen.
            $order = $this->_orderFactory->create()->load($orderId);
            $isOpen = in_array($order->getState(), self::OPEN_ORDER_STATES, true) && $order->canInvoice();

            if ($decision === InvoiceDecision::PAID) {
                $this->markPaid($order, $invoiceId);
            }
            elseif ($isOpen && $decision === InvoiceDecision::PROCESSING) {
                $order->addCommentToStatusHistory(__('Coinsnap: payment received, waiting for confirmation.'), $this->getProcessingStatus() ?: false);
                $order->save();
                $this->logInfo('status processing', $invoiceId);
            }
            elseif ($isOpen && $decision === InvoiceDecision::EXPIRED) {
                $order->addCommentToStatusHistory(__('Coinsnap: invoice expired.'), $this->getExpiredStatus() ?: false);
                $order->save();
                $this->logInfo('status expired', $invoiceId);
            }
            else {
                $this->logInfo('no change from '.$order->getState(), $invoiceId);
            }
        }
        finally {
            $this->lockManager->unlock($lock);
        }
    }

    private function markPaid(Order $order, string $invoiceId): void {
        if (!$order->canInvoice()) {
            // Already invoiced (this or a repeated webhook), or cancelled, so a late payment needs a manual check.
            if ($order->getState() === Order::STATE_CANCELED) {
                $order->addCommentToStatusHistory(__('Coinsnap: invoice %1 was paid after the order was cancelled. Please check it manually.', $invoiceId));
                $order->save();
            }
            $this->logInfo('already processed', $invoiceId);
            return;
        }

        $payment = $order->getPayment();
        $payment->setTransactionId($invoiceId);
        $payment->addTransaction(TransactionInterface::TYPE_CAPTURE);
        $payment->setIsTransactionClosed(1);

        $this->createInvoice($order, $invoiceId);
        $order->addCommentToStatusHistory(__('Coinsnap: invoice %1 settled.', $invoiceId), $this->getSettledStatus() ?: false);
        $order->save();
        $this->logInfo('paid', $invoiceId);
    }

    public function webhookExists(): bool {
        $stored = $this->storedWebhook();
        if (empty($stored['id'])) {
            return false;
        }

        try {
            $existing = (new \Coinsnap\Client\Webhook($this->getApiUrl(), $this->getApiKey()))->getWebhook($this->getStoreId(), (string) $stored['id']);
        }
        catch (\Coinsnap\Exception\RequestException $e) {
            if (in_array($e->getCode(), [400, 404], true)) {
                return false;
            }
            throw $e;
        }

        return ($existing->getData()['id'] ?? null) === $stored['id']
            && strpos((string) ($existing->getData()['url'] ?? ''), $this->getWebhookUrl()) !== false;
    }

    public function registerWebhook(): bool {
        $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $this->getApiKey());
        $events = $this->isBtcpay() ? self::BTCPAY_WEBHOOK_EVENTS : self::COINSNAP_WEBHOOK_EVENTS;

        try {
            $webhook = $whClient->createWebhook($this->getStoreId(), $this->getWebhookUrl(), $events, null)->getData();
        }
        catch (\Throwable $e) {
            $this->_logger->error('Coinsnap: webhook could not be registered (status '.$e->getCode().')');
            return false;
        }
        if (empty($webhook['id']) || empty($webhook['secret'])) {
            return false;
        }

        $previous = $this->storedWebhook();
        $this->setConfigData('coinsnap_webhook', json_encode([
            'id' => $webhook['id'],
            'secret' => $webhook['secret'],
            'url' => $webhook['url'] ?? $this->getWebhookUrl(),
        ]));

        // Remove the webhook this one replaces, so its old secret stops being used.
        if (!empty($previous['id']) && $previous['id'] !== $webhook['id']) {
            try {
                $whClient->deleteWebhook($this->getStoreId(), (string) $previous['id']);
            }
            catch (\Throwable $e) {
                $this->_logger->warning('Coinsnap: previous webhook could not be deleted');
            }
        }
        return true;
    }

    private function logInfo(string $reason, string $invoiceId = ''): void {
        $this->_logger->info($this->webhookLine('INFO', $reason, $invoiceId));
    }

    // At most 10 security lines a minute, so a flood of fake requests can't fill the log.
    private function logSecurity(string $reason, string $invoiceId = ''): void {
        $key = 'coinsnap_webhook_seclog_'.gmdate('YmdHi');
        $count = (int) $this->cache->load($key) + 1;
        $this->cache->save((string) $count, $key, [], 120);
        if ($count === self::SECURITY_LINES_PER_MINUTE + 1) {
            $this->_logger->warning('Coinsnap SECURITY webhook: more rejections this minute, not logged');
        }
        if ($count <= self::SECURITY_LINES_PER_MINUTE) {
            $this->_logger->warning($this->webhookLine('SECURITY', $reason, $invoiceId));
        }
    }

    private function webhookLine(string $level, string $reason, string $invoiceId): string {
        // The invoice id comes from the request.
        $id = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', $invoiceId), 0, 64);
        return sprintf('Coinsnap %s webhook reason=%s invoice=%s', $level, $reason, $id !== '' ? $id : '-');
    }

    protected function createInvoice($order, $trans_id){
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->setTransactionId($trans_id);
        $invoice = $this->invoiceRepository->save($invoice);
        $this->registry->register('current_invoice', $invoice, true);

        $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();
        $this->invoiceSender->send($invoice);
    }
}
