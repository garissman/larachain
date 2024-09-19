<?php

declare(strict_types=1);

/**
 * This file is part of Scout Extended.
 *
 * (c) Algolia Team <contact@algolia.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Garissman\Clerk\Facades;


use Garissman\Wit\Client;
use Illuminate\Support\Facades\Facade;



/**
 *
 * @method static string message(string $message,string|null $conversationId=null )
 * @method static array converse(string $message, string|null $conversationId = null)
 *
 */
class Clerk extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return \Garissman\Clerk\Clerk::class;
    }
}
