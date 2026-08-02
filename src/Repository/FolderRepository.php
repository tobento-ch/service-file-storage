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

namespace Tobento\Service\FileStorage\Repository;

use Tobento\Service\FileStorage\Folder;
use Tobento\Service\FileStorage\FolderException;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\RepositoryReadException;
use Tobento\Service\Repository\RepositoryUpdateException;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\Tables\Tables;

class FolderRepository implements RepositoryInterface
{
    /**
     * Create a new repository instance with the specified configuration.
     *
     * @param StorageInterface $storage The underlying file storage.
     * @param string $rootFolder The root folder to operate on.
     * @param array<string, string> $attributeAliases The attribute aliases (alias => raw).
     * @param bool $recursive Whether to include files from subfolders.
     */
    final public function __construct(
        protected StorageInterface $storage,
        protected string $rootFolder = '',
        protected array $attributeAliases = [],
        protected bool $recursive = false,
    ) {}
    
    /**
     * Returns the storage.
     *
     * @return StorageInterface
     */
    public function storage(): StorageInterface
    {
        return $this->storage;
    }
    
    /**
     * Returns a new instance with the specified storage.
     *
     * @param StorageInterface $storage The storage to use for loading files.
     * @return static A new instance with the updated storage.
     */
    public function withStorage(StorageInterface $storage): static
    {
        $new = clone $this;
        $new->storage = $storage;
        return $new;
    }

    /**
     * Returns the root folder.
     *
     * @return string
     */
    public function rootFolder(): string
    {
        return $this->rootFolder;
    }
    
    /**
     * Returns a new instance with the specified root folder.
     *
     * @param string $rootFolder The root folder to operate on.
     * @return static A new instance with the updated root folder.
     */
    public function withRootFolder(string $rootFolder): static
    {
        $new = clone $this;
        $new->rootFolder = $rootFolder;
        return $new;
    }

    /**
     * Returns a new instance with the specified attribute aliases.
     *
     * @param array<string, string> $aliases
     * @return static
     */
    public function withAttributeAliases(array $aliases): static
    {
        $new = clone $this;
        $new->attributeAliases = $aliases;
        return $new;
    }
    
    /**
     * Returns the attribute aliases (alias => raw).
     *
     * @return array<string, string>
     */
    public function attributeAliases(): array
    {
        return $this->attributeAliases;
    }
    
    /**
     * Returns whether recursive mode is enabled.
     *
     * @return bool
     */
    public function recursive(): bool
    {
        return $this->recursive;
    }
    
    /**
     * Returns a new instance with recursive mode enabled or disabled.
     *
     * @param bool $recursive Whether to include files from subfolders.
     * @return static A new instance with the updated recursive flag.
     */
    public function withRecursive(bool $recursive = true): static
    {
        $new = clone $this;
        $new->recursive = $recursive;
        return $new;
    }
    
    /**
     * Returns the found entity using the specified id (primary key)
     * or null if none found.
     *
     * @param int|string $id
     * @return null|object
     * @throws RepositoryReadException
     */
    public function findById(int|string $id): null|object
    {
        $path = $this->buildPath($id);

        // Reuse findAll with a where filter
        $results = $this->findAll(
            where: ['path' => ['=' => $path]],
            limit: 1,
        );
        
        $array = is_array($results) ? $results : iterator_to_array($results, false);
        
        return $array[0] ?? null;
    }
    
    /**
     * Returns the found entity using the specified id (primary key)
     * or null if none found.
     *
     * @param int|string ...$ids
     * @return iterable<object>
     * @throws RepositoryReadException
     */
    public function findByIds(int|string ...$ids): iterable
    {
        $paths = array_map(fn($id) => $this->buildPath($id), $ids);

        return $this->findAll(
            where: ['path' => ['in' => $paths]]
        );
    }

    /**
     * Returns the found entity using the specified where parameters
     * or null if none found.
     *
     * @param array $where
     * @return null|object
     * @throws RepositoryReadException
     */
    public function findOne(array $where = []): null|object
    {
        foreach($this->findAll($where) as $file) {
            return $file;
        }
        
        return null;
    }

