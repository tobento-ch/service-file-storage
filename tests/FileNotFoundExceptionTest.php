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
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\StorageException;

/**
 * FileNotFoundExceptionTest
 */
class FileNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new FileNotFoundException(path: 'folder/file.txt');
        
        $this->assertInstanceof(StorageException::class, $e);
        $this->assertSame('folder/file.txt', $e->path());
    }
}