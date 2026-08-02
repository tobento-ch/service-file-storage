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
use Tobento\Service\FileStorage\Repository\FolderRepository;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\Test\Flysystem\TestFileFactory;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryReadException;
use Tobento\Service\Repository\RepositoryUpdateException;

class FolderRepositoryTest extends TestCase
{
    protected string $tmp = __DIR__ . '/../tmp/repository-folder';
    
    protected function makeRepo(): FolderRepository
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

    protected function sorted(array $values, string $direction): array
    {
        $sorted = $values;

        if ($direction === 'asc') {
            sort($sorted);
        } else {
            rsort($sorted);
        }

        return $sorted;
    }
    
    public function testStorageMethods(): void
    {
        $repo = $this->makeRepo();
        $originalStorage = $repo->storage();

        $newStorage = clone $originalStorage;
        $newRepo = $repo->withStorage($newStorage);

        // getter returns original
        $this->assertSame($originalStorage, $repo->storage());

        // withStorage returns new instance with updated storage
        $this->assertNotSame($repo, $newRepo);
        $this->assertSame($newStorage, $newRepo->storage());
    }

    public function testRootFolderMethods(): void
    {
        $repo = $this->makeRepo();

        // default root folder
        $this->assertSame('', $repo->rootFolder());

        $newRepo = $repo->withRootFolder('animals');

        // original unchanged
        $this->assertSame('', $repo->rootFolder());

        // new instance updated
        $this->assertNotSame($repo, $newRepo);
        $this->assertSame('animals', $newRepo->rootFolder());
    }

    public function testRecursiveMethods(): void
    {
        $repo = $this->makeRepo();

        // default recursive flag
        $this->assertFalse($repo->recursive());

        $newRepo = $repo->withRecursive(true);

        // original unchanged
        $this->assertFalse($repo->recursive());

        // new instance updated
        $this->assertNotSame($repo, $newRepo);
        $this->assertTrue($newRepo->recursive());
    }

    public function testFindByIdReturnsFileFromRoot(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->findById('fruits');

        $this->assertNotNull($folder);
        $this->assertSame('fruits', $folder->path());
    }

    public function testFindByIdReturnsNullForMissingFile(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->findById('does-not-exist');

        $this->assertNull($folder);
    }

    public function testFindByIdInsideRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $folder = $repo->findById('cats');

