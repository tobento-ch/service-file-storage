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
use Tobento\Service\FileStorage\Folder;
use Tobento\Service\FileStorage\FolderInterface;

/**
 * FolderTest
 */
class FolderTest extends TestCase
{
    public function testWithPathOnly()
    {
        $folder = new Folder(path: 'foo/bar');
        
        $this->assertInstanceof(FolderInterface::class, $folder);
        $this->assertSame('foo/bar', $folder->path());
        $this->assertSame('foo', $folder->parentPath());
        $this->assertSame('bar', $folder->name());
        $this->assertSame(null, $folder->lastModified());
        $this->assertSame(null, $folder->visibility());
        $this->assertSame([], $folder->metadata());
    }
    
    public function testWithAllAttributes()
    {
        $folder = new Folder(
            path: 'bar',
            lastModified: 1672822057,
            visibility: 'private',
            metadata: ['foo' => 'bar'],            
        );
        
        $this->assertSame('bar', $folder->path());
        $this->assertSame('', $folder->parentPath());
        $this->assertSame('bar', $folder->name());
        $this->assertSame(1672822057, $folder->lastModified());
        $this->assertSame('private', $folder->visibility());
        $this->assertSame(['foo' => 'bar'], $folder->metadata());
    }
}