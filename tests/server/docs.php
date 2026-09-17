<?php

declare(strict_types=1);

// Documentation server for DocsTest: realistic answers, route by route, for the examples of README.md and readme/**.
// `php -S 127.0.0.1:<port> tests/server/docs.php` — grows with every resource.

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = (string) parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/** @return array<string, mixed> */
function ticker(float $price, float $marketcap, int $rank, float $change): array
{
    return ['price' => $price, 'marketcap' => $marketcap, 'rank' => $rank, 'percentChange24h' => $change];
}

/** @return array<string, list<array{int, float}>> */
function history(float $current): array
{
    $now = 1_700_000_000;
    $series = static fn (int $points, int $step): array => array_map(static fn (int $i): array => [$now - ($points - 1 - $i) * $step, round($current * (0.9 + $i / $points / 10), 2)], range(0, $points - 1));

    return ['d' => $series(24, 3600), 'w' => $series(7, 86400), 'm' => $series(30, 86400), 'y' => $series(12, 2592000), 'all' => $series(24, 2592000)];
}

/** @return array<string, mixed> */
function asset(string $uuid, string $iso, string $label, string $contract, int $baseUnit, int $gasUnit, string $caip2, string $family, float $price, float $marketcap, int $rank, float $change): array
{
    return [
        'uuid' => $uuid, 'state' => 'AVAILABLE', 'iso' => $iso, 'label' => $label, 'contractAddress' => $contract,
        'baseUnit' => $baseUnit, 'gasUnit' => $gasUnit, 'logo' => null, 'data' => '{}',
        'fees' => ['low' => null, 'medium' => null, 'high' => null, 'computed' => ['gas' => (string) $gasUnit, 'native' => '0.000021']],
        'ticker' => ticker($price, $marketcap, $rank, $change), 'history' => history($price),
        'network' => ['uuid' => 'net-' . strtolower($iso), 'state' => 'ENABLED', 'caip2' => $caip2, 'label' => $label, 'gasBase' => 1, 'data' => '{}', 'type' => ['uuid' => 'type-' . $family, 'code' => $family, 'label' => $family, 'data' => '{}']],
    ];
}

$assets = [
    'btc' => asset('asset-btc', 'BTC', 'Bitcoin', '', 8, 250, 'bip122:000000000019d6689c085ae165831e93', 'UTXO', 61230.4, 1.2e12, 1, -1.2),
    'eth' => asset('asset-eth', 'ETH', 'Ethereum', '', 18, 21000, 'eip155:1', 'EVM', 2031.5, 2.44e11, 2, 0.8),
    'usdc' => asset('asset-usdc', 'USDC', 'USD Coin', '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48', 6, 65000, 'eip155:1', 'EVM', 0.92, 3.3e10, 6, 0.0),
    'sol' => asset('asset-sol', 'SOL', 'Solana', '', 9, 5000, 'solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp', 'SOLANA', 128.4, 5.6e10, 5, 2.1),
];

$answer = static function (int $status, mixed $body): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
};
$error = static function (int $status, string $code) use ($answer): void {
    $answer($status, ['error' => true, 'message' => $code, 'code' => $status]);
};
$lookup = static function (string $key) use ($assets): ?array {
    $key = strtolower($key);
    foreach ($assets as $iso => $asset) {
        if ($key === $iso || $key === $asset['uuid']) {
            return $asset;
        }
    }

    return null;
};

