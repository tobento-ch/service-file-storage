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
 
namespace Tobento\Service\FileStorage;

/**
 * ReadOnlyStorageAdapter
 */
class ReadOnlyStorageAdapter implements StorageInterface
{
    /**
     * Create a new ReadOnlyStorage instance.
     *
     * @param StorageInterface $storage
     * @param bool $throw
     */
    final public function __construct(
        protected StorageInterface $storage,
        protected bool $throw = false,
    ) {}
    
    /**
     * Returns the storage name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->storage->name();
    }
    
    /**
     * Returns the storage visibility type.
     *
     * Supported values:
     * - 'public'
     * - 'private'
     *
     * @return string  The visibility type of the storage.
     */
    public function type(): string
    {
        return $this->storage->type();
    }

    /**
     * Returns true if the storage is public.
     *
     * Public storages expose files via direct URLs and are suitable
     * for features such as responsive images, variants, and public downloads.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->storage->isPublic();
    }

    /**
     * Returns true if the storage is private.
     *
     * Private storages do not expose direct URLs. Files must be accessed
     * through signed URLs or application-controlled routes.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->storage->isPrivate();
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param mixed $content
     * @return void
     * @throws FileWriteException
     */
    public function write(string $path, mixed $content): void
    {
        throw new FileWriteException(
            path: $path,
            content: $content,
            message: sprintf('Storage %s is readonly', $this->name())
        );
    }
    
    /**
     * Returns true if file exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->storage->exists(path: $path);
    }
    
    /**
     * Returns the file.
     *
     * @param string $path
     * @return FileInterface
     * @throws FileNotFoundException
     */
    public function file(string $path): FileInterface
    {
        try {
            return $this->storage->file(path: $path);
        } catch (FileNotFoundException $e) {
            if ($this->throw) {
                throw $e;
            }
            
            return new File(
                storageName: $this->storage->name(),
                path: $path,
                url: '',
                width: 0,
                height: 0,
            );
        }
    }
    
    /**
     * Returns the files from the specified path.
     *
     * @param string $path
     * @param bool $recursive
     * @return FilesInterface
     */
    public function files(string $path, bool $recursive = false): FilesInterface
    {
        return $this->storage->files(path: $path, recursive: $recursive);
    }
    
    /**
     * Delete file at the specified path.
     *
     * @param string $path
     * @return void
     * @throws FileException
     */
    public function delete(string $path): void
    {
        throw new FileException(path: $path, message: sprintf('Storage %s is readonly', $this->name()));
    }
    
    /**
     * Move a file to a new destination.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws FileException
     */
    public function move(string $from, string $to): void
    {
        throw new FileException(path: $from, message: sprintf('Storage %s is readonly', $this->name()));
    }
    
    /**
     * Copy a file to a new destination.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws FileException
     */
    public function copy(string $from, string $to): void
    {
        throw new FileException(path: $from, message: sprintf('Storage %s is readonly', $this->name()));
    }
    
    /**
     * Create a folder.
     *
     * @param string $path
     * @return void
     * @throws FolderException
     */
    public function createFolder(string $path): void
    {
        throw new FolderException(path: $path, message: sprintf('Storage %s is readonly', $this->name()));
    }
    
    /**
     * Returns true if folder exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function folderExists(string $path): bool
    {
        return $this->storage->folderExists(path: $path);
    }
    
    /**
     * Returns the folders from the specified path.
     *
     * @param string $path
     * @return FoldersInterface
     * @throws StorageException
     */
    public function folders(string $path, bool $recursive = false): FoldersInterface
    {
        return $this->storage->folders(path: $path, recursive: $recursive);
    }
    
    /**
     * Delete folder at the specified path.
     *
     * @param string $path
     * @return void
     * @throws FolderException
     */
    public function deleteFolder(string $path): void
    {
        throw new FolderException(path: $path, message: sprintf('Storage %s is readonly', $this->name()));
    }
    
    /**
     * Returns a new instance with the specified attribute.
     *
     * @param string ...$attribute
     * @return static
     */
    public function with(string ...$attribute): static
    {
        return new static(
            storage: $this->storage->with(...$attribute),
            throw: $this->throw,
        );
    }
}