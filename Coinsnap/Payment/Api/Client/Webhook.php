<?php

declare(strict_types=1);

namespace Coinsnap\Payment\Api\Client;

class Webhook extends AbstractClient
{
    /**
     * @param string $storeId
     * @return \Coinsnap\Payment\Api\Result\WebhookList
     */
    public function getStoreWebhooks(string $storeId): \Coinsnap\Payment\Api\Result\WebhookList
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks';
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Payment\Api\Result\WebhookList(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function getWebhook(string $storeId, string $webhookId): \Coinsnap\Payment\Api\Result\Webhook
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Payment\Api\Result\Webhook($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    

    public function createWebhook(string $storeId, string $url, ?array $specificEvents, ?string $secret): \Coinsnap\Payment\Api\Result\WebhookCreated {
        $data = ['url' => $url];
        
        if(isset($specificEvents) && count($specificEvents) > 0){
            $data['events'] = $specificEvents;
        }
        
        if ($secret === '') {
            throw new \InvalidArgumentException('Argument $secret should be NULL (let Server auto-generate a secret) or you should provide a long and safe secret string.');
        } 
        elseif ($secret !== null) $data['secret'] = $secret;

        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks';
        $headers = $this->getRequestHeaders();
        
        $method = 'POST';
        $response = $this->getHttpClient()->request($method, $url, $headers, json_encode($data, JSON_THROW_ON_ERROR));	

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $result = new \Coinsnap\Payment\Api\Result\WebhookCreated($data);
            return $result;
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * Updates an existing webhook.
     *
     * @return \Coinsnap\Payment\Api\Result\Webhook
     * @throws \JsonException
     */
    public function updateWebhook(string $storeId, string $url, string $webhookId, ?array $specificEvents, bool $enabled = true,?string $secret = null): \Coinsnap\Payment\Api\Result\Webhook {
        $data = [
          'enabled' => $enabled,
          'url' => $url,
          'secret' => $secret
        ];

        if(isset($specificEvents) && count($specificEvents) > 0){
            $data['events'] = $specificEvents;
        }
        
        

        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId);
        $headers = $this->getRequestHeaders();
        $method = 'PUT';
        $response = $this->getHttpClient()->request($method, $url, $headers, json_encode($data, JSON_THROW_ON_ERROR));

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Payment\Api\Result\Webhook($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    //  Check if the request your received from a webhook is authentic and can be trusted.
    public static function isIncomingWebhookRequestValid(string $requestBody, string $coinsnapSignatureHeader, string $secret): bool{
        if ($requestBody && $coinsnapSignatureHeader) {            
            $expectedHeader = 'sha256='.hash_hmac('sha256', $requestBody, $secret);
            if ($expectedHeader === $coinsnapSignatureHeader) {
                return true;
            }
        }
        return false;
    }

    public function deleteWebhook(string $storeId, string $webhookId): void
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId);
        $headers = $this->getRequestHeaders();
        $method = 'DELETE';
        $response = $this->getHttpClient()->request($method, $url, $headers);		

        if ($response->getStatus() !== 200) {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * @deprecated 2.0.0 Please use `getStoreWebhooks()` instead.
     * @see getStoreWebhooks()
     *
     * @param string $storeId
     * @return \Coinsnap\Payment\Api\Result\Webhook[]
     */
    public function getWebhooks(string $storeId): array
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks';		
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);		

        if ($response->getStatus() === 200) {
            $r = [];
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $item) {
                $item = new \Coinsnap\Payment\Api\Result\Webhook($item);
                $r[] = $item;
            }
            return $r;
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
}
