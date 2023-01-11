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

namespace Tobento\Service\FileStorage\Test\Flysystem;

use PHPUnit\Framework\TestCase;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\FilesInterface;
use Tobento\Service\FileStorage\FolderInterface;
use Tobento\Service\FileStorage\FoldersInterface;
use Tobento\Service\FileStorage\Visibility;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\FileWriteException;
use Tobento\Service\FileStorage\FileException;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Filesystem\File;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\StreamInterface;

/**
 * StorageTest
 */
class StorageTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/');
    }
    
    protected function createStorage(string $name, string $folder): StorageInterface
    {
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: __DIR__.'/../'.$folder,
            ),
            config: ['public_url' => 'https://www.example.com/path'],
        );

        return new Flysystem\Storage(
            name: $name,
            flysystem: $filesystem,
            fileFactory: new Flysystem\FileFactory(
                flysystem: $filesystem,
                streamFactory: new Psr17Factory()
            ),
        );
    }
    
    public function testNameMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $this->assertSame('local', $storage->name());
    }    
    
    public function testWriteMethodWithString()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $this->assertTrue($storage->exists('file.txt'));
        $this->assertSame('lorem', $storage->with('stream')->file('file.txt')->content());
    }
    
    public function testWriteMethodWithResource()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $resource = fopen('php://memory', 'ab+');
        fwrite($resource, 'lorem');
        
        $storage->write(path: 'resource.txt', content: $resource);
        
        $this->assertTrue($storage->exists('resource.txt'));
        $this->assertSame('lorem', $storage->with('stream')->file('resource.txt')->content());
    }
    
    public function testWriteMethodWithStream()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $stream = (new Psr17Factory())->createStream('lorem');
        
        $storage->write(path: 'stream.txt', content: $stream);
        
        $this->assertTrue($storage->exists('stream.txt'));
        $this->assertSame('lorem', $storage->with('stream')->file('stream.txt')->content());
    }
    
    public function testWriteMethodWithFile()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $file = new File(__DIR__.'/../src/foo.txt');
        
        $storage->write(path: 'file.txt', content: $file);
        
        $this->assertTrue($storage->exists('file.txt'));
        $this->assertSame('foo', $storage->with('stream')->file('file.txt')->content());
    }
    
    public function testWriteMethodThrowsFileWriteExceptionIfUnsupportedContent()
    {
        $this->expectException(FileWriteException::class);
        
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $storage->write(path: 'file.txt', content: true);
    }    
    
    public function testExistsMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $this->assertFalse($storage->exists('file.txt'));
        
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $this->assertTrue($storage->exists('file.txt'));
    }
    
    public function testFileMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $file = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->file(path: 'foo.txt');
        
        $this->assertInstanceOf(FileInterface::class, $file);
        $this->assertSame('foo.txt', $file->path());
        $this->assertSame('foo.txt', $file->name());
        $this->assertSame('foo', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('', $file->folderPath());
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertSame('foo', $file->content());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame(3, $file->size());
        $this->assertSame(null, $file->width());
        $this->assertSame(null, $file->height());
        $this->assertTrue(is_int($file->lastModified()));
        $this->assertSame('https://www.example.com/path/foo.txt', $file->url());
        $this->assertSame('public', $file->visibility());
        $this->assertSame([], $file->metadata());
        $this->assertFalse($file->isHtmlImage());
    }
    
    public function testFileMethodFromSubfolder()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $file = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->file(path: 'foo/subfoo/subfoo.txt');
        
        $this->assertInstanceOf(FileInterface::class, $file);
        $this->assertSame('foo/subfoo/subfoo.txt', $file->path());
        $this->assertSame('subfoo.txt', $file->name());
        $this->assertSame('subfoo', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('foo/subfoo', $file->folderPath());
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertSame('subfoo', $file->content());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame(6, $file->size());
        $this->assertSame(null, $file->width());
        $this->assertSame(null, $file->height());
        $this->assertTrue(is_int($file->lastModified()));
        $this->assertSame('https://www.example.com/path/foo/subfoo/subfoo.txt', $file->url());
        $this->assertSame('public', $file->visibility());
        $this->assertSame([], $file->metadata());
        $this->assertFalse($file->isHtmlImage());
    }
    
    public function testFilesMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $files = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->files(path: '');
        
        $this->assertInstanceOf(FilesInterface::class, $files);
        $this->assertSame(2, count($files->all()));
        
        $file = $files->all()[0];
        $this->assertSame('foo.txt', $file->path());
        $this->assertSame('foo.txt', $file->name());
        $this->assertSame('foo', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('', $file->folderPath());
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertSame('foo', $file->content());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame(3, $file->size());
        $this->assertSame(null, $file->width());
        $this->assertSame(null, $file->height());
        $this->assertTrue(is_int($file->lastModified()));
        $this->assertSame('https://www.example.com/path/foo.txt', $file->url());
        $this->assertSame('public', $file->visibility());
        $this->assertSame([], $file->metadata());
        $this->assertFalse($file->isHtmlImage());        
    }
    
    public function testFilesMethodFromDeeperPath()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $files = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->files(path: 'foo');
        
        $this->assertSame(1, count($files->all()));
        
        $file = $files->all()[0];
        $this->assertSame('foo/lorem.txt', $file->path());
        $this->assertSame('lorem.txt', $file->name());
        $this->assertSame('lorem', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('foo', $file->folderPath());
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertSame('lorem', $file->content());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame('https://www.example.com/path/foo/lorem.txt', $file->url());     
    }
    
    public function testFilesMethodRecursive()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $files = $storage
            ->with('stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility')
            ->files(path: '', recursive: true);
        
        $this->assertSame(4, count($files->all()));
    }    

    public function testDeleteFileMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $this->assertTrue($storage->exists('file.txt'));
        
        $storage->delete(path: 'file.txt');
        
        $this->assertFalse($storage->exists('file.txt'));
    }
    
    public function testMoveFileMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        $storage->write(path: 'file.txt', content: 'lorem');
                
        $storage->move(from: 'file.txt', to: 'moved.txt');
        
        $this->assertFalse($storage->exists('file.txt'));
        $this->assertTrue($storage->exists('moved.txt'));
        $this->assertSame('lorem', $storage->with('stream')->file('moved.txt')->content());
    }
    
    public function testCopyFileMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        $storage->write(path: 'file.txt', content: 'lorem');
                
        $storage->copy(from: 'file.txt', to: 'copied.txt');
        
        $this->assertTrue($storage->exists('file.txt'));
        $this->assertSame('lorem', $storage->with('stream')->file('file.txt')->content());
        
        $this->assertTrue($storage->exists('copied.txt'));
        $this->assertSame('lorem', $storage->with('stream')->file('copied.txt')->content());
    }
    
    public function testCreateFolderMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $this->assertFalse($storage->folderExists('foo/bar'));
        
        $storage->createFolder(path: 'foo/bar');
                
        $this->assertTrue($storage->folderExists('foo/bar'));
    }
    
    public function testFolderExistsMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $this->assertFalse($storage->folderExists('folder'));
        $this->assertFalse($storage->folderExists('foo/bar'));
        
        $storage->createFolder(path: 'folder');
        $storage->createFolder(path: 'foo/bar');
        
        $this->assertTrue($storage->folderExists('folder'));
        $this->assertTrue($storage->folderExists('foo/bar'));
    }
    
    public function testFoldersMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $folders = $storage->folders(
            path: '',
            recursive: false
        );
        
        $this->assertInstanceOf(FoldersInterface::class, $folders);
        $this->assertSame(2, count($folders->all()));
        
        $folder = $folders->all()[0];
        $this->assertSame('bar', $folder->path());
        $this->assertSame('', $folder->parentPath());
        $this->assertSame('bar', $folder->name());
        $this->assertTrue(is_int($folder->lastModified()));
        $this->assertSame('public', $folder->visibility());
        $this->assertSame([], $folder->metadata());
    }

    public function testFoldersMethodFromDeeperPath()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $folders = $storage->folders(
            path: 'foo',
            recursive: true
        );
        
        $this->assertSame(1, count($folders->all()));

        $folder = $folders->all()[0];
        $this->assertSame('foo/subfoo', $folder->path());
        $this->assertSame('foo', $folder->parentPath());
        $this->assertSame('subfoo', $folder->name());
    }
    
    public function testFoldersMethodRecursive()
    {
        $storage = $this->createStorage(name: 'local', folder: 'src');
        
        $folders = $storage->folders(
            path: '',
            recursive: true
        );
        
        $this->assertSame(3, count($folders->all()));     
    }
    
    public function testDeleteFolderMethod()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
                
        $storage->createFolder(path: 'foo/bar');
        
        $this->assertTrue($storage->folderExists('foo/bar'));
        
        $storage->deleteFolder(path: 'foo/bar');
        
        $this->assertFalse($storage->folderExists('foo/bar'));
    }
    
    public function testSetVisibility()
    {
        $storage = $this->createStorage(name: 'local', folder: 'tmp');
        
        $storage->write(path: 'file.txt', content: 'lorem');
        
        $public = Visibility::PUBLIC;
        $private = Visibility::PRIVATE;
        
        $storage->setVisibility(
            path: 'file.txt',
            visibility: $public
        );
        
        $storage->setVisibility(
            path: 'file.txt',
            visibility: $private
        );        

        $this->assertTrue(true);
    }    
}