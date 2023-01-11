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
use Tobento\Service\FileStorage\Folders;
use Tobento\Service\FileStorage\FoldersInterface;
use Tobento\Service\FileStorage\Folder;
use Tobento\Service\FileStorage\FolderInterface;

/**
 * FoldersTest
 */
class FoldersTest extends TestCase
{
    public function testCreateFolders()
    {
        $folders = new Folders([
            new Folder(path: 'folder')
        ]);
        
        $this->assertInstanceof(FoldersInterface::class, $folders);
    }
    
    public function testFilterMethod()
    {
        $folders = new Folders([
            new Folder(path: 'file', visibility: 'public'),
            new Folder(path: 'image', visibility: 'private'),
        ]);
        
        $foldersNew = $folders->filter(
            fn(FolderInterface $f): bool => in_array($f->visibility(), ['private'])
        );
        
        $this->assertFalse($folders === $foldersNew);
        $this->assertSame(1, count($foldersNew->all()));
    }
    
    public function testSortMethod()
    {
        $folders = new Folders([
            new Folder(path: 'file'),
            new Folder(path: 'bar'),
            new Folder(path: 'image'),
        ]);
        
        $paths = [];
        
        foreach($folders as $folder) {
            $paths[] = $folder->path();
        }
        
        $this->assertSame(
            ['file', 'bar', 'image'],
            $paths
        );
        
        $foldersNew = $folders->sort(
            fn(FolderInterface $a, FolderInterface $b) => $a->path() <=> $b->path()
        );
        
        $this->assertFalse($folders === $foldersNew);
        
        $paths = [];
        
        foreach($foldersNew as $folder) {
            $paths[] = $folder->path();
        }
        
        $this->assertSame(
            ['bar', 'file', 'image'],
            $paths
        );        
    }    
    
    public function testAllMethod()
    {
        $folders = new Folders([
            new Folder(path: 'file'),
            new Folder(path: 'image'),
        ]);
        
        $this->assertSame(2, count($folders->all()));
    }
    
    public function testIteration()
    {
        $folders = new Folders([
            new Folder(path: 'file'),
            new Folder(path: 'image'),
        ]);
        
        foreach($folders as $folder) {
            $this->assertInstanceof(FolderInterface::class, $folder);
        }
    }
}