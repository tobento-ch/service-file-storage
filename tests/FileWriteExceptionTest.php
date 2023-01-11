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
use Tobento\Service\FileStorage\FileWriteException;
use Tobento\Service\FileStorage\StorageException;

/**
 * FileWriteExceptionTest
 */
class FileWriteExceptionTest extends TestCase
{
    public function testFileWriteException()
    {
        $e = new FileWriteException(path: 'folder/file.txt', content: 'content');
        
        $this->assertInstanceof(StorageException::class, $e);
        $this->assertSame('folder/file.txt', $e->path());
        $this->assertSame('content', $e->content());
    }
}