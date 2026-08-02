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
use Tobento\Service\FileStorage\Repository\FileRepository;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\Test\Flysystem\TestFileFactory;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryReadException;
use Tobento\Service\Repository\RepositoryUpdateException;

class FileRepositoryTest extends TestCase
{
    protected string $tmp = __DIR__ . '/../tmp/repository';
    
    protected function makeRepo(): FileRepository
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
    
    protected function jpegString(): string
    {
        $path = __DIR__.'/../src/image.jpg';

        return file_get_contents($path);
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

    public function testFileAttributeMethods(): void
    {
        $repo = $this->makeRepo();

        // default attributes (all enabled)
        $this->assertSame(
            ['stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url'],
            $repo->fileAttributes()
        );

        $newRepo = $repo->withFileAttributes('size', 'mimeType');

        // original unchanged
        $this->assertSame(
            ['stream', 'mimeType', 'size', 'width', 'height', 'lastModified', 'url'],
            $repo->fileAttributes()
        );

        // new instance updated
        $this->assertNotSame($repo, $newRepo);
        $this->assertSame(['size', 'mimeType'], $newRepo->fileAttributes());
    }
    
    public function testAttributeAliasMethods(): void
    {
        $repo = $this->makeRepo();

        // default: no aliases
        $this->assertSame([], $repo->attributeAliases());

        // add aliases
        $newRepo = $repo->withAttributeAliases([
            'created_at' => 'lastModified',
            'file_name'  => 'filename',
        ]);

        // original unchanged
        $this->assertSame([], $repo->attributeAliases());

        // new instance updated
        $this->assertNotSame($repo, $newRepo);
        $this->assertSame([
            'created_at' => 'lastModified',
            'file_name'  => 'filename',
        ], $newRepo->attributeAliases());
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

        $file = $repo->findById('apple.jpg');

        $this->assertNotNull($file);
        $this->assertSame('apple.jpg', $file->path());
        $this->assertSame(20042, $file->size());
    }

    public function testFindByIdReturnsNullForMissingFile(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->findById('does-not-exist.jpg');

        $this->assertNull($file);
    }

    public function testFindByIdInsideRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $file = $repo->findById('lion.jpg');

        $this->assertNotNull($file);
        $this->assertSame('animals/lion.jpg', $file->path());
        $this->assertSame(1, $file->size());
    }

    public function testFindByIdRootFolderIsolatesFiles(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $this->assertNotNull($repo->findById('tiger.jpg'));
        $this->assertNull($repo->findById('car.jpg'));
    }

    public function testFindByIdSwitchingRootFolders(): void
    {
        $repo = $this->makeRepo();

        $animals = $repo->withRootFolder('animals');
        $vehicles = $repo->withRootFolder('vehicles');

        $this->assertNotNull($animals->findById('lion.jpg'));
        $this->assertNull($animals->findById('car.jpg'));

        $this->assertNotNull($vehicles->findById('car.jpg'));
        $this->assertNull($vehicles->findById('lion.jpg'));
    }

    public function testFindByIdEmptyRootFolderUsesBaseFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('');

        $file = $repo->findById('banana.png');

