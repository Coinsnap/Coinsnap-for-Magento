<?php
namespace Coinsnap\Payment\Model;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Registry;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use \Magento\Framework\App\Config\Storage\WriterInterface;





class Payment extends \Magento\Payment\Model\Method\AbstractMethod {

    const COINSNAP_PAYMENT_CODE = 'coinsnap_payment';
	const WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];	   

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
		
		if (!defined('COINSNAP_SERVER_PATH'))	define( 'COINSNAP_SERVER_PATH', 'stores' );  
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

	
	protected function _getOrder()
    {
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

	public function getStoreId() {
        return $this->getConfigData('store_id');
    }
	
	public function getApiKey() {
        return $this->getConfigData('api_key');
    }	
	public function getApiUrl() {
        return $this->getConfigData('api_url');
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

	public function setConfigData($field, $value)
    {        
        $path = 'payment/' . $this->_code . '/' . $field;
        return $this->configWriter->save($path, $value);
    }

	public function updateWebhookUrl()
    {        
		$webhook_status =  $this->getConfigData('webhook_status');
		if (empty($webhook_status)) {
			$WebhookUrl = $this->getWebhookUrl() ;
			if (!($this->webhookExists($this->getStoreId(), $this->getApiKey(), $WebhookUrl))){
				$this->registerWebhook($this->getStoreId(), $this->getApiKey(), $WebhookUrl);
			}
			else {
				$this->setConfigData('webhook_status', 'Yes');
			}

		}		        
    }
		
		
    /**
     * Return url according to environment
     * @return string
     */
	//get payment page url
    public function getCoinsnapUrl() {		
		$order = $this->_getOrder();
		$billing = $order->getBillingAddress();				
		$order_id = $order->getEntityId();
		
		$amount = number_format(($order->getGrandTotal()), 2,'.', '');	
				
		$buyerName   	= $billing->getFirstname().' '.$billing->getLastname();
		$buyerEmail    	= $order->getCustomerEmail();	
		$currency_code = $order->getBaseCurrencyCode();						
		$this->updateWebhookUrl();		
		
		$redirectUrl =$this->helper->getUrl("checkout/onepage/success");
		$metadata = [];
		$metadata['orderNumber'] = $order_id;
		$metadata['customerName'] = $buyerName;

		$checkoutOptions = new \Coinsnap\Payment\Api\Client\InvoiceCheckoutOptions();
		$checkoutOptions->setRedirectURL( $redirectUrl );
		$client = new \Coinsnap\Payment\Api\Client\Invoice( $this->getApiUrl(), $this->getApiKey() );
		$camount = \Coinsnap\Payment\Api\Util\PreciseNumber::parseFloat($amount,2);
		$invoice = $client->createInvoice(
			$this->getStoreId(),  
			$currency_code,
			$camount,
			$order_id,
			$buyerEmail,
			$buyerName, 
			$redirectUrl,
			'',     
			$metadata,
			$checkoutOptions
		);
		
		$payurl = $invoice->getData()['checkoutLink'] ;
		
		if (empty($payurl)) {
			$errorText = 'API Error';
			$order->cancel()->save();
            $this->_checkoutSession->restoreQuote();			
			$this->messageManager->addErrorMessage($errorText);
			$payurl = $this->helper->getUrl('checkout/onepage/failure');
		}
		
		$this->response->setRedirect($payurl);		
    }

	
	
	
	public function validateResponse(){		
		$notify_json = file_get_contents('php://input');				
		$this->_logger->debug('Coinsnap Webhook - Data: '.$notify_json);
		$notify_ar = json_decode($notify_json, true);		
		$invoice_id = $notify_ar['invoiceId'];
		
		try {
			$client = new \Coinsnap\Payment\Api\Client\Invoice( $this->getApiUrl(), $this->getApiKey() );			
			$invoice = $client->getInvoice($this->getStoreId(), $invoice_id);
			$status = $invoice->getData()['status'] ;
			$order_id = $invoice->getData()['orderId'] ;
			$notify_ar['payment_status'] = $status;
			$notify_ar['order_id'] = $invoice->getData()['orderId'] ;	
			$this->_logger->debug('Coinsnap Webhook - Order Id: '.$order_id);	
			print_r($invoice->getData())		;
	
		}catch (\Throwable $e) {			
			$notify_ar['payment_status'] = 'api_fail';
			$this->_logger->debug('Coinsnap Webhook - Error: '.$e->getMessage());						
			echo "Error";
			exit;
		}
		return $notify_ar;
	}
	

    public function UpdatePaymentStatus(\Magento\Sales\Model\Order $order,
            \Magento\Framework\DataObject $payment, $prams) {	
			
		$order_status = '';
		$status = $prams['payment_status'];		
		
		if ($status == 'Expired') $order_status = $this->getExpiredStatus();
		else if ($status == 'Processing') $order_status = $this->getProcessingStatus();
		else if ($status == 'Settled') $order_status = $this->getSettledStatus();					
		

		if ($order_status == 'processing') {
			$totalAmount = number_format(($order->getGrandTotal()), 2,'.', '');	
			$trans_id = $prams['invoiceId'];
        	$payment->setTransactionId($trans_id);        
        	$payment->addTransaction(TransactionInterface::TYPE_ORDER);
			$payment->setStatus('APPROVED');				
        	$payment->setIsTransactionClosed(0)->save();					
		
        	$order->setStatus($order_status);				
			$order->setTotalPaid($totalAmount);
        	$order->save();
			$this->createInvoice($order, $trans_id);
		}
		else if ($order_status != ''){
			$order->setStatus($order_status);
        	$order->save();	
		}
    }			 	
	
	public function webhookExists(string $storeId, string $apiKey, string $webhook): bool {	
		try {		
			$whClient = new \Coinsnap\Payment\Api\Client\Webhook( $this->getApiUrl(), $apiKey );		
			$Webhooks = $whClient->getWebhooks( $storeId );									
			
			
			foreach ($Webhooks as $Webhook){					
				//$this->deleteWebhook($storeId,$apiKey, $Webhook->getData()['id']);
				if ($Webhook->getData()['url'] == $webhook) return true;	
			}
		}catch (\Throwable $e) {			
			return false;
		}
	
		return false;
	}
	public function registerWebhook(string $storeId, string $apiKey, string $webhook): bool {	
		try {			
			$whClient = new \Coinsnap\Payment\Api\Client\Webhook($this->getApiUrl(), $apiKey);
			
			$webhook = $whClient->createWebhook(
				$storeId,   //$storeId
				$webhook, //$url
				self::WEBHOOK_EVENTS,   //$specificEvents
				null    //$secret
			);											
			return true;
		} catch (\Throwable $e) {
			return false;	
		}

		return false;
	}

	public function deleteWebhook(string $storeId, string $apiKey, string $webhookid): bool {	    
		
		try {			
			$whClient = new \Coinsnap\Payment\Api\Client\Webhook($this->api_url, $apiKey);
			
			$webhook = $whClient->deleteWebhook(
				$storeId,   //$storeId
				$webhookid, //$url			
			);					
			return true;
		} catch (\Throwable $e) {
			
			return false;	
		}


    }	
	
	protected function createInvoice($order, $trans_id)
    {
        
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
		$invoice->setTransactionId($trans_id);
        $invoice = $this->invoiceRepository->save($invoice);
		
        $this->registry->register('current_invoice', $invoice);

        $transactionSave = 
			$this->transaction->addObject($invoice)
			->addObject($invoice->getOrder());

        $transactionSave->save();
        $this->invoiceSender->send($invoice);

    }
}
	
	