/** @return array<string, mixed> */
function identity(string $mode): array
{
    $form = $mode === 'KYB'
        ? ['activity' => 'software', 'submittedAt' => 1699000000, 'score' => 8]
        : ['european_residency' => true, 'ppe' => false, 'ppp' => false, 'source_income' => 'salary', 'net_income' => '30k-50k', 'experience' => 'beginner', 'submittedAt' => 1699000000, 'score' => 12];
    $steps = $mode === 'KYB' ? ['info', 'kbis', 'status', 'domiciliation', 'rbe'] : ['info', 'selfie', 'identity', 'residency'];

    return [
        'uuid' => 'identity-' . strtolower($mode), 'state' => 'VALIDATED', 'mode' => $mode, 'form' => $form,
        'data' => ['steps' => array_combine($steps, array_fill(0, count($steps), ['status' => 'VALIDATED', 'submittedAt' => 1699000000])), 'notifications' => true, 'verificationUrl' => null, 'hosted' => false],
        'validatedAt' => 1699003600, 'expiresAt' => 1730539600, 'renewalNotifiedAt' => null,
    ];
}

/** @return array<string, mixed> */
function customerAccount(): array
{
    return ['email' => 'jean@valjean.fr', 'firstname' => 'Jean', 'lastname' => 'Valjean', 'fin' => null, 'birthdate' => 315532800, 'phoneNumber' => 612345678, 'phoneZone' => '+33', 'address' => ['uuid' => 'address-1', 'state' => 'VALIDATED', 'address' => '1 rue de Paris, 75001 Paris'], 'referralCode' => 'JEAN42'];
}

/** @return array<string, mixed> */
function setup(): array
{
    return ['theme' => 'light', 'currency' => 'EUR', 'locale' => 'FR', 'choosenOrganization' => 'CUSTOMER', 'needActivation' => false, 'notify' => true, 'onboarding' => true];
}

$customer = [
    'uuid' => 'CUSTOMER_UUID', 'state' => 'ENABLED', 'isAvailable' => true, 'createdAt' => 1699000000, 'login' => 'jean@valjean.fr', 'canLogin' => true,
    'account' => customerAccount(), 'client' => ['roles' => ['ROLE_USER'], 'hasTfa' => false, 'hasPhishing' => false, 'isValid' => true],
    'action' => ['setup' => setup()], 'identity' => identity('KYC'), 'business' => [],
    'collaborations' => ['collaborator' => [['uuid' => 'collaboration-1', 'state' => 'ENABLED', 'roles' => ['ROLE_USER'], 'organization' => 'ACME', 'organizationUuid' => 'YOUR_SCOPE_UUID', 'manager' => 'MANAGER_UUID']], 'manager' => []],
    'alert' => [],
];
$account = ['uuid' => 'CUSTOMER_UUID', 'identity' => identity('KYC'), 'business' => [], 'account' => array_merge(customerAccount(), ['address' => ['uuid' => 'address-1', 'address' => '1 rue de Paris, 75001 Paris']]), 'notifications' => ['login' => true, 'newsletter' => false], 'setup' => setup()];
$bankAccount = ['uuid' => 'bank-1', 'message' => 'BTGN-4242', 'iban' => null, 'bank' => null, 'bic' => null, 'balance' => 150.0, 'history' => history(150.0), 'pending' => ['in' => 0, 'out' => 0]];
$operations = [
    ['txId' => 'op-1', 'amount' => 150.0, 'direction' => 'DEPOSIT', 'date' => 1701000000, 'info' => null],
    ['txId' => 'op-2', 'amount' => 25.0, 'direction' => 'PURCHASE', 'date' => 1701003600, 'info' => 'ETH'],
];

/**
 * @param array<string, mixed> $asset
 *
 * @return array<string, mixed>
 */
function wallet(string $uuid, array $asset, string $address, string $balance, ?string $tag = null): array
{
    return ['uuid' => $uuid, 'state' => 'CREATED', 'type' => 'USER', 'address' => $address, 'addressLegacy' => null, 'tag' => $tag, 'balance' => $balance, 'asset' => ['uuid' => $asset['uuid'], 'iso' => $asset['iso'], 'label' => $asset['label']]];
}

