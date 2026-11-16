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
        $newKey = trim((string) $this->getValue());
        if (!$keepsStoredKey && $newKey !== '') {
            $problem = \Coinsnap\Payment\Model\Config\BtcpayKeyCheck::problem(
                trim((string) ($this->getFieldsetDataValue('btcpay_server_url') ?? $this->storedValue('btcpay_server_url'))),
                $newKey,
                trim((string) ($this->getFieldsetDataValue('btcpay_store_id') ?? $this->storedValue('btcpay_store_id')))
            );
            if ($problem !== null) {
                throw new \Magento\Framework\Exception\LocalizedException(__($problem));
            }
        }
        return parent::beforeSave();
    }

    private function storedValue(string $field): string
    {
        return (string) $this->_config->getValue('payment/coinsnap_payment/' . $field, $this->getScope() ?: 'default', $this->getScopeCode());
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
