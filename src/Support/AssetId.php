<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Support;

use Bitgen\Sdk\Model\Asset;
use Bitgen\Sdk\Model\AssetRef;
use InvalidArgumentException;

/**
 * An asset, as accepted by every method expecting one: its uuid or ISO code as a string (`Asset::ETH`, `'eth'`,
 * a uuid — any case, the API normalizes it), or an `Asset` / `AssetRef` model — the SDK then sends its uuid.
 *
 * @internal
 */
final class AssetId
{
    /**
     * @throws InvalidArgumentException a model without a non-empty `uuid`
     */
    public static function resolve(string|Asset|AssetRef $asset): string
    {
        if (is_string($asset)) {
            return $asset;
        }
        if (trim($asset->uuid) === '') {
            throw new InvalidArgumentException('asset must be a uuid or an ISO code, or a model with a non-empty uuid');
        }

        return $asset->uuid;
    }
}