$wallets = ['eth' => wallet('wallet-eth', $assets['eth'], '0xabc0000000000000000000000000000000000001', '0.5'), 'btc' => wallet('wallet-btc', $assets['btc'], 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', '0.01')];
$portfolio = ['uuid' => 'custody-1', 'type' => 'USER', 'history' => history(1628.0)];

/**
 * @param array<string, mixed> $asset
 *
 * @return array<string, mixed>
 */
function order(string $uuid, string $side, string $state, string $amount, ?string $reference, ?float $received, ?float $price, ?float $fee, ?int $completedAt, array $asset): array
{
    return [
        'uuid' => $uuid, 'state' => $state, 'side' => $side, 'amount' => $amount, 'reference' => $reference, 'received' => $received, 'executedPrice' => $price, 'fee' => $fee,
        'completedAt' => $completedAt, 'createdAt' => 1701000000, 'user' => ['uuid' => 'CUSTOMER_UUID', 'login' => 'jean@valjean.fr'], 'organization' => ['uuid' => 'YOUR_SCOPE_UUID', 'name' => 'ACME'],
        'asset' => ['uuid' => $asset['uuid'], 'iso' => $asset['iso'], 'label' => $asset['label']],
    ];
}

$orders = [
    'ORDER_UUID' => order('ORDER_UUID', 'BUY', 'DONE', '25.00', 'order-42', 0.0123, 2031.5, 0.25, 1701003600, $assets['eth']),
    'order-2' => order('order-2', 'SELL', 'DONE', '0.01', null, 20.06, 2031.5, 0.25, 1701007200, $assets['eth']),
];

/**
 * @param array<string, mixed> $asset
 *
 * @return array<string, mixed>
 */
function assetRef(array $asset): array
{
    return ['uuid' => $asset['uuid'], 'iso' => $asset['iso'], 'label' => $asset['label']];
}

$owner = ['uuid' => 'CUSTOMER_UUID', 'state' => 'ENABLED', 'login' => 'jean@valjean.fr', 'account' => ['firstname' => 'Jean', 'lastname' => 'Valjean', 'fin' => null]];
$organization = ['uuid' => 'YOUR_SCOPE_UUID', 'state' => 'ENABLED', 'name' => 'ACME', 'hub' => null];

/**
 * @param array<string, mixed> $owner
 * @param array<string, mixed> $organization
 *
 * @return array<string, mixed>
 */
function transaction(string $uuid, string $state, string $source, string $direction, string $asset, float $amount, ?float $eurValue, ?string $reference, bool $credited, array $owner, array $organization): array
{
    return [
        'uuid' => $uuid, 'state' => $state, 'source' => $source, 'direction' => $direction, 'asset' => $asset, 'amount' => $amount, 'eurValue' => $eurValue, 'reference' => $reference,
        'credited' => $credited, 'silent' => false, 'data' => [], 'createdAt' => 1701000000, 'updatedAt' => 1701003600,
        'owner' => $owner, 'assignee' => null, 'organization' => $organization, 'alert' => null,
    ];
}

$transactions = [
    'transaction-1' => transaction('transaction-1', 'PENDING', 'BANK', 'OUT', 'EUR', 50.0, null, null, false, $owner, $organization),
    'transaction-2' => transaction('transaction-2', 'PENDING', 'CUSTODY', 'OUT', 'ETH', 0.5, 1015.75, 'withdraw-42', false, $owner, $organization),
    'transaction-3' => transaction('transaction-3', 'COMPLETED', 'BANK', 'IN', 'EUR', 150.0, null, 'BANK-TRANSFER-REF-42', true, $owner, $organization),
];

/**
 * @param array<string, mixed>|null $asset
 * @param list<array{string, string, string, mixed}> $fields
 *
 * @return array<string, mixed>
 */
function core(string $uuid, string $name, string $label, string $type, ?array $asset, array $fields, string $state = 'ENABLED'): array
{
    $config = [];
    foreach ($fields as [$fieldName, $fr, $en, $value]) {
        $config[] = ['name' => $fieldName, 'label' => ['fr' => $fr, 'en' => $en], 'data' => ['type' => is_bool($value) ? 'bool' : 'string', 'value' => $value]];
    }

    return ['uuid' => $uuid, 'state' => $state, 'name' => $name, 'label' => $label, 'type' => $type, 'asset' => $asset === null ? null : assetRef($asset), 'config' => $config];
}

$stakingFields = static fn (string $connector, string $apr, string $minDeposit): array => [
    ['connector', 'Connecteur', 'Connector', $connector], ['apr', 'Taux annuel', 'Annual rate', $apr], ['min_deposit', 'Dépôt minimum', 'Minimum deposit', $minDeposit],
    ['deposit_locked_period', 'Blocage du dépôt', 'Deposit lock-up', 'D@3'], ['rewards_locked_period', 'Blocage des revenus', 'Rewards lock-up', 'D@7'], ['unstake_locked_period', 'Blocage de sortie', 'Unstake lock-up', 'D@21'],
    ['can_choose_withdrawal', 'Retrait partiel', 'Partial withdrawal', true], ['can_choose_rewards', 'Revenus partiels', 'Partial rewards', true], ['min_rewards_eur', 'Revenus minimum (EUR)', 'Minimum rewards (EUR)', '5'],
];
$cores = [
    'CORE_UUID' => core('CORE_UUID', 'figment_sol', 'Figment SOL', 'STAKING', $assets['sol'], $stakingFields('figment', '6.5', '1')),
    'core-bitgen-eth' => core('core-bitgen-eth', 'bitgen_eth', 'BITGEN ETH', 'STAKING', $assets['eth'], $stakingFields('bitgen', '3.2', '0.1')),
    'core-bank' => core('core-bank', 'manual_bank', 'Manual bank', 'RAMP', null, [['iban', 'IBAN', 'IBAN', 'FR76…']]),
    'core-exchange' => core('core-exchange', 'exchange', 'Exchange', 'TRADING', null, [['api_key', 'Clé API', 'API key', '']]),
    'core-custodian' => core('core-custodian', 'custodian', 'Custodian', 'CUSTODY', null, [['vault', 'Coffre', 'Vault', 'main']]),
];

$movement = [
    'uuid' => 'MOVEMENT_UUID', 'state' => 'COMPLETED', 'kind' => 'STAKE', 'provider' => 'figment_sol', 'amount' => '2', 'createdAt' => 1701000000, 'updatedAt' => 1701003600,
    'staking' => ['uuid' => 'POSITION_UUID', 'state' => 'ENABLED', 'amount' => '2', 'error' => null, 'data' => ['rewards' => '0.0123', 'lastRewardAt' => 1701090000], 'createdAt' => 1701000000, 'updatedAt' => 1701090000, 'core' => ['uuid' => 'CORE_UUID', 'name' => 'figment_sol', 'label' => 'Figment SOL']],
    'owner' => $owner, 'asset' => assetRef($assets['sol']), 'organization' => ['uuid' => 'YOUR_SCOPE_UUID', 'state' => 'ENABLED', 'name' => 'ACME'],
];
$stakingOperations = [
    ['txId' => 'stk-op-1', 'movement' => 'MOVEMENT_UUID', 'asset' => 'SOL', 'kind' => 'STAKE', 'amount' => '2', 'price' => 128.4, 'value' => 256.8, 'event' => 'validated', 'provider' => 'figment_sol', 'date' => 1701003600],
    ['txId' => 'stk-op-2', 'movement' => null, 'asset' => 'SOL', 'kind' => 'REWARD', 'amount' => '0.0123', 'price' => 130.0, 'value' => 1.6, 'event' => 'reward', 'provider' => 'figment_sol', 'date' => 1701090000],
];
$stakingPortfolio = ['uuid' => 'staking-1', 'balances' => ['capital' => 256.8, 'revenues' => 1.6], 'histories' => ['capital' => history(256.8), 'revenues' => history(1.6)]];

/** @return array<string, mixed> */
function webhookType(string $uuid, string $name, string $fr, string $en, string $state = 'ENABLED'): array
{
    return ['uuid' => $uuid, 'state' => $state, 'name' => $name, 'label' => json_encode(['fr' => $fr, 'en' => $en], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'data' => '{}'];
}

$webhookTypes = [
    'WEBHOOK_UUID' => webhookType('WEBHOOK_UUID', 'custody.sent', 'Crypto envoyée', 'Crypto sent'),
    'webhook-2' => webhookType('webhook-2', 'bank.credited', 'Compte crédité', 'Account credited'),
    'webhook-3' => webhookType('webhook-3', 'trading.buy', 'Achat', 'Purchase'),
    'webhook-4' => webhookType('webhook-4', 'user.created', 'Client créé', 'Customer created'),
    'webhook-5' => webhookType('webhook-5', 'organization.created', 'Organisation créée', 'Organization created', 'ARCHIVED'),
];
$subscriptions = [
    'secret' => 'whsec_9f2c6b1e4d8a7c3b5e0f1a2d4c6b8e9a', 'endpoint' => 'https://example.com/bitgen',
    'items' => [
        ['uuid' => 'SUBSCRIBER_UUID', 'state' => 'ENABLED', 'updatedAt' => 1701000000, 'webhook' => $webhookTypes['WEBHOOK_UUID']],
        ['uuid' => 'subscriber-2', 'state' => 'ARCHIVED', 'updatedAt' => 1700000000, 'webhook' => $webhookTypes['webhook-5']],
    ],
];
$deliveries = [
    ['date' => 1701000000, 'webhook' => 'custody.sent', 'url' => 'https://example.com/bitgen', 'status' => 'SENT', 'http_code' => 204, 'duration_ms' => 87, 'attempts' => 1, 'payload' => ['delivery_id' => 'delivery-1', 'timestamp' => 1701000000, 'event' => 'custody.sent', 'data' => ['wallet' => 'wallet-eth', 'amount' => '0.5']], 'error' => null],
    ['date' => 1701003600, 'webhook' => 'custody.sent', 'url' => 'https://example.com/bitgen', 'status' => 'FAILED', 'http_code' => null, 'duration_ms' => null, 'attempts' => 1, 'payload' => ['delivery_id' => 'delivery-2', 'timestamp' => 1701003600, 'event' => 'custody.sent', 'data' => []], 'error' => 'connection refused'],
];

$apikeys = [
    'APIKEY_UUID' => [
        'uuid' => 'APIKEY_UUID', 'state' => 'ENABLED', 'name' => 'backend', 'permissions' => ['customer.read', 'customer.write', 'bank.read', 'custody.read', 'custody.write', 'trading.read', 'trading.write'], 'expireAt' => 1735689600, 'createdAt' => 1699000000,
        'organization' => ['uuid' => 'YOUR_SCOPE_UUID', 'state' => 'ENABLED', 'name' => 'ACME', 'hub' => null, 'owner' => ['uuid' => 'owner-1', 'login' => 'ceo@acme.fr', 'firstname' => 'Anne', 'lastname' => 'Martin']],
    ],
];
$apikeyLogs = [
    ['date' => 1701000000, 'path' => 'GET /custody/CUSTOMER_UUID', 'payload' => '{"user":"CUSTOMER_UUID"}', 'status' => 200, 'error' => null],
    ['date' => 1701000060, 'path' => 'PUT /bank/CUSTOMER_UUID', 'payload' => '{"amount":"50.00","iban":"****"}', 'status' => 416, 'error' => '{"error":true,"message":"requested_amount_error","code":416}'],
];

$segments = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));

if ($method === 'POST' && $segments === ['customer']) {
    $answer(201, ['uuid' => 'CUSTOMER_UUID']);
} elseif ($method === 'GET' && $segments === ['customer']) {
    $answer(200, ['count' => 1, 'items' => [$customer]]);
} elseif (count($segments) === 2 && $segments[0] === 'account') {
    $method === 'PUT' ? $answer(200, []) : $answer(200, $account);
} elseif ($method === 'POST' && $segments === ['bank']) {
    $answer(201, ['uuid' => 'deposit-1']);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'bank') {
    $answer(200, $bankAccount);
} elseif ($method === 'PUT' && count($segments) === 2 && $segments[0] === 'bank') {
    $answer(200, ['transaction' => 'transaction-1']);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'bank' && $segments[2] === 'operations') {
    $answer(200, ['count' => count($operations), 'items' => $operations]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'custody') {
    $answer(200, array_values($wallets));
} elseif ($method === 'PUT' && count($segments) === 2 && $segments[0] === 'custody') {
    $answer(200, ['transaction' => 'transaction-2']);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'custody' && $segments[2] === 'portfolio') {
    $answer(200, $portfolio);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'custody') {
    $asset = $lookup(rawurldecode($segments[2]));
    if ($asset === null) {
        $error(404, 'unknown_asset');
    } else {
        $iso = strtolower(is_string($asset['iso']) ? $asset['iso'] : '');
        $answer(200, ($wallets[$iso] ?? wallet('wallet-' . $iso, $asset, 'address-' . $iso, '0')) + ['history' => history(1015.75)]);
    }
} elseif ($method === 'POST' && $segments === ['trading']) {
    $answer(201, ['tunnel' => 'ORDER_UUID', 'state' => 'REGISTERED']);
} elseif ($method === 'GET' && $segments === ['trading', 'orders']) {
    $answer(200, ['count' => count($orders), 'items' => array_values($orders)]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'trading') {
    $order = $orders[rawurldecode($segments[1])] ?? null;
    if ($order === null) {
        $error(404, 'unknown_order');
    } else {
        $answer(200, $order);
    }
} elseif ($method === 'GET' && $segments === ['transaction']) {
    $answer(200, ['count' => count($transactions), 'items' => array_values($transactions)]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'transaction') {
    $key = rawurldecode($segments[1]);
    $found = $transactions[$key] ?? null;
    foreach ($transactions as $transaction) {
        if ($transaction['reference'] === $key) {
            $found = $transaction;
        }
    }
    $found === null ? $error(404, 'unknown_transaction') : $answer(200, $found);
} elseif ($method === 'GET' && $segments === ['applications', 'core']) {
    $query = [];
    parse_str((string) parse_url(is_string($uri) ? $uri : '/', PHP_URL_QUERY), $query);
    $wanted = isset($query['asset']) && is_string($query['asset']) ? $lookup($query['asset']) : null;
    if (isset($query['asset']) && $wanted === null) {
        $error(404, 'unknown_asset');
    } else {
        $items = array_values(array_filter($cores, static fn (array $core): bool => (!isset($query['type']) || $core['type'] === $query['type'])
            && (!isset($query['state']) || $core['state'] === $query['state'])
            && ($wanted === null || (is_array($core['asset']) ? $core['asset']['uuid'] : null) === $wanted['uuid'])));
        $answer(200, ['count' => count($items), 'items' => $items]);
    }
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'applications' && $segments[1] === 'core') {
    $core = $cores[rawurldecode($segments[2])] ?? null;
    $core === null ? $error(404, 'unknown_core') : $answer(200, $core);
} elseif ($method === 'POST' && $segments === ['staking']) {
    $answer(201, ['uuid' => 'MOVEMENT_UUID']);
} elseif ($method === 'GET' && $segments === ['staking']) {
    $answer(200, ['count' => 1, 'items' => [$movement]]);
} elseif ($method === 'GET' && $segments === ['staking', 'movements']) {
    $answer(200, ['count' => 0, 'items' => []]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'staking') {
    rawurldecode($segments[1]) === 'MOVEMENT_UUID' ? $answer(200, $movement) : $error(404, 'unknown_staking_movement');
} elseif ($method === 'PUT' && count($segments) === 3 && $segments[0] === 'staking' && in_array($segments[2], ['rewards', 'unstake'], true)) {
    $answer(200, []);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'staking' && $segments[2] === 'operations') {
    $answer(200, ['count' => count($stakingOperations), 'items' => $stakingOperations]);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'staking' && $segments[2] === 'portfolio') {
    $answer(200, $stakingPortfolio);
} elseif ($method === 'POST' && count($segments) === 4 && $segments[0] === 'webhook' && $segments[1] === 'security' && $segments[3] === 'activate') {
    $answer(201, []);
} elseif ($method === 'PATCH' && count($segments) === 3 && $segments[0] === 'webhook' && $segments[1] === 'security') {
    $answer(200, []);
} elseif ($method === 'PATCH' && count($segments) === 4 && $segments[0] === 'webhook' && $segments[1] === 'security' && $segments[3] === 'regenerate') {
    $answer(200, []);
} elseif ($method === 'GET' && $segments === ['webhook']) {
    $answer(200, ['count' => count($webhookTypes), 'items' => array_values($webhookTypes)]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'webhook') {
    $type = $webhookTypes[rawurldecode($segments[1])] ?? null;
    $type === null ? $error(404, 'unknown_webhook') : $answer(200, $type);
} elseif ($method === 'POST' && $segments === ['webhooks']) {
    $answer(201, ['uuid' => 'SUBSCRIBER_UUID']);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'webhooks') {
    $answer(200, $subscriptions);
} elseif ($method === 'DELETE' && count($segments) === 2 && $segments[0] === 'webhooks') {
    $answer(200, []);
} elseif ($method === 'POST' && count($segments) === 2 && $segments[0] === 'webhooks') {
    $answer(201, []);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'webhooks' && $segments[2] === 'logs') {
    $answer(200, ['count' => count($deliveries), 'items' => $deliveries]);
} elseif ($method === 'GET' && count($segments) === 3 && $segments[0] === 'organization' && $segments[2] === 'apikeys') {
    $answer(200, ['count' => count($apikeys), 'items' => array_values($apikeys)]);
} elseif ($method === 'GET' && count($segments) === 4 && $segments[0] === 'organization' && $segments[2] === 'apikeys') {
    $key = $apikeys[rawurldecode($segments[3])] ?? null;
    $key === null ? $error(404, 'unknown_apikey') : $answer(200, $key);
} elseif ($method === 'GET' && count($segments) === 5 && $segments[0] === 'organization' && $segments[2] === 'apikeys' && $segments[4] === 'logs') {
    $answer(200, ['count' => count($apikeyLogs), 'items' => $apikeyLogs]);
} elseif ($method === 'GET' && $segments === ['asset']) {
    $answer(200, ['count' => count($assets), 'items' => array_values($assets)]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'asset') {
    $asset = $lookup(rawurldecode($segments[1]));
    if ($asset === null) {
        $error(404, 'unknown_asset');
    } else {
        $answer(200, $asset);
    }
} elseif ($method === 'GET' && $segments === ['ticker']) {
    $answer(200, ['count' => count($assets), 'items' => array_map(static fn (array $a): array => ['iso' => $a['iso'], 'ticker' => $a['ticker']], array_values($assets))]);
} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'ticker') {
    $asset = $lookup(rawurldecode($segments[1]));
    if ($asset === null) {
        $error(404, 'unknown_asset');
    } else {
        $answer(200, ['iso' => $asset['iso'], 'ticker' => $asset['ticker'], 'history' => $asset['history']]);
    }
} else {
    http_response_code(500);
    echo 'Internal Server Error';
}
