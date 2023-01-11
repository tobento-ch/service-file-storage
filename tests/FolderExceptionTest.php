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
use Tobento\Service\FileStorage\FolderException;
use Tobento\Service\FileStorage\StorageException;

/**
 * FolderExceptionTest
 */
class FolderExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new FolderException(path: 'foo/bar');
        
        $this->assertInstanceof(StorageException::class, $e);
        $this->assertSame('foo/bar', $e->path());
    }
}