    /**
     * Returns the found entities using the specified parameters.
     *
     * @param array $where Usually where parameters.
     * @param array $orderBy The order by parameters.
     * @param null|int|array $limit The limit e.g. 5 or [5(number), 10(offset)].
     * @return iterable<object>
     * @throws RepositoryReadException
     */
    public function findAll(array $where = [], array $orderBy = [], null|int|array $limit = null): iterable
    {
        $folders = $this->storage()
            ->folders(path: $this->rootFolder(), recursive: $this->recursive());
        
        $rows = [];
        
        foreach ($folders as $i => $folder) {
            $rows[$i] = [
                'id' => $i,
                'type' => 'folder',
                'storageName' => $folder->storageName(),
                'path' => $folder->path(),
                'parentPath' => $folder->parentPath(),
                'name' => $folder->name(),
                'lastModified' => $folder->lastModified(),
                'metadata' => json_encode($folder->metadata()),
            ];
        }

        $tables = new Tables()->add(
            table: 'folders',
            columns: [
                'id', 'type', 'storageName', 'path', 'parentPath', 'name', 'lastModified', 'metadata',
            ],
            primaryKey: 'id',
        );
        
        $storage = new InMemoryStorage(['folders' => $rows], $tables);

        $repo = new class(
            $storage,
            $this->attributeAliases(),
        ) extends StorageRepository {
            public function __construct(
                $storage,
                protected array $aliases,
            ) {
                parent::__construct(storage: $storage, table: 'folders', entityFactory: null);
            }
            
            protected function configureColumns(): iterable|ColumnsInterface
            {
                return new Column\AliasedColumns(
                    new Column\Id(),
                    new Column\Text('type'),
                    new Column\Text('storageName'),
                    new Column\Text('path'),
                    new Column\Text('parentPath'),
                    new Column\Text('name'),
                    new Column\Datetime(name: 'lastModified', type: 'timestamp'),
                    new Column\Json('metadata'),
                )->withAliases(
                    aliases: $this->aliases,
                    readonly: true,
                );
            }
        };
        
        /** @var \Tobento\Service\Storage\ItemsInterface $matches */
        $matches = $repo->findAll(where: $where, orderBy: $orderBy, limit: $limit);
        
        $folders = $matches->map(function(ItemInterface $item) {
            return new Folder(
                storageName: $item['storageName'],
                path: $item['path'],
                lastModified: (int)$item['lastModified'],
                metadata: $item['metadata'],
            );
        });
        
        /** @var list<Folder> */
        return array_values($folders->all());
    }
    
