<?php
declare(strict_types=1);

namespace Neos\Flow\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ramsey\Uuid\Uuid;


/**
 * WORKAROUND!!!!!!!!!!!!!!!!! this is the last dependency to Neos/Flow :D :D :D
 * but luckily we can get easily rid of this.
 *
 *
 *
 * A utility class for various algorithms.
 *
 * @Flow\Scope("singleton")
 */
class Algorithms
{
    /**
     * Generates a universally unique identifier (UUID) according to RFC 4122.
     * The algorithm used here, might not be completely random.
     *
     * If php-uuid was installed it will be used instead to speed up the process.
     *
     * @return string The universally unique id
     * @throws \Exception
     * @todo Optionally generate type 1 and type 5 UUIDs.
     */
    public static function generateUUID(): string
    {
        if (is_callable('uuid_create')) {
            return strtolower(uuid_create(UUID_TYPE_RANDOM));
        }

        return Uuid::uuid4()->toString();
    }

}
