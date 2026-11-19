<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Test\Unit;

use Coinsnap\Http\ClientInterface;
use Coinsnap\Http\Response;
use Coinsnap\Http\ResponseInterface;
use Coinsnap\Payment\Model\InvoiceData;
use Coinsnap\Util\PreciseNumber;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Model/library/loader.php';
require_once __DIR__ . '/../../Model/InvoiceData.php';

// Getter-only stand-in for Magento's DataObject based order, address and item models.
class FakeModel
{
    public function __construct(private array $data)
    {
    }

    public function __call(string $method, array $args)
    {
        $key = lcfirst(substr($method, 3));
        return $this->data[$key] ?? null;
    }
}

class RecordingHttpClient implements ClientInterface
{
    public array $requests = [];

    public function request(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'body' => json_decode($body, true)];
        return new Response(200, '{"id":"inv_1","checkoutLink":"https://pay.example/i/inv_1"}', []);
    }
}

// Captures the create-invoice request the module sends (InvoiceData + the bundled library's createInvoice).
class InvoiceDataTest extends TestCase
{
    private function order(bool $guestWithoutAddress = false): FakeModel
    {
        $billing = new FakeModel([
            'firstname' => 'Ada', 'lastname' => 'Lovelace', 'company' => 'Engines Ltd', 'telephone' => '+44 20 1234',
            'street' => ['1 Main St', 'Flat 2'], 'city' => 'London', 'postcode' => 'N1 1AA', 'regionCode' => 'LND', 'countryId' => 'GB',
        ]);

        return new FakeModel([
            'incrementId' => '000000042',
            'entityId' => '42',
            'customerEmail' => $guestWithoutAddress ? null : 'ada@example.com',
            'customerFirstname' => $guestWithoutAddress ? null : 'Ada',
            'customerLastname' => $guestWithoutAddress ? null : 'Lovelace',
            'customerNote' => $guestWithoutAddress ? null : 'Leave at the door',
            'billingAddress' => $guestWithoutAddress ? null : $billing,
            'allVisibleItems' => [
                new FakeModel(['name' => 'Coffee mug', 'sku' => 'MUG-1', 'productId' => '7', 'qtyOrdered' => '2.0000', 'price' => '12.5000', 'rowTotal' => '25.0000']),
                new FakeModel(['name' => 'T-shirt', 'sku' => 'TS-9', 'productId' => '9', 'qtyOrdered' => '1.0000', 'price' => '20.0000', 'rowTotal' => '20.0000']),
            ],
            'couponCode' => $guestWithoutAddress ? null : 'AUTUMN10',
            'discountAmount' => $guestWithoutAddress ? '0.0000' : '-4.5000',
            'shippingDescription' => 'Flat Rate - Fixed',
            'shippingAmount' => '5.0000',
            'taxAmount' => '8.1000',
        ]);
    }

    private function send(array $request, string $apiUrl): array
    {
        $http = new RecordingHttpClient();
        (new \Coinsnap\Client\Invoice($apiUrl, 'test-key', $http))->createInvoice(
            'store-1', 'EUR', PreciseNumber::parseString('53.60'), $request['orderId'],
            $request['buyerEmail'] !== '' ? $request['buyerEmail'] : null,
            $request['buyerName'] !== '' ? $request['buyerName'] : null,
            'https://shop.example/checkout/onepage/success', 'D13456', $request['metadata'], true, ''
        );
        $this->assertCount(1, $http->requests);

        return $http->requests[0]['body'];
    }

    public static function providers(): array
    {
        return ['coinsnap' => ['https://app.coinsnap.io'], 'btcpay' => ['https://btcpay.example']];
    }

