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
use Tobento\Service\FileStorage\NullStorage;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\FilesInterface;
use Tobento\Service\FileStorage\FoldersInterface;
use Tobento\Service\FileStorage\StorageInterface;

class NullStorageTest extends TestCase
{
    public function testImplementsStorageInterface()
    {
        $this->assertInstanceof(StorageInterface::class, new NullStorage());
    }

    public function testNameMethod()
    {
        $this->assertSame('null', (new NullStorage())->name());
        $this->assertSame('custom', (new NullStorage(name: 'custom'))->name());
    }
    
    public function testDefaultTypeIsPrivate()
    {
        $storage = new NullStorage();
        $this->assertSame('private', $storage->type());
        $this->assertTrue($storage->isPrivate());
        $this->assertFalse($storage->isPublic());
    }

    public function testCanSetTypeToPublic()
    {
        $storage = new NullStorage(name: 'null', type: 'public');
        $this->assertSame('public', $storage->type());
        $this->assertTrue($storage->isPublic());
        $this->assertFalse($storage->isPrivate());
    }

    public function testCanSetTypeToPrivate()
    {
        $storage = new NullStorage(name: 'null', type: 'private');
        $this->assertSame('private', $storage->type());
        $this->assertTrue($storage->isPrivate());
        $this->assertFalse($storage->isPublic());
    }

    public function testInvalidTypeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        new NullStorage(name: 'null', type: 'invalid-type');
    }
    
    public function testWriteMethod()
    {
        $storage = new NullStorage();
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $this->assertFalse($storage->exists('file.txt'));
    }
    
    public function testExistsMethod()
    {
        $storage = new NullStorage();
        
        $this->assertFalse($storage->exists('file.txt'));
        
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $this->assertFalse($storage->exists('file.txt'));
    }
    
    public function testFileMethod()
    {
        $storage = new NullStorage();
        
        $file = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url')
            ->file(path: 'foo.txt');
        
        $this->assertInstanceOf(FileInterface::class, $file);
        $this->assertSame('null', $file->storageName());
        $this->assertSame('foo.txt', $file->path());
        $this->assertSame('foo.txt', $file->name());
        $this->assertSame('foo', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('', $file->folderPath());
        $this->assertSame(null, $file->stream());
        $this->assertSame(null, $file->content());
        $this->assertSame(null, $file->mimeType());
        $this->assertSame(null, $file->size());
        $this->assertSame(0, $file->width());
        $this->assertSame(0, $file->height());
        $this->assertSame(null, $file->lastModified());
        $this->assertSame('', $file->url());
        $this->assertSame([], $file->metadata());
        $this->assertFalse($file->isHtmlImage());
    }
    
    public function testFilesMethod()
    {
        $storage = new NullStorage();
        
        $files = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url')
            ->files(path: '');
        
        $this->assertInstanceOf(FilesInterface::class, $files);
        $this->assertSame(0, count($files->all()));
        
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $this->assertSame(0, count($storage->files(path: '')->all()));
    }
    
    public function testDeleteMethod()
    {
        $storage = new NullStorage();
        $storage->delete(path: 'file.txt');
        $this->assertTrue(true);
    }
    
    public function testMoveMethod()
    {
        $storage = new NullStorage();
        $storage->move(from: 'file.txt', to: 'moved.txt');
        $this->assertTrue(true);
    }
    
    public function testCopyMethod()
    {
        $storage = new NullStorage();
        $storage->copy(from: 'file.txt', to: 'copied.txt');
        $this->assertTrue(true);
    }
    
    public function testCreateFolderMethod()
    {
        $storage = new NullStorage();
        
        $this->assertFalse($storage->folderExists('foo/bar'));
        
        $storage->createFolder(path: 'foo/bar');
                
        $this->assertFalse($storage->folderExists('foo/bar'));
    }
    
    public function testFolderExistsMethod()
    {
        $storage = new NullStorage();
        
        $this->assertFalse($storage->folderExists('folder'));
        
        $storage->createFolder(path: 'folder');
        
        $this->assertFalse($storage->folderExists('folder'));
    }
    
    public function testFoldersMethod()
    {
        $storage = new NullStorage();
        
        $folders = $storage->folders(
            path: '',
            recursive: false
        );
        
        $this->assertInstanceOf(FoldersInterface::class, $folders);
        $this->assertSame(0, count($folders->all()));
    }
    
    public function testDeleteFolderMethod()
    {
        $storage = new NullStorage();
        
        $storage->deleteFolder(path: 'foo/bar');
        
        $this->assertFalse($storage->folderExists('foo/bar'));
    }
}