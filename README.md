# tron-gasfree-sdk-php

A simple PHP SDK for the GasFree service, enabling gas-free TRC20 token transfers on the TRON network.

This SDK is provided by [anonwins](https://github.com/anonwins/), while the definition & maintenance of the APIs are managed by the official GasFree project. For more information, visit [gasfree.io](https://gasfree.io).

## Disclaimer

This is an unofficial SDK for the GasFree service. It is not affiliated with, maintained, authorized, or endorsed by the official GasFree project.

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

Here's a simple example of how to perform a gas-free USDT transfer:

```php
use TronGasFreeSDK;

// Initialize the SDK
$sdk = new TronGasFreeSDK(
    'your_api_key_here',
    'your_api_secret_here',
    false // Set to true for testnet
);

try {
    // Step 1: Get USDT token address
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

    // Step 2: Get service provider
    $providers = $sdk->getAllProviders();
    if (empty($providers)) {
        throw new Exception('No service providers available');
    }
    $provider = $providers[0];

    // Step 3: Check account status
    $senderAddress = 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U';
    $accountInfo = $sdk->getAccountInfo($senderAddress);
    if (!$accountInfo['isActive']) {
        throw new Exception('Account is not active for gas-free transfers');
    }

    // Step 4: Prepare transfer parameters
    $transferParams = [
        'token' => $usdtToken['address'],
        'serviceProvider' => $provider['address'],
        'user' => $senderAddress,
        'receiver' => 'TFFAMQLZybALaLb4uxHA9RBE7pxhUAjF3U',
        'value' => '1000000', // Amount in smallest unit (6 decimals for USDT)
        'maxFee' => '100000', // Maximum fee in TRX (0.1 TRX)
        'deadline' => time() + 300, // 5 minutes from now
        'version' => 1,
        'nonce' => $accountInfo['nonce'],
        'sig' => '0x...' // Your signature here
    ];

    // Step 5: Submit transfer
    $transfer = $sdk->submitTransfer($transferParams);
    $traceId = $transfer['traceId'];

    // Step 6: Monitor transfer status
    $maxAttempts = 10;
    $attempt = 0;
    while ($attempt < $maxAttempts) {
        $transferDetails = $sdk->getTransferDetails($traceId);
        
        if ($transferDetails['status'] === 'SUCCESS') {
            echo "Transfer successful! Transaction hash: " . $transferDetails['txHash'];
            break;
        } elseif ($transferDetails['status'] === 'FAILED') {
            throw new Exception('Transfer failed: ' . $transferDetails['error']);
        }
        
        sleep(3);
        $attempt++;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## API Reference

### Constructor

```php
public function __construct(string $apiKey, string $apiSecret, bool $isTestnet = false)
```

### Methods

#### getAllTokens()
Get all supported tokens.

#### getAllProviders()
Get all supported service providers.

#### getAccountInfo(string $accountAddress)
Get GasFree account information.

#### submitTransfer(array $params)
Submit a GasFree transfer authorization.

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
- `sig`: Your signature

#### getTransferDetails(string $traceId)
Get transfer authorization details.

## License

Apache-2.0 license

## Support

For support:
- For SDK-specific issues, please open an issue on [GitHub](https://github.com/anonwins/tron-gasfree-sdk-php/issues)
- For API-related questions, please refer to the [GasFree Developer Documentation](https://gasfree.io/docs/GasFree_specification.html) 