        $this->assertNotNull($folder);
        $this->assertSame('animals/cats', $folder->path());
    }

    public function testFindByIdRootFolderIsolatesFiles(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $this->assertNotNull($repo->findById('cats'));
        $this->assertNull($repo->findById('fruits'));
    }

    public function testFindByIdSwitchingRootFolders(): void
    {
        $repo = $this->makeRepo();

        $animals = $repo->withRootFolder('animals');
        $vehicles = $repo->withRootFolder('vehicles');

        $this->assertNotNull($animals->findById('cats'));
        $this->assertNull($animals->findById('car'));

        $this->assertNotNull($vehicles->findById('cars'));
        $this->assertNull($vehicles->findById('lion'));
    }

    public function testFindByIdEmptyRootFolderUsesBaseFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('');

        $folder = $repo->findById('fruits');

        $this->assertNotNull($folder);
        $this->assertSame('fruits', $folder->path());
    }

    public function testFindByIdLoadsAllAttributes(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        $folder = $repo->findById('animals/cats');
        
        $this->assertSame('local', $folder->storageName());
        $this->assertSame('animals/cats', $folder->path());
        $this->assertSame('animals', $folder->parentPath());
        $this->assertSame('cats', $folder->name());
        $this->assertNotNull($folder->lastModified());
        $this->assertSame([], $folder->metadata());
    }
    
    public function testFindByIdsReturnsOnlyExistingFiles(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findByIds(
            'fruits',
            'does-not-exist',
            'animals'
        ));
        
        $this->assertCount(2, $results);
        $this->assertSame('animals', $results[0]->path());
        $this->assertSame('fruits', $results[1]->path());
    }

    public function testFindByIdsRespectsRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $results = iterator_to_array($repo->findByIds(
            'cats',
            'cars'
        ));

        $this->assertCount(1, $results);
        $this->assertSame('animals/cats', $results[0]->path());
    }

    public function testFindByIdsReturnsEmptyIterableWhenNoneFound(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findByIds(
            'missing-1',
            'missing-2'
        ));

        $this->assertSame([], $results);
    }
    
    public function testFindOneReturnsFirstMatchingFile(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->findOne([
            'path' => ['like' => 'fr%'],
        ]);

        $this->assertNotNull($folder);
        $this->assertSame('fruits', $folder->path());
    }

    public function testFindOneReturnsNullWhenNoMatch(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->findOne([
            'path' => ['like' => 'zzz%'],
        ]);

        $this->assertNull($folder);
    }

    public function testFindOneRespectsRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        // cats exists in animals/
        $folder = $repo->findOne([
            'path' => ['like' => '%ats'],
        ]);

        $this->assertNotNull($folder);
        $this->assertSame('animals/cats', $folder->path());

        // cars exists but NOT inside animals/
        $missing = $repo->findOne([
            'path' => ['like' => 'car%'],
        ]);

        $this->assertNull($missing);
    }

    public function testFindOneReturnsFirstResultFromFindAllOrder(): void
    {
        $repo = $this->makeRepo();

        // Suppose both fruits and animals match "%s"
        // animals should be returned first as alphabetically sorted by the underlying implm
        $folder = $repo->findOne([
            'name' => ['like' => '%s'],
        ]);

        $this->assertNotNull($folder);
        $this->assertSame('animals', $folder->path());
    }

    public function testFindOneWithMetadataFilterIsAlwaysEmpty(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->findOne([
            'metadata->author' => ['=' => 'tom'],
        ]);

        $this->assertNull($folder);
    }

    public function testFindOneWithEmptyWhereReturnsFirstFolder(): void
    {
        $repo = $this->makeRepo();

        // findAll([]) returns all files; first one should be apple.jpg
        $folder = $repo->findOne([]);

        $this->assertNotNull($folder);
        $this->assertSame('animals', $folder->path());
    }
    
    public function testFindAllReturnsAllFiles(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll();

        $this->assertGreaterThanOrEqual(3, count($folders));

        $paths = array_map(fn($f) => $f->path(), $folders);

        $this->assertContains('animals', $paths);
        $this->assertContains('fruits', $paths);
        $this->assertContains('vehicles', $paths);
    }

    public function testFindAllRespectsRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $folders = $repo->findAll();

        $paths = array_map(fn($f) => $f->path(), $folders);

        $this->assertContains('animals/cats', $paths);
        $this->assertContains('animals/dogs', $paths);

        // Should not include files outside animals/
        $this->assertNotContains('fruits', $paths);
        $this->assertNotContains('vehicles/cars', $paths);
    }

    public function testFindAllWithNameFilter(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll([
            'name' => ['like' => 'fr%'],
        ]);

        $this->assertCount(1, $folders);

        $names = array_map(fn($f) => $f->path(), $folders);
        
        $this->assertContains('fruits', $names);
    }

    public function testFindAllWithMetadataFilterReturnsNoneAsEmpty(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll([
            'metadata->author' => ['=' => 'anna'],
        ]);

        $this->assertCount(0, $folders);
    }

    public function testFindAllWithMultipleFilters(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll([
            'name' => ['like' => 'fr%'],
            'path' => ['=' => 'fruits'],
        ]);

        $this->assertCount(1, $folders);
        $this->assertSame('fruits', $folders[0]->path());
    }

    public function testFindAllOrderByAscending(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll(
            where: [],
            orderBy: ['name' => 'asc']
        );

        $names = array_map(fn($f) => $f->name(), $folders);

        $this->assertSame($names, $this->sorted($names, 'asc'));
    }

    public function testFindAllOrderByDescending(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll(
            where: [],
            orderBy: ['name' => 'desc']
        );

        $names = array_map(fn($f) => $f->name(), $folders);

        $this->assertSame($names, $this->sorted($names, 'desc'));
    }

    public function testFindAllWithLimit(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll(
            where: [],
            orderBy: ['name' => 'asc'],
            limit: 1
        );

        $this->assertCount(1, $folders);
    }

    public function testFindAllWithLimitAndOffset(): void
    {
        $repo = $this->makeRepo();

        // limit = [number, offset]
        $folders = $repo->findAll(
            where: [],
            orderBy: ['name' => 'asc'],
            limit: [1, 1]
        );

        $this->assertCount(1, $folders);

        // Get the full sorted list to compare
        $all = $repo->findAll(orderBy: ['name' => 'asc']);
        $expected = $all[1]->name();

        $this->assertSame($expected, $folders[0]->name());
    }
    
    public function testFindAllLoadsAllAttributes(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        // We only need apple.jpg from the result set
        $folders = $repo->findAll([
            'name' => ['=' => 'cats'],
        ]);

        $this->assertCount(1, $folders);

        $folder = $folders[0];

        $this->assertSame('local', $folder->storageName());
        $this->assertSame('animals/cats', $folder->path());
        $this->assertSame('animals', $folder->parentPath());
        $this->assertSame('cats', $folder->name());
        $this->assertNotNull($folder->lastModified());
        $this->assertSame([], $folder->metadata());
    }

    public function testFindAllIsNotRecursiveByDefault(): void
    {
        $repo = $this->makeRepo();

        $folders = $repo->findAll();

        $paths = array_map(fn($f) => $f->path(), $folders);

        // Only root-level folders should appear
        $this->assertContains('animals', $paths);
        $this->assertContains('fruits', $paths);
        $this->assertContains('vehicles', $paths);

        // Subfolders must NOT appear
        $this->assertNotContains('animals/cats', $paths);
        $this->assertNotContains('animals/dogs', $paths);
        $this->assertNotContains('vehicles/cars', $paths);
    }
    
    public function testFindAllWithRecursiveEnabled(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        $folders = $repo->findAll();

        $paths = array_map(fn($f) => $f->path(), $folders);

        // Root-level folders
        $this->assertContains('animals', $paths);
        $this->assertContains('fruits', $paths);
        $this->assertContains('vehicles', $paths);

        // Subfolders must now appear
        $this->assertContains('animals/cats', $paths);
        $this->assertContains('animals/dogs', $paths);
        $this->assertContains('vehicles/cars', $paths);
    }
    
    public function testCountReturnsTotalNumberOfFiles(): void
    {
        $repo = $this->makeRepo();

        $count = $repo->count();

        // Root-level folders only (non-recursive by default)
        $this->assertSame(3, $count);
    }

    public function testCountRespectsWhereFilter(): void
    {
        $repo = $this->makeRepo();

        $count = $repo->count([
            'name' => ['like' => 'fr%'],
        ]);

        // fruits
        $this->assertSame(1, $count);
    }

    public function testCountWithRecursiveEnabled(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        $count = $repo->count();

        $this->assertSame(7, $count);
    }
    
    public function testFindColumnReturnsValues(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('name');

        $this->assertSame(
            [
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

        $values = $repo->findColumn('name', limit: 2);

        $this->assertSame(
            [
                'animals',
                'fruits',
            ],
            $values
        );
    }

    public function testFindColumnWithLimitAndOffset(): void
    {
        $repo = $this->makeRepo();

        // limit = [count, offset]
        $values = $repo->findColumn('name', limit: [1, 1]);

        $this->assertSame(
            [
                'fruits',
            ],
            $values
        );
    }

    public function testFindColumnWithOrderByAscending(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('name', orderBy: ['name' => 'ASC']);

        $this->assertSame(
            [
                'animals',
                'fruits',
                'vehicles',
            ],
            $values
        );
    }

    public function testFindColumnWithOrderByDescending(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('name', orderBy: ['name' => 'DESC']);

        $this->assertSame(
            [
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

        $values = $repo->findColumn(column: 'name', key: 'path', orderBy: ['name' => 'DESC']);

        $this->assertSame(
            [
                'vehicles' => 'vehicles',
                'fruits' => 'fruits',
                'animals' => 'animals',
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
    
    public function testCreateCreatesFolder(): void
    {
        $repo = $this->makeRepo();

        $folder = $repo->create([
            'path' => 'temp-folder',
        ]);

        $this->assertSame('temp-folder', $folder->path());
        $this->assertNotNull($repo->findById('temp-folder'));

        // cleanup
        $repo->deleteById('temp-folder');
    }
    
    public function testCreateThrowsOnInvalidPath(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryCreateException::class);

        $repo->create([
            'path' => ['not-a-string'],
        ]);
    }

    public function testUpdateByIdThrowsUnsupportedException(): void
    {
        $this->expectException(RepositoryUpdateException::class);
        $this->expectExceptionMessage('Unsupported');

        $repo = $this->makeRepo();

        $repo->updateById(1, ['foo' => 'bar']);
    }

    public function testUpdateThrowsUnsupportedException(): void
    {
        $this->expectException(RepositoryUpdateException::class);
        $this->expectExceptionMessage('Unsupported');

        $repo = $this->makeRepo();

        $repo->update(['id' => ['=' => 1]], ['foo' => 'bar']);
    }

    public function testDeleteByIdDeletesFolder(): void
    {
        $repo = $this->makeRepo();

        // create temp folder
        $repo->storage()->createFolder('temp-delete');

        $this->assertNotNull($repo->findById('temp-delete'));

        $deleted = $repo->deleteById('temp-delete');

        $this->assertSame('temp-delete', $deleted->path());
        $this->assertNull($repo->findById('temp-delete'));
    }
    
    public function testDeleteByIdThrowsIfMissing(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryDeleteException::class);

        $repo->deleteById('missing-folder');
    }

    public function testDeleteDeletesMultipleFolders(): void
    {
        $repo = $this->makeRepo();

        // create temp folders
        $repo->storage()->createFolder('tempA');
        $repo->storage()->createFolder('tempAA');
        $repo->storage()->createFolder('tempB');

        // delete folders starting with "tempA"
        $deleted = iterator_to_array($repo->delete([
            'path' => ['like' => 'tempA%'],
        ]));

        $deletedPaths = array_map(fn($f) => $f->path(), $deleted);

        $this->assertCount(2, $deleted);
        $this->assertContains('tempA', $deletedPaths);
        $this->assertContains('tempAA', $deletedPaths);

        // ensure they are gone
        $this->assertNull($repo->findById('tempA'));
        $this->assertNull($repo->findById('tempAA'));

        // cleanup leftover
        $repo->deleteById('tempB');
    }
    
    public function testDeleteReturnsEmptyIfNoMatch(): void
    {
        $repo = $this->makeRepo();

        $deleted = iterator_to_array($repo->delete([
            'path' => ['like' => 'nope%'],
        ]));

        $this->assertSame([], $deleted);
    }
    
    public function testAttributeAliasesWorkForWhere(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'folder_path' => 'path',
        ]);

        $folders = $repo->findAll(where: ['folder_path' => 'animals']);

        $this->assertCount(1, $folders);
        $this->assertSame('animals', $folders[0]->path());
    }

    public function testAttributeAliasesWorkForOrderBy(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'folder_name' => 'name',
        ]);

        $folders = $repo->findAll(orderBy: ['folder_name' => 'ASC']);

        $names = array_map(fn($f) => $f->name(), $folders);

        $this->assertSame(['animals', 'fruits', 'vehicles'], $names);
    }

    public function testFindColumnUsesAttributeAliases(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'folder_name' => 'name',
        ]);

        $values = $repo->findColumn('folder_name');

        $this->assertSame(
            ['animals', 'fruits', 'vehicles'],
            $values
        );
    }

    public function testFindColumnWithKeyUsesAttributeAliases(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'folder_name' => 'name',
            'folder_path' => 'path',
        ]);

        $values = $repo->findColumn(column: 'folder_name', key: 'folder_path');

        $this->assertSame(
            [
                'animals'  => 'animals',
                'fruits'   => 'fruits',
                'vehicles' => 'vehicles',
            ],
            $values
        );
    }

    public function testCreateDoesNotSupportAttributeAliases(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'folder_path' => 'path',
        ]);

        $this->expectException(RepositoryCreateException::class);

        $repo->create(['folder_path' => 'new-folder']);
    }
}