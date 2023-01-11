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

namespace Tobento\Service\FileStorage\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\FileStorage\StorageException;
use Exception;

/**
 * StorageExceptionTest
 */
class StorageExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new StorageException(message: 'message');
        
        $this->assertInstanceof(Exception::class, $e);
    }
}