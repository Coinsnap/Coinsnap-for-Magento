<?php

namespace Coinsnap\Payment\Controller\Standard;

class Webhook extends \Coinsnap\Payment\Controller\CoinsnapAbstract {

    public function execute() {        		        
            $paymentMethod = $this->getPaymentMethod();
            $params = $paymentMethod->validateResponse();
            $order_id =  $params['order_id'];            
            
            $function_response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
    
            
            
            if (!empty($order_id)) {
              $order = $this->getOrderById($order_id);
              $payment = $order->getPayment();
              $paymentMethod->UpdatePaymentStatus($order, $payment, $params);
              $function_response->setContents("OK");
            }
            else {
                $function_response->setContents("Fail");
            }
            return $function_response;		    
   }
}
