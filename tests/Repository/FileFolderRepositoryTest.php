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

namespace Tobento\Service\FileStorage\Test\Repository;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Tobento\Service\FileStorage\Repository\FileFolderRepository;
use Tobento\Service\FileStorage\Repository\FileRepository;
use Tobento\Service\FileStorage\Repository\FolderRepository;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\Test\Flysystem\TestFileFactory;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryReadException;
use Tobento\Service\Repository\RepositoryUpdateException;

class FileFolderRepositoryTest extends TestCase
{
    protected string $tmp = __DIR__ . '/../tmp/repository-file-folder';
    
    protected function makeRepo(): FileFolderRepository
    {
        return new FileFolderRepository($this->makeFileRepo(), $this->makeFolderRepo());
    }
    
    protected function makeFileRepo(): FileRepository
    {
        // 1. Create Flysystem instance
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: $this->tmp,
            ),
            config: ['public_url' => 'https://www.example.com/path'],
        );

        // 2. Create real Flysystem FileFactory
        $realFactory = new Flysystem\FileFactory(
            flysystem: $filesystem,
            streamFactory: new Psr17Factory()
        );

        // 3. Wrap it with TestFileFactory to inject metadata
        $testFactory = new TestFileFactory(
            inner: $realFactory,
            metadataByPath: [
                'apple.jpg'   => ['author' => 'tom',  'tags' => ['red', 'fruit']],
                'banana.png'  => ['author' => 'anna', 'tags' => ['yellow']],
                'carrot.jpg'  => ['author' => 'tom',  'tags' => ['orange']],
                'apricot.jpg' => ['author' => 'john'],
            ]
        );

        // 4. Create storage using the custom factory
        $storage = new Flysystem\Storage(
            name: 'local',
            flysystem: $filesystem,
            fileFactory: $testFactory,
            type: 'private',
        );

        // 5. Write real files to disk using storage->write()
        // Root-level files
        $storage->write('apple.jpg', $this->jpegString());
        $storage->write('banana.png', str_repeat('B', 2));
        $storage->write('carrot.jpg', str_repeat('C', 3));
        $storage->write('apricot.jpg', str_repeat('D', 4));

        // Subfolder: animals
        $storage->write('animals/lion.jpg', 'L');   // size 1
        $storage->write('animals/tiger.jpg', 'TT');  // size 2

        // Subfolder: vehicles
        $storage->write('vehicles/car.jpg', 'CAR'); // size 3
        $storage->write('vehicles/bike.jpg', 'BIKE'); // size 4
        
        // 6. Return repository
        return new FileRepository(storage: $storage);
    }
    
    protected function makeFolderRepo(): FolderRepository
    {
        // 1. Create Flysystem instance
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: $this->tmp,
            ),
            config: ['public_url' => 'https://www.example.com/path'],
        );

        // 2. Create real Flysystem FileFactory
        $realFactory = new Flysystem\FileFactory(
            flysystem: $filesystem,
            streamFactory: new Psr17Factory()
        );

        // 3. Wrap it with TestFileFactory to inject metadata
        $testFactory = new TestFileFactory(
            inner: $realFactory,
            metadataByPath: [],
        );

        // 4. Create storage using the custom factory
        $storage = new Flysystem\Storage(
            name: 'local',
            flysystem: $filesystem,
            fileFactory: $testFactory,
            type: 'private',
        );

        // 5. Create folders
        // Root-level folders
        $storage->createFolder(path: 'fruits');
        $storage->createFolder(path: 'animals');
        $storage->createFolder(path: 'vehicles');

        // Subfolders: animals
        $storage->createFolder(path: 'animals/cats');
        $storage->createFolder(path: 'animals/dogs');

        // Subfolders: vehicles
        $storage->createFolder(path: 'vehicles/cars');
        $storage->createFolder(path: 'vehicles/bikes');
        
        // 6. Return repository
        return new FolderRepository(storage: $storage);
    }
    
    protected function jpegString(): string
    {
        $path = __DIR__.'/../src/image.jpg';

        return file_get_contents($path);
    }
    
    public function testFindByIdReturnsFileOrFolder(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->findById('apple.jpg');
        $this->assertNotNull($file);
        $this->assertSame('apple.jpg', $file->path());

        $folder = $repo->findById('animals');
        $this->assertNotNull($folder);
        $this->assertSame('animals', $folder->path());
    }

    public function testFindByIdReturnsNullIfNotFound(): void
    {
        $repo = $this->makeRepo();

        $this->assertNull($repo->findById('does-not-exist'));
    }

    public function testFindByIdsMergesResults(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findByIds(
            'apple.jpg',
            'animals',
            'does-not-exist'
        ));

        $paths = array_map(fn($e) => $e->path(), $results);

        $this->assertContains('apple.jpg', $paths);
        $this->assertContains('animals', $paths);
        $this->assertCount(2, $results);
    }

    public function testFindOneReturnsFileFirstIfBothMatch(): void
    {
        $repo = $this->makeRepo();

        // both apple.jpg and animals match "%a%"
        $result = $repo->findOne([
            'path' => ['like' => '%a%'],
        ]);

        // FileRepository is checked first
        $this->assertSame('apple.jpg', $result->path());
    }

    public function testFindAllMergesFilesAndFolders(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findAll());

        $paths = array_map(fn($e) => $e->path(), $results);

        $this->assertContains('apple.jpg', $paths);
        $this->assertContains('animals', $paths);
        $this->assertGreaterThan(2, count($results));
    }

    public function testFindAllRespectsLimit(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findAll([], [], 2));

        $this->assertCount(2, $results);
    }

    public function testCountReturnsSumOfFileAndFolderCounts(): void
    {
        $repo = $this->makeRepo();

        $this->assertSame(
            $repo->fileRepository()->count() + $repo->folderRepository()->count(),
            $repo->count()
        );
    }

    public function testWithRootFolderDelegatesToBothRepos(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $fileRepo = $repo->fileRepository();
        $folderRepo = $repo->folderRepository();

        $this->assertSame('animals', $fileRepo->rootFolder());
        $this->assertSame('animals', $folderRepo->rootFolder());
    }

    public function testWithRecursiveDelegatesToBothRepos(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        $this->assertTrue($repo->fileRepository()->recursive());
        $this->assertTrue($repo->folderRepository()->recursive());
    }

    public function testUnsupportedCreateThrows(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryCreateException::class);
        $repo->create([]);
    }

    public function testUnsupportedUpdateThrows(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryUpdateException::class);
        $repo->updateById('id', []);
    }

    public function testUnsupportedDeleteThrows(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryDeleteException::class);
        $repo->deleteById('id');
    }

    public function testUnsupportedFindColumnThrows(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryReadException::class);
        $repo->findColumn('path');
    }
}