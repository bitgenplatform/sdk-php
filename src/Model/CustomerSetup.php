<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** The settings of a customer */
final readonly class CustomerSetup
{
    public function __construct(
        /** Theme of the BITGEN web application, `light` by default */
        public string $theme,
        /** Display currency, `EUR` */
        public string $currency,
        /** Language of the web application and of the emails (`Locale`) */
        public string $locale,
        /** Category chosen at signup — `OrganizationCategory` lists the known values: `CUSTOMER`, `B2B` */
        public string $choosenOrganization,
        /** Activation email pending: the customer is invisible to bank / custody / trading / staking until they activate */
        public bool $needActivation,
        /** Whether the customer accepts BITGEN emails */
        public bool $notify,
        /** Whether the web onboarding has been completed */
        public ?bool $onboarding,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $onboarding = $data['onboarding'] ?? null;

        return new self(
            Cast::string($data, 'theme'),
            Cast::string($data, 'currency'),
            Cast::string($data, 'locale'),
            Cast::string($data, 'choosenOrganization'),
            Cast::bool($data, 'needActivation'),
            Cast::bool($data, 'notify'),
            is_bool($onboarding) ? $onboarding : null,
        );
    }
}
