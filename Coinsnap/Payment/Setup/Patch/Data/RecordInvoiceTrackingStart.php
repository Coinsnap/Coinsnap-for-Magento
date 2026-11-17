<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

// Remembers when invoices started being recorded per order. Orders placed before have no record and are
// accepted only for one day after this time (guide rule 6.7); see Payment::isLegacyOrderAccepted().
class RecordInvoiceTrackingStart implements DataPatchInterface
{
    private const PATH = 'payment/coinsnap_payment/invoice_records_since';

    public function __construct(private ModuleDataSetupInterface $setup)
    {
    }

    public function apply()
    {
        $connection = $this->setup->getConnection();
        $table = $this->setup->getTable('core_config_data');
        $exists = $connection->fetchOne(
            $connection->select()->from($table, ['config_id'])->where('path = ?', self::PATH)->where('scope = ?', 'default')
        );
        if (!$exists) {
            $connection->insert($table, ['scope' => 'default', 'scope_id' => 0, 'path' => self::PATH, 'value' => (string) time()]);
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
