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
            $keepsStoredKey = false;
        }
        $serverUrl = trim((string) ($this->getFieldsetDataValue('btcpay_server_url') ?? $this->storedValue('btcpay_server_url')));
        $storeId = trim((string) ($this->getFieldsetDataValue('btcpay_store_id') ?? $this->storedValue('btcpay_store_id')));

        $newKey = trim((string) $this->getValue());
        if (!$keepsStoredKey && $newKey !== '') {
            $this->refuseUnlessLimitedToStore($serverUrl, $newKey, $storeId);
        }
        // A kept key was checked for the old store; it must also be limited to a newly entered one.
        if ($keepsStoredKey && $this->storeIdChanged($storeId)) {
            $storedKey = $this->storedValue('btcpay_api_key');
            $storedKey = preg_match('/^\d+:\d+:/', $storedKey) ? (string) $this->_encryptor->decrypt($storedKey) : $storedKey;
            if ($storedKey !== '') {
                $this->refuseUnlessLimitedToStore($serverUrl, $storedKey, $storeId);
            }
        }
        return parent::beforeSave();
    }

    private function refuseUnlessLimitedToStore(string $serverUrl, string $apiKey, string $storeId): void
    {
        $problem = \Coinsnap\Payment\Model\Config\BtcpayKeyCheck::problem($serverUrl, $apiKey, $storeId);
        if ($problem !== null) {
            throw new \Magento\Framework\Exception\LocalizedException(__($problem));
        }
    }

    private function storeIdChanged(string $submitted): bool
    {
        return $this->getFieldsetDataValue('btcpay_store_id') !== null && $submitted !== trim($this->storedValue('btcpay_store_id'));
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
