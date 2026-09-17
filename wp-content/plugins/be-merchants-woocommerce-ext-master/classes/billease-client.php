<?php

class BillEaseClient
{
    public function __construct($isSandbox, $merchantCode, $shopCode, $merchantJwt)
    {
        $this->isSandbox = $isSandbox;
        $this->merchant_code = $merchantCode;
        $this->shop_code = $shopCode;
        $this->merchant_jwt = $merchantJwt;
    }

    private function getHeaders()
    {
        return array(
            'Authorization' => 'Bearer ' . $this->merchant_jwt,
            'Content-Type' => 'application/json'
        );
    }

    private function handleResponse($response)
    {
        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message()
            );
        }

        $body = json_decode($response['body'], true);

        return $body;
    }

    private function getBaseUrl()
    {
        return $this->isSandbox ? BILLEASE_BASE_SANDBOX_URL : BILLEASE_BASE_PRODUCTION_URL;
    }

    public function createCheckout($payload)
    {
        $requestArgs = array(
            'body' => $payload,
            'method' => 'POST',
            'headers' => $this->getHeaders()
        );

        $response = wp_remote_post($this->getBaseUrl() . '/trx/checkout', $requestArgs);

        return $this->handleResponse($response);
    }
}
