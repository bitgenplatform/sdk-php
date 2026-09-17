<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Model;

/** Progress of an identity verification: one `IdentityStep` per step, the hosted verification URL when the provider hosts it */
final readonly class IdentityData
{
    /**
     * @param array<string, IdentityStep> $steps
     */
    public function __construct(
        public array $steps,
        /** Internal flag */
        public bool $notifications,
        public ?string $verificationUrl,
        public ?bool $hosted,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $steps = [];
        foreach (Cast::object($data, 'steps') as $name => $step) {
            if (is_array($step)) {
                $steps[$name] = IdentityStep::fromArray(Cast::asObject($step));
            }
        }
        $hosted = $data['hosted'] ?? null;

        return new self($steps, Cast::bool($data, 'notifications'), Cast::nullableString($data, 'verificationUrl'), is_bool($hosted) ? $hosted : null);
    }
}
