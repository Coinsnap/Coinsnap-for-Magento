<?php

namespace Coinsnap\Payment\Controller\Standard;

class Webhook extends \Coinsnap\Payment\Controller\CoinsnapAbstract {

    public function execute() {        		        
            $paymentMethod = $this->getPaymentMethod();
            $params = $paymentMethod->validateResponse();
            
            $order_id =  $params['order_id'];            
            
            
            if (!empty($order_id)) {
              $order = $this->getOrderById($order_id);
              $payment = $order->getPayment();
              $paymentMethod->UpdatePaymentStatus($order, $payment, $params);
              echo "OK";
            }
            else {
              echo "Fail";
            }
            exit;			    
   }
}
