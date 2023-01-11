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
use Tobento\Service\FileStorage\Flysystem\FileFactory;
use Tobento\Service\FileStorage\Flysystem\FileFactoryInterface as FlysystemFactoryInterface;
use Tobento\Service\FileStorage\FileFactoryInterface;
use Tobento\Service\FileStorage\FileInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FileAttributes;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\StreamInterface;

/**
 * FileFactoryTest
 */
class FileFactoryTest extends TestCase
{
    protected function createFlysystem(): FilesystemOperator
    {
        return new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: __DIR__.'/../src/',
            ),
            config: ['public_url' => 'https://www.example.com/path'],
        );
    }
    
    public function testCreateFileFactory()
    {
        $fileFactory = new FileFactory(
            flysystem: $this->createFlysystem(),
            streamFactory: new Psr17Factory(),
        );
        
        $this->assertInstanceof(FileFactoryInterface::class, $fileFactory);
        $this->assertInstanceof(FlysystemFactoryInterface::class, $fileFactory);
    }
    
    public function testCreateFileFromPathMethod()
    {
        $fileFactory = new FileFactory(
            flysystem: $this->createFlysystem(),
            streamFactory: new Psr17Factory(),
        );
        
        $file = $fileFactory->createFileFromPath(
            path: 'foo.txt',
            with: ['stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility'],
        );
        
        $this->assertInstanceof(FileInterface::class, $file);
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
    
    public function testCreateFileFromPathMethodWithImage()
    {
        $fileFactory = new FileFactory(
            flysystem: $this->createFlysystem(),
            streamFactory: new Psr17Factory(),
        );
        
        $file = $fileFactory->createFileFromPath(
            path: 'image.jpg',
            with: ['stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility'],
        );
        
        $this->assertInstanceof(FileInterface::class, $file);
        $this->assertSame('image.jpg', $file->path());
        $this->assertSame('image.jpg', $file->name());
        $this->assertSame('image', $file->filename());
        $this->assertSame('jpg', $file->extension());
        $this->assertSame('', $file->folderPath());
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertTrue(is_string($file->content()));
        $this->assertSame('image/jpeg', $file->mimeType());
        $this->assertSame(20042, $file->size());
        $this->assertSame(null, $file->width()); // as it reads from url
        $this->assertSame(null, $file->height()); // as it reads from url
        $this->assertTrue(is_int($file->lastModified()));
        $this->assertSame('https://www.example.com/path/image.jpg', $file->url());
        $this->assertSame('public', $file->visibility());
        $this->assertSame([], $file->metadata());
        $this->assertTrue($file->isHtmlImage());        
    }
    
    public function testCreateFileFromFileAttributesMethod()
    {
        $fileFactory = new FileFactory(
            flysystem: $this->createFlysystem(),
            streamFactory: new Psr17Factory(),
        );
        
        $attributes = new FileAttributes(
            path: 'foo.txt',
            lastModified: 1673349666,
            visibility: 'public',
        );
        
        $file = $fileFactory->createFileFromFileAttributes(
            attributes: $attributes,
            with: ['stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url', 'visibility'],
        );
        
        $this->assertInstanceof(FileInterface::class, $file);
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
}