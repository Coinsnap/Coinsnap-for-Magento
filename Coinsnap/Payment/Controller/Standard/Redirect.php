<?php

namespace Coinsnap\Payment\Controller\Standard;


class Redirect extends \Coinsnap\Payment\Controller\CoinsnapAbstract {		
    public function execute() {		
        $this->getPaymentMethod()->getCoinsnapUrl();        
    }

}
