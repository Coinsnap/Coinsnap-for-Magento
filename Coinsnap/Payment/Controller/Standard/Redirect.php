<?php

namespace Coinsnap\Payment\Controller\Standard;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Redirect extends \Coinsnap\Payment\Controller\CoinsnapAbstract implements HttpGetActionInterface {

    // getCoinsnapUrl() sets the redirect on the shared response.
    public function execute() {
        $this->getPaymentMethod()->getCoinsnapUrl();
        return $this->getResponse();
    }
}
