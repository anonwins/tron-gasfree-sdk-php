# tron-gasfree-sdk-php

tron-gasfree-sdk-php is a toolkit based on the GasFree API specification. It facilitates the integration of the non-gas TRC20 token transfer service for the PHP programming language.

This SDK is provided by [anonwins](https://github.com/anonwins/), while the definition & maintenance of the APIs are managed by the official GasFree project. For more information, visit [gasfree.io](https://gasfree.io).

## Disclaimer

This is an unofficial SDK for the GasFree service. It is not affiliated with, maintained, authorized, or endorsed by the official GasFree project. While we strive to maintain compatibility with the official API, please refer to the [official documentation](https://gasfree.io/docs/GasFree_specification.html) for the most up-to-date API specifications.

## Features

- Support for both mainnet and testnet
- Complete API coverage
- EIP-712 signature support
- Proper error handling
- Input validation
- Strong typing with PHP 7.4+

## Requirements

- PHP 7.4 or higher
- cURL extension
- OpenSSL extension
- JSON extension

## Installation

1. Download the `TronGasFreeSDK.php` file
2. Include it in your project:

```php
require_once 'TronGasFreeSDK.php';
```

## Quick Start

Here's a complete example of how to perform a gas-free USDT transfer:

```php
use TronGasFreeSDK;

// Initialize the SDK with your API credentials
$sdk = new TronGasFreeSDK(
    'your_api_key_here',
    'your_api_secret_here',
    false // Set to true for testnet
);

try {
    // Step 1: Get supported tokens to find USDT contract address
    $tokens = $sdk->getAllTokens();
    $usdtToken = null;
    foreach ($tokens as $token) {
        if ($token['symbol'] === 'USDT') {
            $usdtToken = $token;
            break;
        }
    }
    if (!$usdtToken) {
        throw new Exception('USDT token not found');
    }

    // Step 2: Get service providers
    $providers = $sdk->getAllProviders();
    if (empty($providers)) {
        throw new Exception('No service providers available');
    }
    // Use the first available provider
    $provider = $providers[0];

    // Step 3: Check account balance and status
    $senderAddress = 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U'; // Your sender address
    $accountInfo = $sdk->getAccountInfo($senderAddress);
    if (!$accountInfo['isActive']) {
        throw new Exception('Account is not active for gas-free transfers');
    }

    // Step 4: Prepare transfer parameters
    $transferParams = [
        'token' => $usdtToken['address'], // USDT contract address
        'serviceProvider' => $provider['address'],
        'user' => $senderAddress,
        'receiver' => 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U', // Recipient address
        'value' => '1000000', // Amount in smallest unit (6 decimals for USDT)
        'maxFee' => '100000', // Maximum fee in TRX (0.1 TRX)
        'deadline' => time() + 300, // 5 minutes from now
        'version' => 1,
        'nonce' => $accountInfo['nonce'], // Use current nonce from account info
        'sig' => '0x...' // Your EIP-712 signature (implementation depends on your signing method)
    ];

    // Step 5: Submit the transfer
    $transfer = $sdk->submitTransfer($transferParams);
    $traceId = $transfer['traceId'];

    // Step 6: Monitor transfer status
    $maxAttempts = 10;
    $attempt = 0;
    $transferDetails = null;

    while ($attempt < $maxAttempts) {
        $transferDetails = $sdk->getTransferDetails($traceId);
        
        if ($transferDetails['status'] === 'SUCCESS') {
            echo "Transfer successful! Transaction hash: " . $transferDetails['txHash'];
            break;
        } elseif ($transferDetails['status'] === 'FAILED') {
            throw new Exception('Transfer failed: ' . $transferDetails['error']);
        }
        
        // Wait 3 seconds before next check
        sleep(3);
        $attempt++;
    }

    if (!$transferDetails || $transferDetails['status'] !== 'SUCCESS') {
        throw new Exception('Transfer status check timed out');
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

This example demonstrates:
1. Finding the USDT token contract address
2. Selecting a service provider
3. Checking account status
4. Preparing transfer parameters
5. Submitting the transfer
6. Monitoring the transfer status

Key points to note:
- Replace `your_api_key_here` and `your_api_secret_here` with your actual GasFree API credentials
- Replace the sender and receiver addresses with actual TRON addresses
- Adjust the `value` parameter based on your transfer amount (remember USDT has 6 decimals)
- Implement proper EIP-712 signing for the `sig` parameter
- The `maxFee` is in TRX (1 TRX = 1,000,000 SUN)
- The `deadline` should be set to a reasonable future time (5-15 minutes recommended)

## API Reference

### Constructor

```php
public function __construct(string $apiKey, string $apiSecret, bool $isTestnet = false)
```

- `$apiKey`: Your GasFree API key
- `$apiSecret`: Your GasFree API secret
- `$isTestnet`: Whether to use testnet (default: false)

### Methods

#### getAllTokens()

Get all supported tokens.

```php
public function getAllTokens(): array
```

Returns an array of supported tokens with their details.

#### getAllProviders()

Get all supported service providers.

```php
public function getAllProviders(): array
```

Returns an array of supported providers with their details.

#### getAccountInfo()

Get GasFree account information.

```php
public function getAccountInfo(string $accountAddress): array
```

Parameters:
- `$accountAddress`: User's EOA address

Returns account details including status, balance, and nonce.

#### submitTransfer()

Submit a GasFree transfer authorization.

```php
public function submitTransfer(array $params): array
```

Required parameters:
- `token`: Token contract address
- `serviceProvider`: Service provider address
- `user`: Sender's address
- `receiver`: Recipient's address
- `value`: Amount to transfer
- `maxFee`: Maximum fee limit
- `deadline`: Unix timestamp for transfer expiration
- `version`: Transfer version (currently 1)
- `nonce`: Unique number for this transfer
- `sig`: EIP-712 signature

#### getTransferDetails()

Get transfer authorization details.

```php
public function getTransferDetails(string $traceId): array
```

Parameters:
- `$traceId`: The trace ID of the transfer authorization

Returns transfer details including status and transaction info.

### Helper Methods

#### getChainId()

Get the chain ID for the current network.

```php
public function getChainId(): int
```

#### getVerifyingContract()

Get the verifying contract address for the current network.

```php
public function getVerifyingContract(): string
```

#### getMessageDomain()

Get the domain for EIP-712 signing.

```php
public function getMessageDomain(): array
```

#### getMessageTypes()

Get the message types for EIP-712 signing.

```php
public function getMessageTypes(): array
```

## Error Handling

The SDK throws exceptions for various error conditions:

- Invalid addresses
- Missing required parameters
- API errors
- Network errors
- Invalid numeric values

Example error handling:

```php
try {
    $result = $sdk->getAccountInfo('invalid_address');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Security

- All API requests are authenticated using HMAC-SHA256
- Input validation for all parameters
- Secure cURL configuration
- No sensitive data logging

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

Apache-2.0 license

## Support

For support:
- For SDK-specific issues, please open an issue on [GitHub](https://github.com/anonwins/tron-gasfree-sdk-php/issues)
- For API-related questions, please refer to the [GasFree Developer Documentation](https://gasfree.io/docs/GasFree_specification.html) 
