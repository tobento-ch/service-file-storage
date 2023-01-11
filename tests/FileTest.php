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
use Tobento\Service\FileStorage\File;
use Tobento\Service\FileStorage\FileInterface;
use Psr\Http\Message\StreamInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * FileTest
 */
class FileTest extends TestCase
{
    public function testWithPathOnly()
    {
        $file = new File(path: 'folder/file.txt');
        
        $this->assertInstanceof(FileInterface::class, $file);
        $this->assertSame('folder/file.txt', $file->path());
        $this->assertSame('file.txt', $file->name());
        $this->assertSame('file', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('folder', $file->folderPath());
        $this->assertSame(null, $file->stream());
        $this->assertSame(null, $file->content());
        $this->assertSame(null, $file->mimeType());
        $this->assertSame(null, $file->size());
        $this->assertSame(null, $file->width());
        $this->assertSame(null, $file->height());
        $this->assertSame(null, $file->lastModified());
        $this->assertSame(null, $file->url());
        $this->assertSame(null, $file->visibility());
        $this->assertSame([], $file->metadata());
        $this->assertFalse($file->isHtmlImage());
    }
    
    public function testAllWithoutStream()
    {
        $file = new File(
            path: 'file.txt',
            stream: null,
            mimeType: 'text/plain',
            size: 200,
            width: 50,
            height: 30,
            lastModified: 1672822057,
            url: 'https::www.example.com/path',
            visibility: 'private',
            metadata: ['foo' => 'bar'],
        );
        
        $this->assertInstanceof(FileInterface::class, $file);
        $this->assertSame('file.txt', $file->path());
        $this->assertSame('file.txt', $file->name());
        $this->assertSame('file', $file->filename());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('', $file->folderPath());
        $this->assertSame(null, $file->stream());
        $this->assertSame(null, $file->content());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame(200, $file->size());
        $this->assertSame(50, $file->width());
        $this->assertSame(30, $file->height());
        $this->assertSame(1672822057, $file->lastModified());
        $this->assertSame('https::www.example.com/path', $file->url());
        $this->assertSame('private', $file->visibility());
        $this->assertSame(['foo' => 'bar'], $file->metadata());
        $this->assertFalse($file->isHtmlImage());
    }    
    
    public function testWithStream()
    {
        $file = new File(path: 'file.txt', stream: null);
        
        $this->assertSame(null, $file->stream());
        $this->assertSame(null, $file->content());
        $this->assertSame(null, $file->size());
        
        $stream = (new Psr17Factory())->createStream('content');
        $file = new File(path: 'file.txt', stream: $stream);
        
        $this->assertInstanceof(StreamInterface::class, $file->stream());
        $this->assertSame('content', $file->content());
        $this->assertSame(7, $file->size());
    }
    
    public function testIsHtmlImage()
    {
        $mimes = ['image/gif', 'image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        
        foreach($mimes as $type) {
            
            $file = new File(path: 'file.jpg', mimeType: $type);

            $this->assertTrue($file->isHtmlImage());            
        }
    }
}