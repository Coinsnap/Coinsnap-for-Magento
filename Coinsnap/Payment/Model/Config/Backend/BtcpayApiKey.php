<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Model\Config\Backend;

// A BTCPay key is never kept for a different server: a changed server URL clears the stored key.
class BtcpayApiKey extends ApiKey
{
    public function beforeSave()
    {
        $keepsStoredKey = (bool) preg_match('/^\*+$/', (string) $this->getValue());
        if ($keepsStoredKey && $this->serverUrlChanged()) {
            $this->setValue('');
        }
        return parent::beforeSave();
    }

    private function serverUrlChanged(): bool
    {
        $submitted = $this->getFieldsetDataValue('btcpay_server_url');
        if ($submitted === null) {
            return false;
        }
        $stored = (string) $this->_config->getValue(
            'payment/coinsnap_payment/btcpay_server_url',
            $this->getScope() ?: 'default',
            $this->getScopeCode()
        );
        return rtrim(trim((string) $submitted), '/') !== rtrim(trim($stored), '/');
    }
}
