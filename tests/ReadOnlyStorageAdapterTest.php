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

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\FileException;
use Tobento\Service\FileStorage\FileWriteException;
use Tobento\Service\FileStorage\FilesInterface;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\FolderException;
use Tobento\Service\FileStorage\FoldersInterface;
use Tobento\Service\FileStorage\NullStorage;
use Tobento\Service\FileStorage\ReadOnlyStorageAdapter;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Visibility;
use Tobento\Service\Filesystem\Dir;

class ReadOnlyStorageAdapterTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/tmp/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/tmp/');
    }
    
    protected function createStorage(bool $throw = false): ReadOnlyStorageAdapter
    {
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: __DIR__.'/tmp/uploads',
            ),
            config: ['public_url' => 'https://www.example.com/files'],
        );

        $storage = new Flysystem\Storage(
            name: 'uploads',
            flysystem: $filesystem,
            fileFactory: new Flysystem\FileFactory(
                flysystem: $filesystem,
                streamFactory: new Psr17Factory()
            ),
        );

        $storage->write(path: 'file.txt', content: 'content');
        $storage->createFolder(path: 'bar');
        
        return new ReadOnlyStorageAdapter(
            storage: $storage,
            throw: $throw,
        );
    }
    
    public function testImplementsStorageInterface()
    {
        $this->assertInstanceof(StorageInterface::class, $this->createStorage());
    }

    public function testNameMethod()
    {
        $this->assertSame('uploads', $this->createStorage()->name());
    }
    
    public function testWriteMethod()
    {
        $this->expectException(FileWriteException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        $storage->write(path: 'file.txt', content: 'lorem');
    }
    
    public function testExistsMethod()
    {
        $storage = $this->createStorage();
        
        $this->assertTrue($storage->exists('file.txt'));
        $this->assertFalse($storage->exists('image.jpg'));
    }
    
    public function testFileMethod()
    {
        $storage = $this->createStorage();
        
        $file = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->file(path: 'file.txt');
        
        $this->assertInstanceOf(FileInterface::class, $file);
        $this->assertSame('file.txt', $file->path());
        $this->assertSame('file.txt', $file->name());
        $this->assertSame('file', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('', $file->folderPath());
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertSame('content', $file->content());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame(7, $file->size());
        $this->assertSame(null, $file->width());
        $this->assertSame(null, $file->height());
        $this->assertTrue(is_int($file->lastModified()));
        $this->assertSame('https://www.example.com/files/file.txt', $file->url());
        $this->assertSame('public', $file->visibility());
        $this->assertSame([], $file->metadata());
        $this->assertFalse($file->isHtmlImage());
    }
    
    public function testFilesMethod()
    {
        $storage = $this->createStorage();
        
        $files = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->files(path: '');
        
        $this->assertInstanceOf(FilesInterface::class, $files);
        $this->assertSame(1, count($files->all()));
        $this->assertSame(0, count($storage->files(path: 'foo')->all()));
    }
    
    public function testDeleteMethod()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        $storage->delete(path: 'file.txt');
    }
    
    public function testMoveMethod()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        $storage->move(from: 'file.txt', to: 'moved.txt');
    }
    
    public function testCopyMethod()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        $storage->copy(from: 'file.txt', to: 'copied.txt');
    }
    
    public function testCreateFolderMethod()
    {
        $this->expectException(FolderException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        
        $storage->createFolder(path: 'foo/bar');
    }
    
    public function testFolderExistsMethod()
    {
        $storage = $this->createStorage();
        
        $this->assertFalse($storage->folderExists('foo'));
        $this->assertTrue($storage->folderExists('bar'));
    }
    
    public function testFoldersMethod()
    {
        $storage = $this->createStorage();
        
        $folders = $storage->folders(
            path: '',
            recursive: false
        );
        
        $this->assertInstanceOf(FoldersInterface::class, $folders);
        $this->assertSame(1, count($folders->all()));
    }
    
    public function testDeleteFolderMethod()
    {
        $this->expectException(FolderException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        $storage->deleteFolder(path: 'foo/bar');
    }
    
    public function testSetVisibility()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Storage uploads is readonly');
        
        $storage = $this->createStorage();
        
        $storage->setVisibility(
            path: 'file.txt',
            visibility: Visibility::PUBLIC
        );
    }
}