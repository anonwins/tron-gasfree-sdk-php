<?php
/**
 * Tron GasFree SDK for PHP
 * 
 * This SDK provides a PHP interface for interacting with the GasFree service,
 * allowing users to perform gas-free token transfers on the TRON network.
 * 
 * @package tron-gasfree-sdk-php
 * @version 1.0.0
 */

class TronGasFreeSDK
{
    // API Version
    private const API_VERSION = 'V1.0.0';
    
    // Network Constants
    private const MAINNET_CHAIN_ID = 728126428;
    private const TESTNET_CHAIN_ID = 3448148188;
    private const MAINNET_CONTRACT = 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U';
    private const TESTNET_CONTRACT = 'THQGuFzL87ZqhxkgqYEryRAd7gqFqL5rdc';
    
    // API Endpoints
    private const MAINNET_BASE_URL = 'https://open.gasfree.io/tron';
    private const TESTNET_BASE_URL = 'https://open-test.gasfree.io/nile';

    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private bool $isTestnet;

    /**
     * Constructor
     * @param string $apiKey Your GasFree API key
     * @param string $apiSecret Your GasFree API secret
     * @param bool $isTestnet Whether to use testnet (default: false)
     */
    public function __construct(string $apiKey, string $apiSecret, bool $isTestnet = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->isTestnet = $isTestnet;
        $this->baseUrl = $isTestnet ? self::TESTNET_BASE_URL : self::MAINNET_BASE_URL;
    }

    /**
     * Get all supported tokens
     * @return array Array containing supported tokens with their details
     * @throws Exception
     */
    public function getAllTokens(): array
    {
        return $this->makeRequest('GET', '/api/v1/config/token/all');
    }

    /**
     * Get all supported service providers
     * @return array Array containing supported providers with their details
     * @throws Exception
     */
    public function getAllProviders(): array
    {
        return $this->makeRequest('GET', '/api/v1/config/provider/all');
    }

    /**
     * Get GasFree account information
     * @param string $accountAddress User's EOA address
     * @return array Array containing account details including status, balance, and nonce
     * @throws Exception
     */
    public function getAccountInfo(string $accountAddress): array
    {
        if (empty($accountAddress)) {
            throw new Exception('Address cannot be empty');
        }
        return $this->makeRequest('GET', "/api/v1/address/{$accountAddress}");
    }

    /**
     * Submit a GasFree transfer authorization
     * @param array $params Transfer parameters
     * @return array Array containing transfer authorization details
     * @throws Exception
     */
    public function submitTransfer(array $params): array
    {
        $requiredParams = [
            'token', 'serviceProvider', 'user', 'receiver',
            'value', 'maxFee', 'deadline', 'version', 'nonce', 'sig'
        ];

        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new Exception("Missing required parameter: {$param}");
            }
        }

        if ($params['deadline'] <= time()) {
            throw new Exception('Deadline must be in the future');
        }

        return $this->makeRequest('POST', '/api/v1/gasfree/submit', $params);
    }

    /**
     * Get transfer authorization details
     * @param string $traceId The trace ID of the transfer authorization
     * @return array Array containing transfer details including status and transaction info
     * @throws Exception
     */
    public function getTransferDetails(string $traceId): array
    {
        if (empty($traceId)) {
            throw new Exception('Trace ID cannot be empty');
        }
        return $this->makeRequest('GET', "/api/v1/gasfree/{$traceId}");
    }

    /**
     * Make an authenticated request to the GasFree API
     * @param string $method HTTP method
     * @param string $path API path
     * @param array|null $data Request data (for POST requests)
     * @return array
     * @throws Exception
     */
    private function makeRequest(string $method, string $path, ?array $data = null): array
    {
        $timestamp = time();
        $message = $method . $path . $timestamp;
        
        // Generate signature
        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $message,
                $this->apiSecret,
                true
            )
        );

        // Prepare headers
        $headers = [
            'Timestamp: ' . $timestamp,
            'Authorization: ApiKey ' . $this->apiKey . ':' . $signature,
            'Content-Type: application/json'
        ];

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST' && $data !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('cURL request failed: ' . curl_error($ch));
        }

        $result = json_decode($response, true);
        if ($result === null) {
            throw new Exception('Failed to parse API response');
        }

        if ($httpCode !== 200) {
            throw new Exception(
                'API request failed: ' . ($result['message'] ?? 'Unknown error') . 
                ' (Code: ' . ($result['code'] ?? $httpCode) . ')'
            );
        }

        return $result['data'] ?? [];
    }
} 
