<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Setup\Patch\Data;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

// Keys saved before 1.2.0 are plain text; the fields now store them encrypted.
class EncryptApiKeys implements DataPatchInterface
{
    private const PATHS = ['payment/coinsnap_payment/api_key', 'payment/coinsnap_payment/btcpay_api_key'];

    public function __construct(
        private ModuleDataSetupInterface $setup,
        private EncryptorInterface $encryptor
    ) {
    }

    public function apply()
    {
        $connection = $this->setup->getConnection();
        $table = $this->setup->getTable('core_config_data');
        $rows = $connection->fetchAll(
            $connection->select()->from($table, ['config_id', 'value'])->where('path IN (?)', self::PATHS)
        );
        foreach ($rows as $row) {
            $value = (string) $row['value'];
            if ($value === '' || preg_match('/^\d+:\d+:/', $value)) {
                continue;
            }
            $connection->update($table, ['value' => $this->encryptor->encrypt($value)], ['config_id = ?' => (int) $row['config_id']]);
        }
        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
