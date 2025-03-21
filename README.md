# tron-gasfree-sdk-php

tron-gasfree-sdk-php is a toolkit based on the GasFree API specification. It facilitates the integration of the non-gas TRC20 token transfer service for the PHP programming language.

This SDK is provided by [anonwins](https://github.com/anonwins/), while the definition & maintenance of the APIs are managed by the official GasFree project. For more information, visit [gasfree.io](https://gasfree.io).

## Disclaimer

This is an unofficial SDK for the GasFree service. It is not affiliated with, maintained, authorized, or endorsed by the official GasFree project. While we strive to maintain compatibility with the official API, please refer to the [official documentation](https://docs.gasfree.io/) for the most up-to-date API specifications.

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

1. Download the `GasFreeSDK.php` file
2. Include it in your project:

```php
require_once 'GasFreeSDK.php';
```

## Quick Start

```php
use GasFreeSDK;

// Initialize the SDK
$sdk = new GasFreeSDK(
    'your_api_key_here',
    'your_api_secret_here',
    false // Set to true for testnet
);

try {
    // Get all supported tokens
    $tokens = $sdk->getAllTokens();
    
    // Get all service providers
    $providers = $sdk->getAllProviders();
    
    // Get account information
    $accountInfo = $sdk->getAccountInfo('TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U');
    
    // Submit a transfer
    $transfer = $sdk->submitTransfer([
        'token' => 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U',
        'serviceProvider' => 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U',
        'user' => 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U',
        'receiver' => 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U',
        'value' => '1000000',
        'maxFee' => '100000',
        'deadline' => time() + 300, // 5 minutes from now
        'version' => 1,
        'nonce' => 0,
        'sig' => '0x...' // Your EIP-712 signature
    ]);
    
    // Get transfer details
    $details = $sdk->getTransferDetails('your_trace_id');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

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

MIT License

## Support

For support:
- For SDK-specific issues, please open an issue on [GitHub](https://github.com/anonwins/tron-gasfree-sdk-php/issues)
- For API-related questions, please refer to the [GasFree Developer Documentation](https://docs.gasfree.io/) 
