<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;

// Stored encrypted and shown as "******"; submitting the stars unchanged keeps the stored key.
class ApiKey extends Encrypted
{
}