        $this->assertNotNull($file);
        $this->assertSame('banana.png', $file->path());
    }

    public function testFindByIdLoadsAllAttributesByDefault(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->findById('apple.jpg');
        
        $this->assertSame('local', $file->storageName());
        $this->assertSame('apple.jpg', $file->path());
        $this->assertSame(20042, $file->size());
        $this->assertSame(200, $file->width());
        $this->assertSame(150, $file->height());
        $this->assertSame('image/jpeg', $file->mimeType());
        $this->assertNotNull($file->lastModified());
        $this->assertSame('https://www.example.com/path/apple.jpg', $file->url());
        $this->assertSame('tom', $file->metadata()['author']);
        $this->assertSame(['red', 'fruit'], $file->metadata()['tags']);
        $this->assertNotNull($file->stream());
    }

    public function testFindByIdReturnsOnlyRequestedFileAttributes(): void
    {
        $repo = $this->makeRepo()->withFileAttributes('lastModified');

        $file = $repo->findById('apple.jpg');

        $this->assertNotNull($file->lastModified());

        $this->assertNull($file->stream());
    }
    
    public function testFindByIdsReturnsOnlyExistingFiles(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findByIds(
            'apple.jpg',
            'does-not-exist.jpg',
            'banana.png'
        ));

        $this->assertCount(2, $results);
        $this->assertSame('apple.jpg', $results[0]->path());
        $this->assertSame('banana.png', $results[1]->path());
    }

    public function testFindByIdsRespectsRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $results = iterator_to_array($repo->findByIds(
            'lion.jpg',
            'car.jpg'
        ));

        $this->assertCount(1, $results);
        $this->assertSame('animals/lion.jpg', $results[0]->path());
    }

    public function testFindByIdsReturnsEmptyIterableWhenNoneFound(): void
    {
        $repo = $this->makeRepo();

        $results = iterator_to_array($repo->findByIds(
            'missing-1.jpg',
            'missing-2.jpg'
        ));

        $this->assertSame([], $results);
    }
    
    public function testFindOneReturnsFirstMatchingFile(): void
    {
        $repo = $this->makeRepo();

        // Example: find first file whose filename starts with "ap"
        $file = $repo->findOne([
            'filename' => ['like' => 'ap%'],
        ]);

        $this->assertNotNull($file);
        $this->assertSame('apple.jpg', $file->path());
    }

    public function testFindOneReturnsNullWhenNoMatch(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->findOne([
            'filename' => ['like' => 'zzz%'],
        ]);

        $this->assertNull($file);
    }

    public function testFindOneRespectsRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        // lion.jpg exists in animals/
        $file = $repo->findOne([
            'filename' => ['like' => 'lion%'],
        ]);

        $this->assertNotNull($file);
        $this->assertSame('animals/lion.jpg', $file->path());

        // car.jpg exists but NOT inside animals/
        $missing = $repo->findOne([
            'filename' => ['like' => 'car%'],
        ]);

        $this->assertNull($missing);
    }

    public function testFindOneReturnsFirstResultFromFindAllOrder(): void
    {
        $repo = $this->makeRepo();

        // Suppose both apple.jpg and apricot.jpg match "a%"
        // apple.jpg should be returned first
        $file = $repo->findOne([
            'filename' => ['like' => 'a%'],
        ]);

        $this->assertNotNull($file);
        $this->assertSame('apple.jpg', $file->path());
    }

    public function testFindOneWithMetadataFilter(): void
    {
        $repo = $this->makeRepo();

        // Example: find first file with width > 100
        $file = $repo->findOne([
            'metadata->author' => ['=' => 'tom'],
        ]);

        $this->assertNotNull($file);
        $this->assertSame('apple.jpg', $file->path());
    }

    public function testFindOneWithEmptyWhereReturnsFirstFile(): void
    {
        $repo = $this->makeRepo();

        // findAll([]) returns all files; first one should be apple.jpg
        $file = $repo->findOne([]);

        $this->assertNotNull($file);
        $this->assertSame('apple.jpg', $file->path());
    }
    
    public function testFindAllReturnsAllFiles(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll();

        // apple.jpg, banana.png, animals/lion.jpg, animals/tiger.jpg, vehicles/car.jpg, ...
        $this->assertGreaterThanOrEqual(3, count($files));

        $paths = array_map(fn($f) => $f->path(), $files);

        $this->assertContains('apple.jpg', $paths);
        $this->assertContains('banana.png', $paths);
    }

    public function testFindAllRespectsRootFolder(): void
    {
        $repo = $this->makeRepo()->withRootFolder('animals');

        $files = $repo->findAll();

        $paths = array_map(fn($f) => $f->path(), $files);

        $this->assertContains('animals/lion.jpg', $paths);
        $this->assertContains('animals/tiger.jpg', $paths);

        // Should not include files outside animals/
        $this->assertNotContains('apple.jpg', $paths);
        $this->assertNotContains('vehicles/car.jpg', $paths);
    }

    public function testFindAllWithFilenameFilter(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll([
            'filename' => ['like' => 'ap%'],
        ]);

        $this->assertCount(2, $files);

        $names = array_map(fn($f) => $f->path(), $files);
        
        $this->assertContains('apple.jpg', $names);
        $this->assertContains('apricot.jpg', $names);
    }

    public function testFindAllWithMetadataFilter(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll([
            'metadata->author' => ['=' => 'anna'],
        ]);

        $this->assertCount(1, $files);
        $this->assertSame('banana.png', $files[0]->path());
    }

    public function testFindAllWithMultipleFilters(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll([
            'filename' => ['like' => 'ap%'],
            'metadata->author' => ['=' => 'tom'],
        ]);

        $this->assertCount(1, $files);
        $this->assertSame('apple.jpg', $files[0]->path());
    }

    public function testFindAllOrderByAscending(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll(
            where: [],
            orderBy: ['filename' => 'asc']
        );

        $names = array_map(fn($f) => $f->filename(), $files);

        $this->assertSame($names, $this->sorted($names, 'asc'));
    }

    public function testFindAllOrderByDescending(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll(
            where: [],
            orderBy: ['filename' => 'desc']
        );

        $names = array_map(fn($f) => $f->filename(), $files);

        $this->assertSame($names, $this->sorted($names, 'desc'));
    }

    public function testFindAllWithLimit(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll(
            where: [],
            orderBy: ['filename' => 'asc'],
            limit: 1
        );

        $this->assertCount(1, $files);
    }

    public function testFindAllWithLimitAndOffset(): void
    {
        $repo = $this->makeRepo();

        // limit = [number, offset]
        $files = $repo->findAll(
            where: [],
            orderBy: ['filename' => 'asc'],
            limit: [1, 1]
        );

        $this->assertCount(1, $files);

        // Get the full sorted list to compare
        $all = $repo->findAll(orderBy: ['filename' => 'asc']);
        $expected = $all[1]->filename();

        $this->assertSame($expected, $files[0]->filename());
    }
    
    public function testFindAllLoadsAllAttributesByDefault(): void
    {
        $repo = $this->makeRepo();

        // We only need apple.jpg from the result set
        $files = $repo->findAll([
            'filename' => ['=' => 'apple'],
        ]);

        $this->assertCount(1, $files);

        $file = $files[0];

        $this->assertSame('local', $file->storageName());
        $this->assertSame('apple.jpg', $file->path());
        $this->assertSame(20042, $file->size());
        $this->assertSame(200, $file->width());
        $this->assertSame(150, $file->height());
        $this->assertSame('image/jpeg', $file->mimeType());
        $this->assertNotNull($file->lastModified());
        $this->assertSame('https://www.example.com/path/apple.jpg', $file->url());
        $this->assertSame('tom', $file->metadata()['author']);
        $this->assertSame(['red', 'fruit'], $file->metadata()['tags']);
        $this->assertNotNull($file->stream());
    }
    
    public function testFindAllReturnsOnlyRequestedFileAttributes(): void
    {
        $repo = $this->makeRepo()->withFileAttributes('lastModified');

        $files = $repo->findAll([
            'filename' => ['=' => 'apple'],
        ]);

        $this->assertCount(1, $files);

        $file = $files[0];

        $this->assertNotNull($file->lastModified());
        $this->assertNull($file->stream());
    }
    
    public function testFindAllIsNotRecursiveByDefault(): void
    {
        $repo = $this->makeRepo();

        $files = $repo->findAll();

        $paths = array_map(fn($f) => $f->path(), $files);

        // Only root-level files should appear
        $this->assertContains('apple.jpg', $paths);
        $this->assertContains('banana.png', $paths);
        $this->assertContains('carrot.jpg', $paths);

        // Subfolder files must NOT appear
        $this->assertNotContains('animals/lion.jpg', $paths);
        $this->assertNotContains('animals/tiger.jpg', $paths);
        $this->assertNotContains('vehicles/car.jpg', $paths);
    }
    
    public function testFindAllWithRecursiveEnabled(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        $files = $repo->findAll();

        $paths = array_map(fn($f) => $f->path(), $files);

        // Root-level files
        $this->assertContains('apple.jpg', $paths);
        $this->assertContains('banana.png', $paths);
        $this->assertContains('carrot.jpg', $paths);

        // Subfolder files must now appear
        $this->assertContains('animals/lion.jpg', $paths);
        $this->assertContains('animals/tiger.jpg', $paths);
        $this->assertContains('vehicles/car.jpg', $paths);
    }
    
    public function testCountReturnsTotalNumberOfFiles(): void
    {
        $repo = $this->makeRepo();

        $count = $repo->count();

        // Root-level files only (non-recursive by default)
        $this->assertSame(4, $count);
    }

    public function testCountRespectsWhereFilter(): void
    {
        $repo = $this->makeRepo();

        $count = $repo->count([
            'filename' => ['like' => 'ap%'],
        ]);

        // apple.jpg + apricot.jpg
        $this->assertSame(2, $count);
    }

    public function testCountWithMetadataFilter(): void
    {
        $repo = $this->makeRepo();

        $count = $repo->count([
            'metadata->author' => ['=' => 'anna'],
        ]);

        // banana.png
        $this->assertSame(1, $count);
    }

    public function testCountWithRecursiveEnabled(): void
    {
        $repo = $this->makeRepo()->withRecursive(true);

        $count = $repo->count();

        $this->assertSame(8, $count);
    }

    public function testFindColumnReturnsValues(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('filename');

        $this->assertSame(
            ['apple', 'apricot', 'banana', 'carrot'],
            $values
        );
    }

    public function testFindColumnWithKey(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn(column: 'filename', key: 'path');

        $this->assertSame(
            [
                'apple.jpg'   => 'apple',
                'apricot.jpg' => 'apricot',
                'banana.png'  => 'banana',
                'carrot.jpg'  => 'carrot',
            ],
            $values
        );
    }

    public function testFindColumnWithLimit(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('filename', limit: 2);

        $this->assertSame(
            ['apple', 'apricot'],
            $values
        );
    }

    public function testFindColumnWithLimitAndOffset(): void
    {
        $repo = $this->makeRepo();

        // limit = [count, offset]
        $values = $repo->findColumn('filename', limit: [2, 1]);

        $this->assertSame(
            ['apricot', 'banana'],
            $values
        );
    }

    public function testFindColumnWithOrderByAscending(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('filename', orderBy: ['filename' => 'ASC']);

        $this->assertSame(
            ['apple', 'apricot', 'banana', 'carrot'],
            $values
        );
    }

    public function testFindColumnWithOrderByDescending(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn('filename', orderBy: ['filename' => 'DESC']);

        $this->assertSame(
            ['carrot', 'banana', 'apricot', 'apple'],
            $values
        );
    }

    public function testFindColumnWithKeyAndOrderBy(): void
    {
        $repo = $this->makeRepo();

        $values = $repo->findColumn(column: 'filename', key: 'path', orderBy: ['filename' => 'DESC']);

        $this->assertSame(
            [
                'carrot.jpg' => 'carrot',
                'banana.png' => 'banana',
                'apricot.jpg' => 'apricot',
                'apple.jpg' => 'apple',
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
    
    public function testCreateWritesFile(): void
    {
        $repo = $this->makeRepo();

        $file = $repo->create([
            'path' => 'newfile.txt',
            'content' => 'Hello World',
        ]);

        $this->assertSame('newfile.txt', $file->path());
        $this->assertSame(11, $file->size());

        // cleanup
        $repo->deleteById('newfile.txt');
    }
    
    public function testCreateWithoutContentAssumesFileExists(): void
    {
        $repo = $this->makeRepo();

        // Pre‑write file manually
        $repo->storage()->write('prewritten.txt', 'ABC');

        $file = $repo->create([
            'path' => 'prewritten.txt',
        ]);

        $this->assertSame('prewritten.txt', $file->path());
        $this->assertSame(3, $file->size());

        // cleanup
        $repo->deleteById('prewritten.txt');
    }
    
    public function testCreateWithoutContentThrowsIfFileMissing(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryCreateException::class);

        $repo->create([
            'path' => 'missing.txt',
        ]);
    }
    
    public function testCreateThrowsOnInvalidPath(): void
    {
        $repo = $this->makeRepo();

        $this->expectException(RepositoryCreateException::class);

        $repo->create([
            'path' => ['not-a-string'],
            'content' => 'X',
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

    public function testDeleteByIdDeletesFile(): void
    {
        $repo = $this->makeRepo();

        // Create a dedicated test file
        $repo->storage()->write('temp-delete-test.jpg', 'XYZ');

        $this->assertNotNull($repo->findById('temp-delete-test.jpg'));

        $deleted = $repo->deleteById('temp-delete-test.jpg');

        $this->assertSame('temp-delete-test.jpg', $deleted->path());
        $this->assertNull($repo->findById('temp-delete-test.jpg'));
    }

    public function testDeleteDeletesMultipleFiles(): void
    {
        $repo = $this->makeRepo();

        // Create temporary test files that match the filter
        $repo->storage()->write('tempAA.jpg', 'AA');
        $repo->storage()->write('tempAAA.jpg', 'AAA');
        $repo->storage()->write('tempBB.jpg', 'BB');

        // Act: delete all files with author = tom
        $deleted = iterator_to_array($repo->delete([
            'filename' => ['like' => 'tempAA%'],
        ]));

        // We expect exactly the two temp files to be deleted
        $deletedPaths = array_map(fn($f) => $f->path(), $deleted);

        $this->assertCount(2, $deleted);
        $this->assertContains('tempAA.jpg', $deletedPaths);
        $this->assertContains('tempAAA.jpg', $deletedPaths);

        // Ensure they are gone
        $this->assertNull($repo->findById('tempAA.jpg'));
        $this->assertNull($repo->findById('tempAAA.jpg'));
        
        // cleanup
        $repo->deleteById('tempBB.jpg');
    }

    public function testDeleteReturnsEmptyIfNoMatch(): void
    {
        $repo = $this->makeRepo();

        $deleted = iterator_to_array($repo->delete([
            'author' => 'nobody',
        ]));

        $this->assertSame([], $deleted);
    }
    
    public function testAttributeAliasesWorkForWhere(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'file_name' => 'filename',
        ]);

        $files = $repo->findAll(where: ['file_name' => 'banana']);

        $this->assertCount(1, $files);
        $this->assertSame('banana', $files[0]->filename());
    }
    
    public function testAttributeAliasesWorkForOrderBy(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'file_name' => 'filename',
        ]);

        $files = $repo->findAll(orderBy: ['file_name' => 'DESC']);

        $this->assertSame('carrot', $files[0]->filename());
    }
    
    public function testFindColumnUsesAttributeAliases(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'file_name' => 'filename',
        ]);

        $values = $repo->findColumn('file_name');

        $this->assertSame(['apple', 'apricot', 'banana', 'carrot'], $values);
    }
    
    public function testFindColumnWithKeyUsesAttributeAliases(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'file_name' => 'filename',
        ]);

        $values = $repo->findColumn(column: 'file_name', key: 'path');

        $this->assertSame(
            [
                'apple.jpg'   => 'apple',
                'apricot.jpg' => 'apricot',
                'banana.png'  => 'banana',
                'carrot.jpg'  => 'carrot',
            ],
            $values
        );
    }
    
    public function testCreateDoesNotSupportAttributeAliases(): void
    {
        $repo = $this->makeRepo()->withAttributeAliases([
            'file_path' => 'path',
        ]);

        $this->expectException(RepositoryCreateException::class);
        $this->expectExceptionMessage('Missing file path');

        $repo->create([
            'file_path' => 'newfile.txt',
            'content'   => 'Hello World',
        ]);
    }
}