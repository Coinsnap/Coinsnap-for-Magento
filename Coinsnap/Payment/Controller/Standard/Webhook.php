<?php

namespace Coinsnap\Payment\Controller\Standard;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

// The only route without Magento's form key: the provider posts here, and the signature is checked first.
class Webhook extends \Coinsnap\Payment\Controller\CoinsnapAbstract implements CsrfAwareActionInterface, HttpPostActionInterface {

    public function execute() {
        $request = $this->getRequest();
        try {
            [$status, $body] = $this->getPaymentMethod()->processWebhook(
                (string) $request->getContent(),
                (string) $request->getHeader('X-Coinsnap-Signature'),
                (string) $request->getHeader('BTCPay-Sig')
            );
        }
        catch (\Throwable $e) {
            // No stack trace or message in the reply; the provider retries on 503.
            $this->_logger->error('Coinsnap INFO webhook reason=retry: order update failed (' . get_class($e) . ')');
            [$status, $body] = [503, 'Retry'];
        }

        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode($status);
        $result->setContents($body);
        return $result;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
