<?php
namespace Coinsnap\Payment\Model;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Registry;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use \Magento\Framework\App\Config\Storage\WriterInterface;
include_once(__DIR__ . DIRECTORY_SEPARATOR .'/library/loader.php');

if (!defined('COINSNAP_SERVER_PATH')){ define( 'COINSNAP_SERVER_PATH', 'stores' ); }
if (!defined('COINSNAP_REFERRAL_CODE')){ define( 'COINSNAP_REFERRAL_CODE', 'D13456' ); }
if(!defined('COINSNAP_CURRENCIES')){define( 'COINSNAP_CURRENCIES', array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB") );}
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}
if(!defined('COINSNAP_SERVER_PATH')){define( 'COINSNAP_SERVER_PATH', 'stores' );}

class Payment extends \Magento\Payment\Model\Method\AbstractMethod {

    const COINSNAP_PAYMENT_CODE = 'coinsnap_payment';
    public const COINSNAP_WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];
    public const BTCPAY_WEBHOOK_EVENTS = ['InvoiceCreated','InvoiceExpired','InvoiceSettled','InvoiceProcessing'];   

    protected $_code = self::COINSNAP_PAYMENT_CODE;

    /**
     *
     * @var \Magento\Framework\UrlInterface 
     */
    protected $_urlBuilder;
    
    protected $_checkoutSession;
	protected $_order;   
	protected $_orderFactory;	
	protected $endpoint  ;	
	
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
	
	

    /**
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
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
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession ,
     	InvoiceService $invoiceService,
		InvoiceSender $invoiceSender,
		InvoiceRepositoryInterface $invoiceRepository,
	    \Magento\Framework\DB\Transaction $transaction,
		\Psr\Log\LoggerInterface $Psrlogger ,
	  	\Magento\Framework\App\Response\Http $response,
		\Magento\Framework\Message\ManagerInterface  $messageManager,
		\Magento\Store\Api\Data\StoreInterface $store,
		\Magento\Framework\Locale\Resolver $resolver,
		WriterInterface $configWriter
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
        $this->httpClientFactory = $httpClientFactory;
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
    
    private function get_payment_provider() {
        return ($this->getConfigData( 'provider') === 'btcpay')? 'btcpay' : 'coinsnap';
    }

    public function getStoreId() {
        return ($this->get_payment_provider() === 'btcpay')? $this->getConfigData('btcpay_store_id') : $this->getConfigData('store_id');
    }
	
    public function getApiKey() {
        return ($this->get_payment_provider() === 'btcpay')? $this->getConfigData('btcpay_api_key') : $this->getConfigData('api_key');
    }
    
    public function getApiUrl() {
        return ($this->get_payment_provider() === 'btcpay')? $this->getConfigData('btcpay_api_url') : COINSNAP_SERVER_URL;
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

    public function setConfigData($field, $value){        
        $path = 'payment/' . $this->_code . '/' . $field;
        return $this->configWriter->save($path, $value);
    }

    public function updateWebhookUrl(){        
		
        if (!($this->webhookExists($this->getApiUrl(), $this->getApiKey(), $this->getStoreId()))){
            $this->registerWebhook($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());
	}
	else {
            $this->setConfigData('webhook_status', 'Yes');
	}
			        
    }
		
    public function checkAmount($amount, $currency)
    {
        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $store = new \Coinsnap\Client\Store($this->getApiUrl(), $this->getApiKey());
        $checkInvoice = [];

        try {
            $_provider = $this->get_payment_provider();
            if ($_provider === 'btcpay') {
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
		
    /**
     * Return payment page url according to environment
     * @return string
     */

    public function getCoinsnapUrl() {		
        $order = $this->_getOrder();
	$billing = $order->getBillingAddress();				
	$order_id = $order->getEntityId();
		
	$amount = number_format(($order->getGrandTotal()), 2,'.', '');
        $currency = $order->getBaseCurrencyCode();						
	$this->updateWebhookUrl();
        
        $client = new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $checkInvoice = $this->checkAmount($amount,strtoupper( $currency ));	
	
        if($checkInvoice['result'] === true){
				
            $redirectUrl = (!empty($this->getConfigData('returnurl')))? $this->getConfigData('returnurl') : $this->helper->getUrl("checkout/onepage/success");
            
            $buyerName   	= $billing->getFirstname().' '.$billing->getLastname();
            $buyerEmail    	= $order->getCustomerEmail();	
			
            $metadata = [];
            $metadata['orderNumber'] = $order_id;
            $metadata['customerName'] = $buyerName;
                
            if($this->get_payment_provider() === 'btcpay') {
                $metadata['orderId'] = $order_id;
            }
            
            if($this->getPaymentProvider() === 'btcpay' && $currency !== 'BTC'){
                    $store = new \Coinsnap\Client\Store($this->getApiUrl(), $this->getApiKey());
                    $btcpayCurrencies = $store -> getStoreCurrenciesRates($this->getStoreId(),array($currency));
                    $isCurrency = true;
                    if(!isset($btcpayCurrencies['result']['error']) && count($btcpayCurrencies['result']['currencies'])>0){
                            if(!isset($btcpayCurrencies['result']['currencies']['BTC_'.$currency])){
                                $isCurrency = false;
                            }
                    }
                    else {
                        $isCurrency = false;
                    }

                    // Handle currencies non-supported by BTCPay Server, we need to change them BTC and adjust the amount.
                    if( !$isCurrency ){
                            $currency = 'BTC';
                            $rate = 1/$checkInvoice['rate'];
                            $amountBTC = bcdiv(strval($amount), strval($rate), 8);
                            $amount = (float)$amountBTC;
                    }
            }

            $camount = ($currency === 'BTC')? \Coinsnap\Util\PreciseNumber::parseFloat($amount,8) : \Coinsnap\Util\PreciseNumber::parseFloat($amount,2);
                
            $redirectAutomatically = ($this->getConfigData( 'autoredirect') > 0)? true : false;
            $walletMessage = '';

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
                $walletMessage
            );
		
            $payurl = $csinvoice->getData()['checkoutLink'] ;
		
            if (empty($payurl)) {
                $errorText = 'API Error';
                $order->cancel()->save();
                $this->_checkoutSession->restoreQuote();			
                $this->messageManager->addErrorMessage($errorText);
                $payurl = $this->helper->getUrl('checkout/onepage/failure');
            }
		
            $this->response->setRedirect($payurl);	
        }
        else {
            
            if($checkInvoice['error'] === 'currencyError'){
                $errorText = sprintf('Currency %1$s is not supported by Coinsnap', strtoupper( $currency ));
            }      
            elseif($checkInvoice['error'] === 'amountError'){
                $errorText = sprintf( 'Invoice amount cannot be less than %1$s %2$s', $checkInvoice['min_value'], strtoupper( $currency ));
            }
            else {
                $errorText = $checkInvoice['error'];
            }
            
            $this->_logger->debug('Payment error: '.$errorText);
            $this->messageManager->addErrorMessage($errorText);
        }
        exit;
    }

	
	
    public function validateResponse(){		
	
        try {
            $rawPostData = file_get_contents("php://input");
            if (!$rawPostData) {
                http_response_code(400);
                die('No raw post data received');
            }
            $this->_logger->debug('Coinsnap Webhook Data: '.$rawPostData);

            // Get headers and check for signature
            $headers = getallheaders();
            $signature = null; $payloadKey = null;

            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'x-coinsnap-sig' || strtolower($key) === 'btcpay-sig') {
                    $signature = $value;
                    $payloadKey = strtolower($key);
                }
            }

            // Handle missing or invalid signature
            if (!isset($signature)) {
                http_response_code(401);
                die('Authentication required');
            }

            // Validate the signature
            $webhook = json_decode($this->getConfigData('coinsnap_webhook'), true);
            if (!Webhook::isIncomingWebhookRequestValid($rawPostData, $signature, $webhook['secret'])) {
                http_response_code(401);
                die('Invalid authentication signature for '.$payloadKey);
            }

            try {

                // Parse the JSON payload
                $postData = json_decode($rawPostData, false, 512, JSON_THROW_ON_ERROR);

                if (!isset($postData->invoiceId)) {
                    http_response_code(400);
                    die('No Coinsnap invoiceId provided');
                }

                if(strpos($postData->invoiceId,'test_') !== false){
                    http_response_code(200);
                    die('Successful webhook test');
                }

                $invoice_id = $postData->invoiceId;

                $client = new \Coinsnap\Client\Invoice( $this->getApiUrl(), $this->getApiKey() );	
                $csinvoice = $client->getInvoice($this->getStoreId(), $invoice_id);
                $status = $csinvoice->getData()['status'] ;
                $order_id = ($this->get_payment_provider() === 'btcpay')? $csinvoice->getData()['metadata']['orderId'] : $csinvoice->getData()['orderId'];
                $postData->payment_status = $status;
                $postData->order_id = $csinvoice->getData()['orderId'] ;	
                $this->_logger->debug('Coinsnap Webhook - Order Id: '.$order_id);	
            }
            catch (JsonException $e) {
                http_response_code(400);
                die('Invalid JSON payload');
            }
        }
        catch (\Throwable $e) {
            $postData->payment_status = 'api_fail';
            $this->_logger->debug('Coinsnap Webhook - Error: '.$e->getMessage());
        }
        return $postData;
    }

    public function UpdatePaymentStatus(\Magento\Sales\Model\Order $order, \Magento\Framework\DataObject $payment, $params) {	
			
        $order_status = '';
        $status = $params->payment_status;
        
        switch($status){
            case 'Expired':
            case 'InvoiceExpired':    
                $order_status = $this->getExpiredStatus();
                break;
            case 'Processing':
            case 'InvoiceProcessing':
                $order_status = $this->getProcessingStatus();
                break;
            case 'Settled':
            case 'InvoiceSettled':
                $order_status = $this->getSettledStatus();
                break;
            default: break;
        }
		
        //  Order status and transaction saving if status is "processing"
	if ($order_status == $this->getProcessingStatus()) {
            $totalAmount = number_format(($order->getGrandTotal()), 2,'.', '');	
            $trans_id = $params->invoiceId;
            
            // Transaction saving
            $payment->setTransactionId($trans_id);        
            $payment->addTransaction(TransactionInterface::TYPE_ORDER);
            $payment->setStatus('APPROVED');				
            $payment->setIsTransactionClosed(0)->save();					
		
            //  Order status saving
            $order->setStatus($order_status);				
            $order->setTotalPaid($totalAmount);
            $order->save();
	
            $this->createInvoice($order, $trans_id);
        }
	elseif ($order_status != ''){
            $order->setStatus($order_status);
            $order->save();	
	}
    }			 	
	
    public function webhookExists(string $apiUrl, string $apiKey, string $storeId): bool {
	$whClient = new \Coinsnap\Client\Webhook( $apiUrl, $apiKey );
        $webhook = $this->getConfigData('coinsnap_webhook');
                
        if ($storedWebhook = json_decode($webhook, true)) {
            
            try {
		$existingWebhook = $whClient->getWebhook( $storeId, $storedWebhook['id'] );
                
                if($existingWebhook->getData()['id'] === $storedWebhook['id'] && strpos( $existingWebhook->getData()['url'], $storedWebhook['url'] ) !== false){
                    return true;
		}
            }
            catch (\Throwable $e) {
		$errorMessage = 'Error fetching existing Webhook. Message: '.$e->getMessage();
            }
	}
        try {
            $storeWebhooks = $whClient->getWebhooks( $storeId );
            foreach($storeWebhooks as $webhook){
                if(strpos( $webhook->getData()['url'], $this->get_webhook_url() ) !== false){
                    $whClient->deleteWebhook( $storeId, $webhook->getData()['id'] );
                }
            }
        }
        catch (\Throwable $e) {
            $errorMessage = sprintf('Error fetching webhooks for store ID %1$s Message: ', $storeId).$e->getMessage();
        }
        
	return false;
    }
        
    public function registerWebhook(string $storeId, string $apiKey, string $webhook): bool {	
        try {			
            $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $apiKey);
            $webhook_events = ($this->get_payment_provider() === 'btcpay')? self::BTCPAY_WEBHOOK_EVENTS : self::COINSNAP_WEBHOOK_EVENTS;
            $webhook = $whClient->createWebhook(
                $storeId,   //$storeId
		$webhook, //$url
		$webhook_events,   //$specificEvents
		null    //$secret
            );								
            
            $this->setConfigData('coinsnap_webhook',
                json_encode([
                    'id' => $webhook->getData()['id'],
                    'secret' => $webhook->getData()['secret'],
                    'url' => $webhook->getData()['url']
                ])
            );

            return $webhook;
	}
        catch (\Throwable $e) {
            print_r($e);
            return false;	
	}

	return false;
    }
	
    protected function createInvoice($order, $trans_id){
        
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
	$invoice->setTransactionId($trans_id);
        $invoice = $this->invoiceRepository->save($invoice);
        $this->registry->register('current_invoice', $invoice);

        $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());

        $transactionSave->save();
        $this->invoiceSender->send($invoice);
    }
}
	
	
