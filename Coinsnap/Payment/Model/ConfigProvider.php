<?php

namespace Coinsnap\Payment\Model;

use Magento\Framework\View\Asset\Repository;
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    protected $methodCode = \Coinsnap\Payment\Model\Payment::COINSNAP_PAYMENT_CODE;
    
    
    protected $method;
    private $assetRepo;
	

    public function __construct(\Magento\Payment\Helper\Data $paymenthelper, Repository $assetRepo){
        $this->method = $paymenthelper->getMethodInstance($this->methodCode);
        $this->assetRepo = $assetRepo;
    }

    public function getConfig(){

        return $this->method->isAvailable() ? [
            'payment'=>['coinsnap_payment'=>[                
                'getPaymentSrc'=>$this->getPaymentSrc()
            ]
        ]
        ]:[];
    }

    public function getPaymentSrc()
    {
        return $this->assetRepo->getUrl('Coinsnap_Payment::images/front-logo.png');
    }
}
