<?php

namespace Coinsnap\Payment\Controller\Standard;

class Webhook extends \Coinsnap\Payment\Controller\CoinsnapAbstract {

    public function execute() {
        $request = $this->getRequest();
        [$status, $body] = $this->getPaymentMethod()->processWebhook(
            (string) $request->getContent(),
            (string) $request->getHeader('X-Coinsnap-Signature'),
            (string) $request->getHeader('BTCPay-Sig')
        );

        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode($status);
        $result->setContents($body);
        return $result;
    }
}
