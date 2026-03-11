# bitgen/sdk

Official PHP SDK for the [BITGEN](https://bitgen.com) Crypto-as-a-Service API.

## Requirements

- PHP 8.2+
- Guzzle 7.x (`guzzlehttp/guzzle`)

## Installation

```bash
composer require bitgen/sdk
```

## Quick start

```php
use Bitgen\Sdk\BitgenClient;

$client = new BitgenClient([
    'scope'  => 'YOUR_SCOPE_UUID',
    'apiKey' => 'YOUR_API_KEY',
    'env'    => 'sandbox', // 'sandbox' | 'production'
]);
```

## Usage

### Assets

```php
$asset  = $client->asset->get('BTC');
echo $asset->name;  // Bitcoin
echo $asset->price; // 57731.19

$assets = $client->asset->list(); // Asset[]
```

### Bank

```php
$account = $client->bank->get($userUuid);
echo $account->amount; // 470.13

$result = $client->bank->withdraw($userUuid, 50);
echo $result->amount->finalWithdrawnAmount; // 47

$ops = $client->bank->operations($userUuid, [
    'direction' => 'WITHDRAWAL',
    'from'      => 1770748202,
    'to'        => 1772399995,
]);
```

### Orders

```php
// Buy — amount in EUR
$order = $client->order->create([
    'user'   => $userUuid,
    'asset'  => 'ETH',
    'amount' => 25,
    'mode'   => 'BUY',
]);
echo $order->tunnel; // "a8a6787e-..."
echo $order->state;  // "REGISTERED"

// Sell — amount in asset units
$order = $client->order->create([
    'user'   => $userUuid,
    'asset'  => 'BTC',
    'amount' => 0.0003601,
    'mode'   => 'SELL',
]);

// Poll status
$order = $client->order->get($tunnel);
echo $order->state;                    // "PAID"
echo $order->data->deliveredAmount;    // 0.01419787

$history = $client->order->history($userUuid); // Order[]
```

### Transactions (withdrawals)

```php
use Bitgen\Sdk\Exception\BitgenException;

try {
    $tx = $client->transaction->create([
        'user'          => $userUuid,
        'asset'         => 'BTC',
        'amount'        => 0.0003,
        'targetAddress' => 'tb1qrpd5g02e04lven83f46aeqy52wfqn8kk9h5kts',
        // 'targetTag'  => '...',  // optional — required for some assets (e.g. XRP)
    ]);
    echo $tx->txId;  // "761860cb-..."
    echo $tx->state; // "INITIALIZED"

} catch (BitgenException $e) {
    echo $e->code;       // 406
    echo $e->apiMessage; // "insufficient_funds"
}

$tx      = $client->transaction->get($txId);
$history = $client->transaction->history($userUuid, ['asset' => 'BTC']);
```

### Users

```php
$created = $client->user->create([
    'email'     => 'jane.doe@example.com',
    'iban'      => 'FR76...',
    'firstname' => 'Jane',
    'lastname'  => 'Doe',
]);
echo $created->uuid;

$list   = $client->user->list(['offset' => 0, 'limit' => 100]);
echo $list->meta->total;

$detail = $client->user->get($userUuid);
echo $detail->user->account->email;
echo $detail->bank->amount;

$client->user->update($userUuid, ['email' => 'new@example.com']);
$client->user->disable($userUuid);
```

### Wallets

```php
$wallets = $client->wallet->list($userUuid); // Wallet[]
$wallet  = $client->wallet->get($userUuid, 'BTC');
echo $wallet->balance;         // 0.0015849
echo $wallet->asset->baseUnit; // 100000000
```

### Stats

```php
$health = $client->stats->health();
echo $health->version; // "3.1"

$stats = $client->stats->quotas();
echo $stats->users->remain; // 989
```

## Error handling

| Exception            | When                                               | Properties                                            |
|----------------------|----------------------------------------------------|-------------------------------------------------------|
| `BitgenException`    | Any standard API error (JSON body with `error` key) | `status`, `code`, `service`, `module`, `apiMessage`   |

```php
use Bitgen\Sdk\Exception\BitgenException;

try {
    $order = $client->order->create([...]);
} catch (BitgenException $e) {
    // $e->service    → "order"
    // $e->module     → "create"
    // $e->code       → 416
    // $e->apiMessage → "requested_amount_error"
    // $e->status     → HTTP status code
}
```

## License

MIT
