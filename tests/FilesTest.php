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
use Tobento\Service\FileStorage\Files;
use Tobento\Service\FileStorage\FilesInterface;
use Tobento\Service\FileStorage\File;
use Tobento\Service\FileStorage\FileInterface;

/**
 * FilesTest
 */
class FilesTest extends TestCase
{
    public function testCreateFiles()
    {
        $files = new Files([
            new File(path: 'file.txt')
        ]);
        
        $this->assertInstanceof(FilesInterface::class, $files);
    }
    
    public function testFilterMethod()
    {
        $files = new Files([
            new File(path: 'file.txt', mimeType: 'text/plain'),
            new File(path: 'image.jpg', mimeType: 'image/jpeg'),
        ]);
        
        $filesNew = $files->filter(
            fn(FileInterface $f): bool => in_array($f->mimeType(), ['image/jpeg'])
        );
        
        $this->assertFalse($files === $filesNew);
        $this->assertSame(1, count($filesNew->all()));
    }
    
    public function testSortMethod()
    {
        $files = new Files([
            new File(path: 'file.txt'),
            new File(path: 'bar.txt'),
            new File(path: 'image.jpg'),
        ]);
        
        $paths = [];
        
        foreach($files as $file) {
            $paths[] = $file->path();
        }
        
        $this->assertSame(
            ['file.txt', 'bar.txt', 'image.jpg'],
            $paths
        );
        
        $filesNew = $files->sort(
            fn(FileInterface $a, FileInterface $b) => $a->path() <=> $b->path()
        );
        
        $this->assertFalse($files === $filesNew);
        
        $paths = [];
        
        foreach($filesNew as $file) {
            $paths[] = $file->path();
        }
        
        $this->assertSame(
            ['bar.txt', 'file.txt', 'image.jpg'],
            $paths
        );        
    }
    
    public function testAllMethod()
    {
        $files = new Files([
            new File(path: 'file.txt'),
            new File(path: 'image.jpg'),
        ]);
        
        $this->assertSame(2, count($files->all()));
    }
    
    public function testIteration()
    {
        $files = new Files([
            new File(path: 'file.txt'),
            new File(path: 'image.jpg'),
        ]);
        
        foreach($files as $file) {
            $this->assertInstanceof(FileInterface::class, $file);
        }
    }
}