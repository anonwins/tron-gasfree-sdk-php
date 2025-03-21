<?php
/**
 * Tron GasFree SDK for PHP
 * 
 * This SDK provides a PHP interface for interacting with the GasFree service,
 * allowing users to perform gas-free token transfers on the TRON network.
 * 
 * Key Features:
 * - Support for both mainnet and testnet
 * - Complete API coverage
 * - EIP-712 signature support
 * - Proper error handling
 * - Input validation
 * 
 * Technical Approach:
 * - Single-file class design for simplicity
 * - Strong typing with PHP 7.4+
 * - Comprehensive error handling
 * - Clean and minimal design
 * - Full PHPDoc documentation
 * 
 * Security Features:
 * - HMAC-SHA256 authentication
 * - Input validation
 * - Error handling
 * - Secure cURL configuration
 * 
 * Usage Example:
 * ```php
 * $sdk = new TronGasFreeSDK($apiKey, $apiSecret, false);
 * $tokens = $sdk->getAllTokens();
 * ```
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
    
    // API Response Codes
    private const RESPONSE_SUCCESS = 200;
    private const RESPONSE_BAD_REQUEST = 400;
    private const RESPONSE_SERVER_ERROR = 500;
    
    // Error Types from Documentation
    private const ERROR_TYPES = [
        'ProviderAddressNotMatchException',
        'DeadlineExceededException',
        'InvalidSignatureException',
        'UnsupportedTokenException',
        'TooManyPendingTransferException',
        'VersionNotSupportedException',
        'NonceNotMatchException',
        'MaxFeeExceededException',
        'InsufficientBalanceException'
    ];

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
        $this->validateAddress($accountAddress);
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
            'token',
            'serviceProvider',
            'user',
            'receiver',
            'value',
            'maxFee',
            'deadline',
            'version',
            'nonce',
            'sig'
        ];

        // Validate required parameters
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new Exception("Missing required parameter: {$param}");
            }
        }

        // Validate addresses
        $this->validateAddress($params['token']);
        $this->validateAddress($params['serviceProvider']);
        $this->validateAddress($params['user']);
        $this->validateAddress($params['receiver']);

        // Validate numeric values
        $this->validateNumericValue($params['value'], 'value');
        $this->validateNumericValue($params['maxFee'], 'maxFee');
        $this->validateNumericValue($params['deadline'], 'deadline');
        $this->validateNumericValue($params['version'], 'version');
        $this->validateNumericValue($params['nonce'], 'nonce');

        // Validate deadline
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

        // Handle specific API errors
        if ($httpCode !== self::RESPONSE_SUCCESS) {
            $errorType = $result['reason'] ?? 'UnknownError';
            $errorMessage = $result['message'] ?? 'Unknown error';
            
            if (in_array($errorType, self::ERROR_TYPES)) {
                throw new Exception("GasFree API Error: {$errorType} - {$errorMessage}");
            }
            
            throw new Exception(
                'API request failed: ' . $errorMessage . 
                ' (Code: ' . $result['code'] . ')'
            );
        }

        return $result['data'] ?? [];
    }

    /**
     * Validate a TRON address
     * @param string $address The address to validate
     * @throws Exception
     */
    private function validateAddress(string $address): void
    {
        if (empty($address)) {
            throw new Exception('Address cannot be empty');
        }
        
        // Basic TRON address format validation (starts with T and is 34 chars)
        if (!preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', $address)) {
            throw new Exception('Invalid TRON address format');
        }
    }

    /**
     * Validate a numeric value
     * @param mixed $value The value to validate
     * @param string $paramName The parameter name for error messages
     * @throws Exception
     */
    private function validateNumericValue($value, string $paramName): void
    {
        if (!is_numeric($value) || $value <= 0) {
            throw new Exception("{$paramName} must be a positive number");
        }
    }

    /**
     * Get chain ID based on network
     * @return int
     */
    public function getChainId(): int
    {
        return $this->isTestnet ? self::TESTNET_CHAIN_ID : self::MAINNET_CHAIN_ID;
    }

    /**
     * Get verifying contract address based on network
     * @return string
     */
    public function getVerifyingContract(): string
    {
        return $this->isTestnet ? self::TESTNET_CONTRACT : self::MAINNET_CONTRACT;
    }

    /**
     * Get domain for EIP-712 signing
     * @return array
     */
    public function getMessageDomain(): array
    {
        return [
            'name' => 'GasFreeController',
            'version' => self::API_VERSION,
            'chainId' => $this->getChainId(),
            'verifyingContract' => $this->getVerifyingContract()
        ];
    }

    /**
     * Get message types for EIP-712 signing
     * @return array
     */
    public function getMessageTypes(): array
    {
        return [
            'PermitTransfer' => [
                ['name' => 'token', 'type' => 'address'],
                ['name' => 'serviceProvider', 'type' => 'address'],
                ['name' => 'user', 'type' => 'address'],
                ['name' => 'receiver', 'type' => 'address'],
                ['name' => 'value', 'type' => 'uint256'],
                ['name' => 'maxFee', 'type' => 'uint256'],
                ['name' => 'deadline', 'type' => 'uint256'],
                ['name' => 'version', 'type' => 'uint256'],
                ['name' => 'nonce', 'type' => 'uint256']
            ]
        ];
    }
} 