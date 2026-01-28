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

use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\Repository\RepositoryCreateException;
use Tobento\Service\Repository\RepositoryDeleteException;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\RepositoryReadException;
use Tobento\Service\Repository\RepositoryUpdateException;

class FileFolderRepository implements RepositoryInterface
{
    /**
     * Create a new repository instance.
     *
     * @param FileRepository $fileRepository
     * @param FolderRepository $folderRepository
     */
    final public function __construct(
        protected FileRepository $fileRepository,
        protected FolderRepository $folderRepository,
    ) {}
    
    /**
     * Returns the file repository.
     *
     * @return FileRepository
     */
    public function fileRepository(): FileRepository
    {
        return $this->fileRepository;
    }
    
    /**
     * Returns the folder repository.
     *
     * @return FolderRepository
     */
    public function folderRepository(): FolderRepository
    {
        return $this->folderRepository;
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
        $new->fileRepository = $this->fileRepository->withStorage($storage);
        $new->folderRepository = $this->folderRepository->withStorage($storage);
        return $new;
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
        $new->fileRepository = $this->fileRepository->withRootFolder($rootFolder);
        $new->folderRepository = $this->folderRepository->withRootFolder($rootFolder);
        return $new;
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
        $new->fileRepository = $this->fileRepository->withRecursive($recursive);
        $new->folderRepository = $this->folderRepository->withRecursive($recursive);
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
        return $this->fileRepository->findById($id)
            ?? $this->folderRepository->findById($id);
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
        return array_merge(
            iterator_to_array($this->fileRepository->findByIds(...$ids)),
            iterator_to_array($this->folderRepository->findByIds(...$ids)),
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
        return $this->fileRepository->findOne($where)
            ?? $this->folderRepository->findOne($where);
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
        $files = iterator_to_array($this->fileRepository->findAll($where, $orderBy, $limit));
        $folders = iterator_to_array($this->folderRepository->findAll($where, $orderBy, $limit));

        $all = array_merge($files, $folders);

        if (is_int($limit)) {
            return array_slice($all, 0, $limit);
        }

        if (is_array($limit) && isset($limit[0], $limit[1])) {
            return array_slice($all, $limit[1], $limit[0]);
        }

        return $all;
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
        throw new RepositoryReadException('Unsupported');
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
        return $this->fileRepository->count($where) + $this->folderRepository->count($where);
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
        throw new RepositoryCreateException([], 'Unsupported');
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
        throw new RepositoryDeleteException($id, 'Unsupported');
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
        throw new RepositoryDeleteException('', 'Unsupported');
    }
}