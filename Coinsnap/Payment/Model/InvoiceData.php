<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Model;

/**
 * The order data sent with a new Coinsnap or BTCPay invoice: the order number (increment ID) as orderId,
 * the buyer, the items and the totals, so the merchant can find the order from the invoice.
 *
 * The webhook finds the order by the entity ID in metadata `magentoOrderId` (see orderEntityId()).
 */
class InvoiceData
{
    // Metadata key with the order's entity ID, which the webhook loads the order by.
    public const ENTITY_ID_KEY = 'magentoOrderId';

    private const MAX_ITEM_DESC = 255;

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array{orderId: string, buyerEmail: string, buyerName: string, metadata: array<string, mixed>}
     */
    public static function fromOrder($order, string $orderUrl, string $pluginVersion, string $language): array
    {
        $orderNumber = (string) $order->getIncrementId();
        $billing = $order->getBillingAddress();

        $name = $billing ? trim($billing->getFirstname() . ' ' . $billing->getLastname()) : '';
        if ($name === '') {
            $name = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
        }
        $email = trim((string) $order->getCustomerEmail());

        $metadata = [
            'orderId' => $orderNumber,
            'orderNumber' => $orderNumber,
            self::ENTITY_ID_KEY => (string) $order->getEntityId(),
            'orderUrl' => $orderUrl,
            'platform' => 'magento',
            'pluginVersion' => $pluginVersion,
            'customerName' => $name,
            'buyerName' => $name,
            'buyerEmail' => $email,
        ];

        if ($billing) {
            $street = $billing->getStreet();
            $street = is_array($street) ? array_values(array_filter(array_map('trim', $street), 'strlen')) : [trim((string) $street)];
            $metadata += [
                'buyerCompany' => trim((string) $billing->getCompany()),
                'buyerPhone' => trim((string) $billing->getTelephone()),
                'buyerAddress1' => (string) ($street[0] ?? ''),
                'buyerAddress2' => implode(' ', array_slice($street, 1)),
                'buyerCity' => trim((string) $billing->getCity()),
                'buyerZip' => trim((string) $billing->getPostcode()),
                'buyerState' => trim((string) ($billing->getRegionCode() ?: $billing->getRegion())),
                'buyerCountry' => trim((string) $billing->getCountryId()),
            ];
        }

        $metadata['buyerMessage'] = trim((string) $order->getCustomerNote());

        $lineItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $quantity = (float) $item->getQtyOrdered();
            $lineItems[] = self::withoutEmpty([
                'name' => (string) $item->getName(),
                'sku' => (string) $item->getSku(),
                'productId' => (string) $item->getProductId(),
                'quantity' => floor($quantity) === $quantity ? (int) $quantity : $quantity,
                'price' => self::amount($item->getPrice()),
                'total' => self::amount($item->getRowTotal()),
            ]);
        }
        $names = array_map(static fn (array $item): string => $item['quantity'] . ' x ' . ($item['name'] ?? ''), $lineItems);
        $skus = array_values(array_unique(array_filter(array_map(static fn (array $item): string => $item['sku'] ?? '', $lineItems))));

        $metadata += [
            'itemDesc' => self::truncate($names !== [] ? implode(', ', $names) : 'Order ' . $orderNumber),
            'itemCode' => implode(', ', $skus),
            'lineItems' => $lineItems,
            'couponCodes' => trim((string) $order->getCouponCode()),
            'discountTotal' => abs(self::amount($order->getDiscountAmount())),
            'shippingMethod' => trim((string) $order->getShippingDescription()),
            'shippingTotal' => self::amount($order->getShippingAmount()),
            'taxTotal' => self::amount($order->getTaxAmount()),
            'language' => $language,
        ];

        return [
            'orderId' => $orderNumber,
            'buyerEmail' => $email,
            'buyerName' => $name,
            'metadata' => self::withoutEmpty($metadata),
        ];
    }

    /**
     * Entity ID of the order an invoice was created for, or '' when it has none.
     * Invoices created before magentoOrderId existed carry the entity ID as orderId
     * (metadata on BTCPay, top level on Coinsnap).
     */
    public static function orderEntityId(array $invoice, bool $isBtcpay): string
    {
        $metadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $id = array_key_exists(self::ENTITY_ID_KEY, $metadata)
            ? $metadata[self::ENTITY_ID_KEY]
            : ($isBtcpay ? ($metadata['orderId'] ?? '') : ($invoice['orderId'] ?? $metadata['orderId'] ?? ''));

        return is_scalar($id) && ctype_digit((string) $id) ? (string) $id : '';
    }

    private static function amount($value): float
    {
        return round((float) $value, 2);
    }

    // Empty strings, nulls and empty lists are left out; zero amounts are kept.
    private static function withoutEmpty(array $values): array
    {
        return array_filter($values, static fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    }

    private static function truncate(string $text): string
    {
        return mb_strlen($text) > self::MAX_ITEM_DESC ? mb_substr($text, 0, self::MAX_ITEM_DESC - 3) . '...' : $text;
    }
}
