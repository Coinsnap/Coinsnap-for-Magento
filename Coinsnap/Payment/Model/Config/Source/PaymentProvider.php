<?php
namespace Coinsnap\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentProvider implements ArrayInterface {
    public function toOptionArray() {
        return [
            ['value' => 'coinsnap', 'label' => 'Coinsnap'],
            ['value' => 'btcpay', 'label' => 'BTCPay Server']
        ];
    }
}