    /**
     * Returns the found column values using the specified parameters.
     *
     * @param string $column The column name for the values.
     * @param null|string $key The column name for the index key.
     * @param array $where Usually where parameters.
     * @param array $orderBy The order by parameters.
     * @param null|int|array $limit The limit e.g. 5 or [5(number), 10(offset)].
     * @return array
     * @throws RepositoryReadException
     */
    public function findColumn(
        string $column,
        null|string $key = null,
        array $where = [],
        array $orderBy = [],
        null|int|array $limit = null
    ): array {
        $column = $this->rewriteAttributeAlias($column);
        $key = $key ? $this->rewriteAttributeAlias($key) : null;
        
        // Reuse the in-memory repo created in findAll()
        $folders = $this->findAll(where: $where, orderBy: $orderBy, limit: $limit);

        $values = [];

        foreach ($folders as $i => $folder) {
            // Convert Folder object to array-like structure
            $row = [
                'id' => $i,
                'type' => 'folder',
                'storageName'  => $folder->storageName(),
                'path' => $folder->path(),
                'parentPath' => $folder->parentPath(),
                'name' => $folder->name(),
                'lastModified' => $folder->lastModified(),
                'metadata' => $folder->metadata(),
            ];

            if (!array_key_exists($column, $row)) {
                continue;
            }

            $value = $row[$column];

            if ($key !== null && array_key_exists($key, $row)) {
                $values[$row[$key]] = $value;
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }
    
    /**
     * Returns the number of items using the specified where parameters.
     *
     * @param array $where
     * @return int
     * @throws RepositoryReadException
     */
    public function count(array $where = []): int
    {
        return count($this->findAll($where));
    }
    
    /**
     * Create an entity.
     *
     * @param array $attributes
     * @return object The created entity.
     * @throws RepositoryCreateException
     */
    public function create(array $attributes): object
    {
        if (!isset($attributes['path'])) {
            throw new RepositoryCreateException(
                attributes: $attributes,
                message: 'Missing folder path',
            );
        }

        if (!is_string($attributes['path'])) {
            throw new RepositoryCreateException(
                attributes: $attributes,
                message: 'Folder path must be a string',
            );
        }

        $path = $this->buildPath($attributes['path']);

        try {
            $this->storage->createFolder($path);
        } catch (FolderException $e) {
            throw new RepositoryCreateException(
                attributes: $attributes,
                message: $e->getMessage(),
                previous: $e,
            );
        }

        $folder = $this->findById($path);

        if (is_null($folder)) {
            throw new RepositoryCreateException(
                attributes: $attributes,
                message: 'Folder not found after creation',
            );
        }

        return $folder;
    }
    
    /**
     * Update an entity by id.
     *
     * @param string|int $id
     * @param array $attributes The attributes to update the entity.
     * @return object The updated entity.
     * @throws RepositoryUpdateException
     */
    public function updateById(string|int $id, array $attributes): object
    {
        throw new RepositoryUpdateException([], $id, 'Unsupported');
    }
    
    /**
     * Update entities.
     *
     * @param array $where The where parameters.
     * @param array $attributes The attributes to update the entities.
     * @return iterable<object> The updated entities.
     * @throws RepositoryUpdateException
     */
    public function update(array $where, array $attributes): iterable
    {
        throw new RepositoryUpdateException([], '', 'Unsupported');
    }
    
    /**
     * Delete an entity by id.
     *
     * @param string|int $id
     * @return object The deleted entity.
     * @throws RepositoryDeleteException
     */
    public function deleteById(string|int $id): object
    {
        $folder = $this->findById($id);

        if (is_null($folder)) {
            throw new RepositoryDeleteException(
                message: 'Folder not found for deletion',
                id: $id,
            );
        }

        try {
            $this->storage->deleteFolder($folder->path());
        } catch (FolderException $e) {
            throw new RepositoryDeleteException(
                message: $e->getMessage(),
                id: $id,
                previous: $e,
            );
        }

        return $folder;
    }
    
    /**
     * Delete entities.
     *
     * @param array $where The where parameters.
     * @return iterable<object> The deleted entities.
     * @throws RepositoryDeleteException
     */
    public function delete(array $where): iterable
    {
        $folders = $this->findAll(where: $where);

        $deleted = [];

        foreach ($folders as $folder) {
            try {
                $this->storage->deleteFolder($folder->path());
            } catch (FolderException $e) {
                throw new RepositoryDeleteException(
                    message: $e->getMessage(),
                    id: $folder->path(),
                    previous: $e
                );
            }

            $deleted[] = $folder;
        }

        return $deleted;
    }
    
    /**
     * Builds the full storage path for the given file id.
     *
     * The id is treated as a relative file path. If a root folder is defined,
     * the id is appended to it. Leading and trailing slashes are normalized.
     *
     * Examples:
     *   rootFolder = 'exports',  id = 'file.jpg'   → 'exports/file.jpg'
     *   rootFolder = '',         id = 'file.jpg'   → 'file.jpg'
     *
     * @param string|int $id The file identifier or relative path.
     * @return string The normalized full path within the storage.
     */
    protected function buildPath(string|int $id): string
    {
        $root = trim($this->rootFolder(), '/');
        $id = trim((string)$id, '/');

        return $root === '' ? $id : $root . '/' . $id;
    }
    
    /**
     * Rewrite attribute alias.
     *
     * @param string $attribute
     * @return string
     */
    protected function rewriteAttributeAlias(string $attribute): string
    {
        return $this->attributeAliases[$attribute] ?? $attribute;
    }
}