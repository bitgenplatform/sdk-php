<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests\Resource;

use Bitgen\Sdk\Exception\BitgenException;
use Bitgen\Sdk\Http\HttpClient;
use Bitgen\Sdk\Model\Account;
use Bitgen\Sdk\Model\Created;
use Bitgen\Sdk\Model\Customer;
use Bitgen\Sdk\Model\CustomerState;
use Bitgen\Sdk\Model\Identity;
use Bitgen\Sdk\Model\IdentityMode;
use Bitgen\Sdk\Model\IdentityState;
use Bitgen\Sdk\Model\KybIdentity;
use Bitgen\Sdk\Model\KycIdentity;
use Bitgen\Sdk\Model\Locale;
use Bitgen\Sdk\Model\OrganizationCategory;
use Bitgen\Sdk\Resource\CustomerResource;
use Bitgen\Sdk\Tests\Http\FakeTransport;
use Bitgen\Sdk\Tests\TypeErrors;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CustomerResourceTest extends TestCase
{
    use TypeErrors;

    /** A realistic KYC identity file (contract § 4) */
    public const KYC = [
        'uuid' => 'id-1', 'state' => 'VALIDATED', 'mode' => 'KYC',
        'form' => ['european_residency' => true, 'ppe' => false, 'ppp' => false, 'source_income' => 'salary', 'net_income' => '30k-50k', 'experience' => 'beginner', 'submittedAt' => 1700000000, 'score' => 12],
        'data' => ['steps' => ['info' => ['status' => 'VALIDATED', 'submittedAt' => 1700000000], 'selfie' => ['status' => 'PENDING', 'submittedAt' => null]], 'notifications' => true, 'verificationUrl' => 'https://verify.example/abc', 'hosted' => true],
        'validatedAt' => 1700003600, 'expiresAt' => 1731539600, 'renewalNotifiedAt' => null,
    ];

    /** A realistic KYB identity file */
    public const KYB = [
        'uuid' => 'id-2', 'state' => 'PENDING', 'mode' => 'KYB',
        'form' => ['activity' => 'software', 'submittedAt' => null, 'score' => 0],
        'data' => ['steps' => ['kbis' => ['status' => 'REQUESTED', 'submittedAt' => null]], 'notifications' => false, 'verificationUrl' => null],
        'validatedAt' => null, 'expiresAt' => null, 'renewalNotifiedAt' => null,
    ];

    /** A realistic item of `GET /customer` */
    public const CUSTOMER = [
        'uuid' => 'c-1', 'state' => 'ENABLED', 'isAvailable' => true, 'createdAt' => 1699000000, 'login' => 'jean@valjean.fr', 'canLogin' => true,
        'account' => ['email' => 'jean@valjean.fr', 'firstname' => 'Jean', 'lastname' => 'Valjean', 'fin' => null, 'birthdate' => 315532800, 'phoneNumber' => 612345678, 'phoneZone' => '+33', 'address' => ['uuid' => 'ad-1', 'state' => 'VALIDATED', 'address' => '1 rue de Paris'], 'referralCode' => 'JEAN42'],
        'client' => ['roles' => ['ROLE_USER'], 'hasTfa' => false, 'hasPhishing' => false, 'isValid' => true],
        'action' => ['setup' => ['theme' => 'light', 'currency' => 'EUR', 'locale' => 'FR', 'choosenOrganization' => 'CUSTOMER', 'needActivation' => false, 'notify' => true, 'onboarding' => true]],
        'identity' => self::KYC,
        'business' => [['identity' => self::KYB]],
        'collaborations' => ['collaborator' => [['uuid' => 'col-1', 'state' => 'ENABLED', 'roles' => ['ROLE_USER'], 'organization' => 'ACME', 'organizationUuid' => 'org-uuid', 'manager' => 'man-1']], 'manager' => []],
        'alert' => [['uuid' => 'al-1', 'state' => 'OPEN', 'severity' => 'WARNING', 'sources' => ['kyt' => ['score' => 3]]]],
        'somethingNew' => 'ignored',
    ];

    private FakeTransport $transport;
    private CustomerResource $customer;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->customer = new CustomerResource(new HttpClient($this->transport, 'org-uuid', 'k', 'https://api.test', 1000, 'ua'));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /** @return array<mixed> */
    private function lastBody(): array
    {
        $body = json_decode((string) $this->transport->last()['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        return $body;
    }

    public function testCreateSendsTheExactBodyWithTheScopeAsOrganizationAndNoRole(): void
    {
        $this->transport->willAnswer(201, '{"uuid":"c-1"}');
        $created = $this->customer->create(email: 'jean@valjean.fr', manager: 'man-1', firstname: 'Jean', locale: Locale::FR);

        self::assertInstanceOf(Created::class, $created);
        self::assertSame('c-1', $created->uuid);
        self::assertSame('POST', $this->transport->last()['method']);
        self::assertSame('https://api.test/customer', $this->transport->last()['url']);
        self::assertSame([
            'account' => ['email' => 'jean@valjean.fr', 'firstname' => 'Jean'],
            'group' => ['manager' => 'man-1', 'organization' => 'org-uuid'],
            'locale' => 'FR',
        ], $this->lastBody());
        // (the exact body above proves it: no `role`, the organization is the scope)

        // every option, needActivation / notify travel as given (false is sent, not dropped)
        $this->customer->create(email: 'a@b.c', manager: 'm', lastname: 'V', fin: 'FIN', needActivation: false, notify: false, locale: Locale::EN, organization: OrganizationCategory::B2B);
        self::assertSame([
            'account' => ['email' => 'a@b.c', 'lastname' => 'V', 'fin' => 'FIN', 'needActivation' => false, 'notify' => false],
            'group' => ['manager' => 'm', 'organization' => 'org-uuid'],
            'locale' => 'EN',
            'organization' => 'B2B',
        ], $this->lastBody());
    }

    public function testBusinessCategoryIsRefusedBeforeAnyRequest(): void
    {
        try {
            $this->customer->create(email: 'a@b.c', manager: 'm', locale: 'en');
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertSame('locale must be FR or EN', $e->getMessage());
        }
        try {
            $this->customer->update('c-1', locale: 'xx');
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }
        try {
            $this->customer->create(email: 'a@b.c', manager: 'm', organization: 'BUSINESS');   // reserved to platform administrators: outside OrganizationCategory::VALUES
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertSame('organization must be CUSTOMER or B2B', $e->getMessage());   // the list, never the value received
        }
        try {
            $this->customer->create(email: 'a@b.c', manager: 'm', organization: 'b2b');
            self::fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testListSendsTheExactQueryAndMapsCustomers(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [self::CUSTOMER]]));
        $page = $this->customer->list(offset: 0, limit: 50, includeClosed: true);

        self::assertSame('https://api.test/customer?offset=0&limit=50&includeClosed=true', $this->transport->last()['url']);
        self::assertSame(1, $page->count);
        $customer = $page->items[0];
        self::assertSame('c-1', $customer->uuid);
        self::assertSame(CustomerState::ENABLED, $customer->state);
        self::assertTrue($customer->isAvailable);
        self::assertSame(1699000000, $customer->createdAt);
        self::assertSame('jean@valjean.fr', $customer->login);
        self::assertTrue($customer->canLogin);
        self::assertSame('Jean', $customer->account->firstname);
        self::assertSame('Valjean', $customer->account->lastname);
        self::assertNull($customer->account->fin);
        self::assertSame(315532800, $customer->account->birthdate);
        self::assertSame(612345678, $customer->account->phoneNumber);
        self::assertSame('+33', $customer->account->phoneZone);
        self::assertNotNull($customer->account->address);
        self::assertSame('1 rue de Paris', $customer->account->address->address);
        self::assertSame('VALIDATED', $customer->account->address->state);
        self::assertSame('JEAN42', $customer->account->referralCode);
        self::assertSame(['ROLE_USER'], $customer->client->roles);
        self::assertFalse($customer->client->hasTfa);
        self::assertTrue($customer->client->isValid);
        self::assertSame('light', $customer->action->setup->theme);
        self::assertSame('EUR', $customer->action->setup->currency);
        self::assertSame(OrganizationCategory::CUSTOMER, $customer->action->setup->choosenOrganization);
        self::assertFalse($customer->action->setup->needActivation);
        self::assertTrue($customer->action->setup->onboarding);
        // KYC identity, narrowed by class
        $identity = $customer->identity;
        self::assertInstanceOf(KycIdentity::class, $identity);
        self::assertSame(IdentityState::VALIDATED, $identity->state);
        self::assertSame(IdentityMode::KYC, $identity->mode);
        self::assertTrue($identity->form->european_residency);
        self::assertSame('salary', $identity->form->source_income);
        self::assertSame(12, $identity->form->score);
        self::assertSame('VALIDATED', $identity->data->steps['info']->status);
        self::assertNull($identity->data->steps['selfie']->submittedAt);
        self::assertTrue($identity->data->notifications);
        self::assertSame('https://verify.example/abc', $identity->data->verificationUrl);
        self::assertTrue($identity->data->hosted);
        self::assertSame(1700003600, $identity->validatedAt);
        self::assertNull($identity->renewalNotifiedAt);
        // KYB identity of the business
        $business = $customer->business[0]->identity;
        self::assertInstanceOf(KybIdentity::class, $business);
        self::assertSame('software', $business->form->activity);
        self::assertSame(0, $business->form->score);
        self::assertNull($business->data->hosted);
        self::assertSame('ENABLED', $customer->collaborations->collaborator[0]->state);
        self::assertSame('ACME', $customer->collaborations->collaborator[0]->organization);
        self::assertSame('man-1', $customer->collaborations->collaborator[0]->manager);
        self::assertSame([], $customer->collaborations->manager);
        self::assertSame('WARNING', $customer->alert[0]->severity);
        self::assertSame(['kyt' => ['score' => 3]], $customer->alert[0]->sources);

        $this->customer->list();
        self::assertSame('https://api.test/customer', $this->transport->last()['url']);
        $this->customer->list(manager: 'man-1', includeClosed: false);
        self::assertSame('https://api.test/customer?includeClosed=false&manager=man-1', $this->transport->last()['url']);
    }

    public function testGetByUuidEmailOrObjectMapsTheAccount(): void
    {
        $account = [
            'uuid' => 'c-1', 'identity' => self::KYC, 'business' => [],
            'account' => ['email' => 'jean@valjean.fr', 'firstname' => 'Jean', 'lastname' => null, 'fin' => null, 'birthdate' => null, 'phoneNumber' => null, 'phoneZone' => null, 'address' => ['uuid' => 'ad-1', 'address' => '1 rue de Paris'], 'referralCode' => 'JEAN42'],
            'notifications' => ['login' => true, 'newsletter' => false],
            'setup' => ['theme' => 'dark', 'currency' => 'EUR', 'locale' => 'EN', 'choosenOrganization' => 'B2B', 'needActivation' => true, 'notify' => false],
        ];
        $this->transport->willAnswer(200, self::json($account))->willAnswer(200, self::json($account))->willAnswer(200, self::json($account));

        $result = $this->customer->get('c-1');
        self::assertSame('https://api.test/account/c-1', $this->transport->last()['url']);
        self::assertSame('c-1', $result->uuid);
        self::assertInstanceOf(KycIdentity::class, $result->identity);
        self::assertSame([], $result->business);
        self::assertNull($result->account->lastname);
        self::assertNotNull($result->account->address);
        self::assertNull($result->account->address->state);
        self::assertTrue($result->notifications->login);
        self::assertFalse($result->notifications->newsletter);
        self::assertSame('dark', $result->setup->theme);
        self::assertTrue($result->setup->needActivation);
        self::assertNull($result->setup->onboarding);

        $this->customer->get('jean@valjean.fr');
        self::assertSame('https://api.test/account/jean%40valjean.fr', $this->transport->last()['url']);
        $this->customer->get(new Created('c-1'));
        self::assertSame('https://api.test/account/c-1', $this->transport->last()['url']);
    }

    public function testAnUnknownIdentityModeGivesTheBaseIdentity(): void
    {
        $this->transport->willAnswer(200, self::json(['count' => 1, 'items' => [['uuid' => 'c', 'identity' => ['uuid' => 'i', 'state' => 'CREATED', 'mode' => 'KYX', 'form' => ['x' => 1]]]]]));
        $identity = $this->customer->list()->items[0]->identity;
        self::assertSame(Identity::class, $identity::class);
        self::assertSame('KYX', $identity->mode);
    }

    public function testUpdateSendsOnlyTheKeysGiven(): void
    {
        $this->transport->willAnswer(200, '[]');
        $this->customer->update('c-1', locale: Locale::EN, notifications: ['newsletter' => false]);
        self::assertSame('PUT', $this->transport->last()['method']);
        self::assertSame('https://api.test/account/c-1', $this->transport->last()['url']);
        self::assertSame(['action' => ['locale' => 'EN'], 'notifications' => ['newsletter' => false]], $this->lastBody());

        $this->customer->update('c-1', theme: 'dark');
        self::assertSame(['action' => ['theme' => 'dark']], $this->lastBody());

        $this->customer->update('c-1');
        self::assertSame('{}', $this->transport->last()['body']);
        $this->customer->update('c-1', notifications: []);   // nothing to update: nothing sent
        self::assertSame('{}', $this->transport->last()['body']);
    }

    public function testApiErrorsBecomeBitgenExceptions(): void
    {
        $cases = [
            [403, 'forbidden_permission', fn () => $this->customer->list()],
            [404, 'unknown_user', fn () => $this->customer->get('nope')],
            [422, 'invalid_include_closed', fn () => $this->customer->list(includeClosed: true)],
            [403, 'missing_group_organization_or_manager', fn () => $this->customer->create(email: 'a@b.c', manager: 'm')],
        ];
        foreach ($cases as [$status, $code, $call]) {
            $this->transport->willAnswer($status, self::json(['error' => true, 'message' => $code, 'code' => $status]));
            try {
                $call();
                self::fail('expected a BitgenException');
            } catch (BitgenException $e) {
                self::assertSame($status, $e->status);
                self::assertSame($code, $e->errorCode);
            }
        }
    }

    public function testInvalidUsersAreRefusedBeforeAnyRequest(): void
    {
        foreach (['', '..', new Created('')] as $bad) {
            try {
                $this->customer->get($bad);
                self::fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
            }
        }
        self::assertSame([], $this->transport->requests);
    }

    public function testAModelCarryingTheCustomerUuidIsAcceptedAndAnyOtherObjectIsATypeError(): void
    {
        $this->transport->willAnswer(200, self::json(['uuid' => 'c-1']));
        $this->customer->get(Customer::fromArray(self::CUSTOMER));
        self::assertSame('https://api.test/account/c-1', $this->transport->last()['url']);
        $this->customer->update(Account::fromArray(['uuid' => 'c-1']), theme: 'dark');
        self::assertSame('https://api.test/account/c-1', $this->transport->last()['url']);
        $sent = count($this->transport->requests);
        self::assertTypeError(fn () => $this->customer->get(new \stdClass())); // @phpstan-ignore argument.type
        self::assertCount($sent, $this->transport->requests);
    }
}
