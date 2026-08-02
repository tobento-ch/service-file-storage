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
    
    public function testCreateFileExplicitType(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->create([
            'type' => 'file',
            'path' => 'temp-file.txt',
            'content' => 'ABC',
        ]);

        $this->assertSame('temp-file.txt', $file->path());
        $this->assertNotNull($repo->findById('temp-file.txt'));

        // cleanup
        $repo->deleteById('temp-file.txt');
    }
    
    public function testCreateFolderExplicitType(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->create([
            'type' => 'folder',
            'path' => 'temp-folder',
        ]);

        $this->assertSame('temp-folder', $folder->path());
        $this->assertNotNull($repo->findById('temp-folder'));

        // cleanup
        $repo->deleteById('temp-folder');
    }

    public function testCreateInfersFileFromContent(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->create([
            'path' => 'temp-content.txt',
            'content' => 'XYZ',
        ]);

        $this->assertSame('temp-content.txt', $file->path());

        // cleanup
        $repo->deleteById('temp-content.txt');
    }
    
    public function testCreateInfersFileFromExtension(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->create([
            'path' => 'temp-image.jpg',
            'content' => 'IMG',
        ]);

        $this->assertSame('temp-image.jpg', $file->path());

        // cleanup
        $repo->deleteById('temp-image.jpg');
    }

    public function testCreateDefaultsToFolder(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->create([
            'path' => 'temp-default-folder',
        ]);

        $this->assertSame('temp-default-folder', $folder->path());

        // cleanup
        $repo->deleteById('temp-default-folder');
    }
    
    public function testUnsupportedUpdateThrows(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryUpdateException::class);
        $repo->updateById('id', []);
    }

    public function testDeleteByIdDeletesFile(): void
    {
        $repo = $this->makeRepo();

        // create temp file
        $repo->fileRepository()->storage()->write('temp-file.txt', 'ABC');

        $this->assertNotNull($repo->findById('temp-file.txt'));

        $deleted = $repo->deleteById('temp-file.txt');

        $this->assertSame('temp-file.txt', $deleted->path());
        $this->assertNull($repo->findById('temp-file.txt'));
    }
    
    public function testDeleteByIdDeletesFolder(): void
    {
        $repo = $this->makeRepo();

        // create temp folder
        $repo->folderRepository()->storage()->createFolder('temp-folder');

        $this->assertNotNull($repo->findById('temp-folder'));

        $deleted = $repo->deleteById('temp-folder');

        $this->assertSame('temp-folder', $deleted->path());
        $this->assertNull($repo->findById('temp-folder'));
    }
    
    public function testDeleteByIdThrowsIfMissing(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryDeleteException::class);

        $repo->deleteById('does-not-exist');
    }
    
    public function testDeleteDeletesFilesAndFolders(): void
    {
        $repo = $this->makeRepo();

        // create temp file + folder
        $repo->fileRepository()->storage()->write('tempA.txt', 'AAA');
        $repo->folderRepository()->storage()->createFolder('tempA-folder');

        // create non-matching items
        $repo->fileRepository()->storage()->write('tempB.txt', 'BBB');
        $repo->folderRepository()->storage()->createFolder('tempB-folder');

        // delete everything starting with "tempA"
        $deleted = iterator_to_array($repo->delete([
            'path' => ['like' => 'tempA%'],
        ]));

        $deletedPaths = array_map(fn($e) => $e->path(), $deleted);

        $this->assertCount(2, $deleted);
        $this->assertContains('tempA.txt', $deletedPaths);
        $this->assertContains('tempA-folder', $deletedPaths);

        // ensure they are gone
        $this->assertNull($repo->findById('tempA.txt'));
        $this->assertNull($repo->findById('tempA-folder'));

        // cleanup leftovers
        $repo->deleteById('tempB.txt');
        $repo->deleteById('tempB-folder');
    }
    
    public function testDeleteReturnsEmptyIfNoMatch(): void
    {
        $repo = $this->makeRepo();

        $deleted = iterator_to_array($repo->delete([
            'path' => ['like' => 'nope%'],
        ]));

        $this->assertSame([], $deleted);
    }
    
    public function testFindColumnReturnsValues(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('name');

        $this->assertSame(
            [
                // files (filename)
                'apple.jpg',
                'apricot.jpg',
                'banana.png',
                'carrot.jpg',

                // folders (name)
                'animals',
                'fruits',
                'vehicles',
            ],
            $values
        );
    }

    public function testFindColumnWithKey(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn(column: 'name', key: 'path');

        $this->assertSame(
            [
                // files
                'apple.jpg' => 'apple.jpg',
                'apricot.jpg' => 'apricot.jpg',
                'banana.png' => 'banana.png',
                'carrot.jpg' => 'carrot.jpg',

                // folders
                'animals' => 'animals',
                'fruits' => 'fruits',
                'vehicles' => 'vehicles',
            ],
            $values
        );
    }

    public function testFindColumnWithLimit(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('name', limit: 3);

        $this->assertSame(
            [
                'apple.jpg',
                'apricot.jpg',
                'banana.png',
            ],
            $values
        );
    }

    public function testFindColumnWithKeyAndLimit(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn(column: 'name', key: 'path', limit: 4);

        $this->assertSame(
            [
                'apple.jpg' => 'apple.jpg',
                'apricot.jpg' => 'apricot.jpg',
                'banana.png' => 'banana.png',
                'carrot.jpg' => 'carrot.jpg',
            ],
            $values
        );
    }

    public function testFindColumnWithOrderBy(): void
    {
        $repo = $this->makeRepo();

        // Order by name descending
        $values = $repo->findColumn('name', orderBy: ['name' => 'DESC']);

        // DESC ordering applied inside each repo before merge
        $this->assertSame(
            [
                // files DESC
                'carrot.jpg',
                'banana.png',
                'apricot.jpg',
                'apple.jpg',

                // folders DESC
                'vehicles',
                'fruits',
                'animals',
            ],
            $values
        );
    }

    public function testFindColumnWithKeyAndOrderBy(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn(
            column: 'name',
            key: 'path',
            orderBy: ['name' => 'ASC']
        );

        $this->assertSame(
            [
                // files ASC
                'apple.jpg' => 'apple.jpg',
                'apricot.jpg' => 'apricot.jpg',
                'banana.png' => 'banana.png',
                'carrot.jpg' => 'carrot.jpg',

                // folders ASC
                'animals' => 'animals',
                'fruits' => 'fruits',
                'vehicles' => 'vehicles',
            ],
            $values
        );
    }
    
    public function testFindColumnMissingColumnReturnsEmpty(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('nonexistent');

        $this->assertSame([], $values);
    }
}