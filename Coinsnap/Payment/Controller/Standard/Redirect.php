<?php

namespace Coinsnap\Payment\Controller\Standard;

class Redirect extends \Coinsnap\Payment\Controller\CoinsnapAbstract {

    // getCoinsnapUrl() sets the redirect on the shared response.
    public function execute() {
        $this->getPaymentMethod()->getCoinsnapUrl();
        return $this->getResponse();
    }
}
