<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Cast;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\Locale;
use Bitgen\Sdk\Model\OrderUser;
use Bitgen\Sdk\Model\OrganizationCategory;
use Bitgen\Sdk\Model\UserSummary;
use Bitgen\Sdk\Page;
use Bitgen\Sdk\Support\Enum;
use Bitgen\Sdk\Support\Path;
use Bitgen\Sdk\Support\UserId;
use InvalidArgumentException;

/**
 * `/customer`, `/account` — the customers of the organization (`$client->customer`).
 *
 * @phpstan-import-type UserRef from UserId
 */
class CustomerResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Create a customer of the key's organization — or attach an existing, KYC-validated account to it.
     * By default an activation email is sent: the customer stays `CREATED` and invisible to the financial
     * routes (bank, custody, trading, staking) until they activate (`$needActivation: false` skips it).
     *
     * @param string             $email          login of the customer
     * @param string             $manager        uuid of the collaborator of the organization who follows the customer
     * @param string|null        $fin            tax identification number, 100 characters max
     * @param bool|null          $needActivation default true: activation email, account `CREATED` until activated — false: usable right away, no email
     * @param bool|null          $notify         default true: the customer receives BITGEN's emails (newsletter) — false: none
     * @param string|null        $locale         `FR` (default) or `EN` — a `Locale` constant
     * @param string|null        $organization   category: `CUSTOMER` (default) or `B2B` (also opens a KYB file) — an `OrganizationCategory` constant; `BUSINESS` is reserved to platform administrators
     *
     * @throws InvalidArgumentException `$organization` is not an `OrganizationCategory` (`BUSINESS` included), or `$locale` is not `FR` or `EN`
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     */
    public function create(
        string $email,
        string $manager,
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $fin = null,
        ?bool $needActivation = null,
        ?bool $notify = null,
        ?string $locale = null,
        ?string $organization = null,
    ): Created {
        $body = [
            'account' => self::compact([
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'fin' => $fin,
                'needActivation' => $needActivation,
                'notify' => $notify,
            ]),
            // Only the manager comes from the caller: the organization is always the scope, and no role is ever sent (the API takes ROLE_USER)
            'group' => ['manager' => $manager, 'organization' => $this->http->scope],
            'locale' => self::locale($locale),
            // `BUSINESS` is reserved to platform administrators: refused here, like any string outside the list
            'organization' => $organization === null ? null : Enum::ensure($organization, OrganizationCategory::VALUES, 'organization'),
        ];

        return Created::fromArray(Cast::answer($this->http->post('/customer', self::compact($body))));
    }

    /**
     * The customers of the organization — a freshly created one appears as `CREATED`
     *
     * @param bool|null   $includeClosed also the `CLOSED` customers
     * @param string|null $manager       only the customers directly managed by this collaborator (uuid)
     *
     * @return Page<Customer>
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function list(?int $offset = null, ?int $limit = null, ?bool $includeClosed = null, ?string $manager = null): Page
    {
        return Page::fromArray(
            Cast::answer($this->http->get('/customer', ['offset' => $offset, 'limit' => $limit, 'includeClosed' => $includeClosed, 'manager' => $manager])),
            Customer::fromArray(...),
        );
    }

    /**
     * One account, by uuid (or email) or by model
     *
     * @param UserRef $user
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function get(string|Created|Customer|Account|UserSummary|OrderUser $user): Account
    {
        return Account::fromArray(Cast::answer($this->http->get('/account/' . Path::segment(UserId::resolve($user), 'user'))));
    }

    /**
     * Update the settings a key may write: theme, locale, notifications — nothing else is sent
     *
     * @param UserRef                                     $user
     * @param string|null                                 $locale        `FR` or `EN` — a `Locale` constant
     * @param array{login?: bool, newsletter?: bool}|null $notifications
     *
     * @throws BitgenException          the API answered an error, or no HTTP answer was received
     * @throws InvalidArgumentException an argument is invalid — nothing was sent
     */
    public function update(string|Created|Customer|Account|UserSummary|OrderUser $user, ?string $theme = null, ?string $locale = null, ?array $notifications = null): void
    {
        $action = self::compact(['theme' => $theme, 'locale' => self::locale($locale)]);
        $body = self::compact(['action' => $action === [] ? null : $action, 'notifications' => $notifications]);
        $this->http->put('/account/' . Path::segment(UserId::resolve($user), 'user'), $body);
    }

    /**
     * `FR` or `EN` — a string outside `Locale::VALUES` is a caller's mistake, refused before any request
     *
     * @throws InvalidArgumentException
     */
    private static function locale(?string $locale): ?string
    {
        return $locale === null ? null : Enum::ensure($locale, Locale::VALUES, 'locale');
    }

    /**
     * The entries that were given: a null value — or an empty array — is not sent
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function compact(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
