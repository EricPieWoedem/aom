<?php

class PawaPay
{
    private $token = '';
    private $environment = 'sandbox';
    private $url = 'https://api.sandbox.pawapay.io';

    public function __construct()
    {
        $envToken = getenv('PAWAPAY_API_KEY');
        $envMode = getenv('PAWAPAY_ENV');

        if (!empty($envToken)) {
            $this->token = $envToken;
        }

        if (!empty($envMode)) {
            $this->environment = strtolower($envMode);
        }

        if ($this->environment === 'live') {
            $this->url = 'https://api.pawapay.io';
        }
    }

    private function generateUUIDv4()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function request($method, $path, $payload = null)
    {
        if (empty($this->token)) {
            return array(null, 500, 'Missing PAWAPAY_API_KEY');
        }

        if (!function_exists('curl_init')) {
            return array(null, 500, 'PHP cURL extension is not enabled');
        }

        $curl = curl_init();
        $headers = array(
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
        );

        $options = array(
            CURLOPT_URL => $this->url . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        );

        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            return array(null, $httpCode, !empty($curlError) ? $curlError : 'Unknown cURL error');
        }

        return array(json_decode($response), $httpCode, null);
    }

    /**
     * Hosted Payment Page (Merchant API v2).
     *
     * @param string|null $country ISO 3166-1 alpha-3; required with amountDetails unless $phoneNumber is set
     * @param string|null $phoneNumber MSISDN digits only (country code, no leading 0)
     * @param array|null $metadata Optional; e.g. preferredProvider for dashboards/callbacks (hosted UI still lists wallets for country)
     * @see https://docs.pawapay.io/v2/api-reference/payment-page/deposit-via-payment-page
     */
    public function createPaymentPage($amount, $currency, $description, $returnUrl, $country = null, $phoneNumber = null, $metadata = null)
    {
        $depositId = $this->generateUUIDv4();
        $reason = trim((string) $description);
        if ($reason === '') {
            $reason = 'Coaching session';
        }
        if (strlen($reason) > 50) {
            $reason = substr($reason, 0, 50);
        }

        $payload = array(
            'depositId' => $depositId,
            'returnUrl' => (string) $returnUrl,
            'amountDetails' => array(
                'amount' => (string) $amount,
                'currency' => strtoupper((string) $currency),
            ),
            'reason' => $reason,
        );

        $countryCode = $country !== null && $country !== '' ? strtoupper(trim((string) $country)) : null;
        $phone = $phoneNumber !== null && $phoneNumber !== '' ? preg_replace('/\D/', '', (string) $phoneNumber) : null;
        if (!empty($countryCode)) {
            $payload['country'] = $countryCode;
        } elseif (!empty($phone)) {
            $payload['phoneNumber'] = $phone;
        }

        if (!empty($metadata) && is_array($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $requestResult = $this->request('POST', '/v2/paymentpage', $payload);
        $result = $requestResult[0];
        $httpCode = $requestResult[1];
        $error = $requestResult[2];

        $redirect = null;
        if (is_object($result)) {
            if (isset($result->redirectUrl)) {
                $redirect = $result->redirectUrl;
            } elseif (isset($result->redirectURL)) {
                $redirect = $result->redirectURL;
            }
        }

        return array(
            'result' => $result,
            'http_code' => $httpCode,
            'error' => $error,
            'deposit_id' => $depositId,
            'transaction_id' => $depositId,
            'redirect_url' => $redirect,
        );
    }

    /**
     * Direct deposit initiation (Merchant API v2).
     * @see https://docs.pawapay.io/v2/api-reference/deposits/initiate-deposit
     */
    public function deposit($transactionName, $amount, $currency, $country, $correspondent, $customerName, $customerEmail, $customerPhone)
    {
        $uuid = $this->generateUUIDv4();

        $msg = preg_replace('/[^a-zA-Z0-9 ]/', '', (string) $transactionName);
        $msg = trim($msg);
        if (strlen($msg) < 4) {
            $msg = 'Payment';
        }
        if (strlen($msg) > 22) {
            $msg = substr($msg, 0, 22);
        }

        $payload = array(
            'depositId' => $uuid,
            'payer' => array(
                'type' => 'MMO',
                'accountDetails' => array(
                    'provider' => (string) $correspondent,
                    'phoneNumber' => (string) $customerPhone,
                ),
            ),
            'amount' => (string) $amount,
            'currency' => strtoupper((string) $currency),
            'customerMessage' => $msg,
        );

        $requestResult = $this->request('POST', '/v2/deposits', $payload);
        $result = $requestResult[0];
        $httpCode = $requestResult[1];
        $error = $requestResult[2];

        return array(
            'result' => $result,
            'deposit_id' => $uuid,
            'http_code' => $httpCode,
            'error' => $error,
        );
    }

    /**
     * @see https://docs.pawapay.io/v2/api-reference/deposits/check-deposit-status
     */
    public function verifyTransaction($depositId)
    {
        $requestResult = $this->request('GET', '/v2/deposits/' . rawurlencode((string) $depositId));
        $result = $requestResult[0];
        $httpCode = $requestResult[1];
        $error = $requestResult[2];
        $tranStatus = 'pending';

        if (is_object($result) && isset($result->status) && $result->status === 'FOUND'
            && isset($result->data) && is_object($result->data) && isset($result->data->status)) {
            $status = strtoupper((string) $result->data->status);
            if ($status === 'COMPLETED') {
                $tranStatus = 'success';
            } elseif ($status === 'FAILED') {
                $tranStatus = 'rejected';
            }
        }

        return array(
            'result' => $result,
            'tran_status' => $tranStatus,
            'http_code' => $httpCode,
            'error' => $error,
        );
    }
}
