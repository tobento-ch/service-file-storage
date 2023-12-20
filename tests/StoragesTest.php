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
use Tobento\Service\FileStorage\Storages;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\FileStorage\StorageException;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\StorageInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * StoragesTest
 */
class StoragesTest extends TestCase
{
    protected function createStorage(string $name): StorageInterface
    {
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: __DIR__.'/tmp/',
            ),
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
    
    public function testCreateStorages()
    {
        $storage = $this->createStorage('private');
        $storages = new Storages($storage);
        
        $this->assertInstanceOf(StoragesInterface::class, $storages);
        $this->assertSame($storage, $storages->get('private'));
        $this->assertTrue($storages->has('private'));
    }
    
    public function testAddMethod()
    {
        $storages = new Storages();
        
        $this->assertFalse($storages->has('private'));
        
        $storages->add($this->createStorage('private'));
            
        $this->assertTrue($storages->has('private'));
        $this->assertSame('private', $storages->get('private')->name());
    }
    
    public function testRegisterMethod()
    {
        $storages = new Storages();
        
        $this->assertFalse($storages->has('private'));
        
        $storages->register(
            'private',
            function(string $name): StorageInterface {        
                return $this->createStorage('private');
            }
        );
            
        $this->assertTrue($storages->has('private'));
        $this->assertSame('private', $storages->get('private')->name());
    }

    public function testGetMethodThrowsStorageExceptionIfNotExist()
    {
        $this->expectException(StorageException::class);
        
        $storages = new Storages();
        
        $storages->get('name'); 
    }
    
    public function testHasMethod()
    {
        $storages = new Storages();
        
        $this->assertFalse($storages->has('private'));
        
        $storages->add($this->createStorage('private'));
            
        $this->assertTrue($storages->has('private'));
        
        $this->assertFalse($storages->has('foo')); 
    }
    
    public function testAddDefaultMethod()
    {
        $storages = new Storages();
        
        $storages->addDefault(name: 'primary', storage: 'private');
            
        $this->assertTrue(true);
    }
    
    public function testDefaultMethod()
    {
        $storages = new Storages();
        
        $storage = $this->createStorage('private');
        
        $storages->add($storage);
        
        $storages->addDefault(name: 'primary', storage: 'private');
            
        $this->assertSame(
            $storage,
            $storages->default('primary')
        );
    } 
    
    public function testDefaultMethodThrowsStorageExceptionIfNotExist()
    {
        $this->expectException(StorageException::class);
        
        $storages = new Storages();

        $storages->default('primary');
    }
    
    public function testHasDefaultMethod()
    {
        $storages = new Storages();
        
        $storage = $this->createStorage('private');
        
        $storages->add($storage);
        
        $storages->addDefault(name: 'primary', storage: 'private');
        
        $this->assertTrue($storages->hasDefault('primary'));
        
        $this->assertFalse($storages->hasDefault('foo')); 
    }
    
    public function testGetDefaultsMethod()
    {
        $storages = new Storages();
        
        $storages->addDefault(name: 'primary', storage: 'private');
        $storages->addDefault(name: 'secondary', storage: 'public');
        
        $this->assertSame(
            [
                'primary' => 'private',
                'secondary' => 'public',
            ],
            $storages->getDefaults()
        );
    }
    
    public function testNamesMethod()
    {
        $this->assertSame([], (new Storages())->names());
        
        $this->assertSame(
            ['foo', 'bar'],
            (new Storages($this->createStorage('foo'), $this->createStorage('bar')))->names()
        );
    }
}