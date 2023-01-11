<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\FileStorage;

/**
 * Visibility
 */
interface Visibility
{
    /**
     * @var string
     */
    public const PUBLIC = 'public';
    
    /**
     * @var string
     */
    public const PRIVATE = 'private';    
}