    /** @dataProvider providers */
    public function testRequestCarriesOrderNumberAndOrderData(string $apiUrl): void
    {
        $request = InvoiceData::fromOrder($this->order(), 'https://shop.example/admin/sales/order/view/order_id/42/', '1.2.0', 'en_GB');
        $body = $this->send($request, $apiUrl);
        $meta = $body['metadata'];

        $this->assertSame('000000042', $body['orderId']);
        $this->assertSame('ada@example.com', $body['buyerEmail']);
        $this->assertSame([
            'orderId' => '000000042',
            'orderNumber' => '000000042',
            'magentoOrderId' => '42',
            'orderUrl' => 'https://shop.example/admin/sales/order/view/order_id/42/',
            'platform' => 'magento',
            'pluginVersion' => '1.2.0',
            'customerName' => 'Ada Lovelace',
            'buyerName' => 'Ada Lovelace',
            'buyerEmail' => 'ada@example.com',
            'buyerCompany' => 'Engines Ltd',
            'buyerPhone' => '+44 20 1234',
            'buyerAddress1' => '1 Main St',
            'buyerAddress2' => 'Flat 2',
            'buyerCity' => 'London',
            'buyerZip' => 'N1 1AA',
            'buyerState' => 'LND',
            'buyerCountry' => 'GB',
            'buyerMessage' => 'Leave at the door',
            'itemDesc' => '2 x Coffee mug, 1 x T-shirt',
            'itemCode' => 'MUG-1, TS-9',
            'lineItems' => [
                ['name' => 'Coffee mug', 'sku' => 'MUG-1', 'productId' => '7', 'quantity' => 2, 'price' => 12.5, 'total' => 25],
                ['name' => 'T-shirt', 'sku' => 'TS-9', 'productId' => '9', 'quantity' => 1, 'price' => 20, 'total' => 20],
            ],
            'couponCodes' => 'AUTUMN10',
            'discountTotal' => 4.5,
            'shippingMethod' => 'Flat Rate - Fixed',
            'shippingTotal' => 5,
            'taxTotal' => 8.1,
            'language' => 'en_GB',
        ], $meta);

        // The webhook finds the order from this invoice by its entity ID.
        $this->assertSame('42', InvoiceData::orderEntityId(['orderId' => $body['orderId'], 'metadata' => $meta], $apiUrl === 'https://btcpay.example'));
    }

    public function testGuestWithoutAddressSendsNoEmptyValues(): void
    {
        $request = InvoiceData::fromOrder($this->order(true), '', '', '');
        $body = $this->send($request, 'https://app.coinsnap.io');

        $this->assertSame('000000042', $body['orderId']);
        $this->assertNull($body['buyerEmail']);
        foreach ($body['metadata'] as $key => $value) {
            $this->assertNotContains($value, ['', null, []], "metadata.$key is empty");
            $this->assertStringStartsNotWith('buyer', $key);
        }
        $this->assertArrayNotHasKey('orderUrl', $body['metadata']);
        $this->assertSame(0.0, (float) $body['metadata']['discountTotal']);
    }

    public function testWebhookOrderIdForNewAndOlderInvoices(): void
    {
        // New invoices: orderId is the increment ID, the entity ID is in metadata.magentoOrderId.
        $new = ['orderId' => '000000042', 'metadata' => ['orderId' => '000000042', 'magentoOrderId' => '42']];
        $this->assertSame('42', InvoiceData::orderEntityId($new, false));
        $this->assertSame('42', InvoiceData::orderEntityId($new, true));

        // Invoices created before this change: orderId was the entity ID.
        $this->assertSame('41', InvoiceData::orderEntityId(['orderId' => '41', 'metadata' => ['orderId' => '41']], false));
        $this->assertSame('41', InvoiceData::orderEntityId(['metadata' => ['orderId' => '41']], true));
        // BTCPay invoices: only metadata counts, as before.
        $this->assertSame('', InvoiceData::orderEntityId(['orderId' => '41', 'metadata' => []], true));

        // magentoOrderId present but not an ID: no fallback to orderId.
        $this->assertSame('', InvoiceData::orderEntityId(['orderId' => '41', 'metadata' => ['magentoOrderId' => 'x']], false));
        $this->assertSame('', InvoiceData::orderEntityId(['orderId' => ['41']], false));
        $this->assertSame('', InvoiceData::orderEntityId([], false));
    }
}
