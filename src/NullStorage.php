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

class NullStorage implements StorageInterface
{
    /**
     * Create a new NullStorage instance.
     *
     * @param string $name
     * @param string $type The storage visibility type: 'public' or 'private'.
     */
    final public function __construct(
        protected string $name = 'null',
        protected string $type = 'private',
    ) {
        if (!in_array($this->type, ['public', 'private'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid storage type '%s'. Allowed values are 'public' or 'private'.",
                    $this->type
                )
            );
        }
    }
    
    /**
     * Returns the storage name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
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
        return $this->type;
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
        return $this->type === 'public';
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
        return $this->type === 'private';
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
        //
    }
    
    /**
     * Returns true if file exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return false;
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
        return new File(
            storageName: $this->name(),
            path: $path,
            url: '',
            width: 0,
            height: 0,
        );
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
        return new Files();
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
        //
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
        //
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
        //
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
        //
    }
    
    /**
     * Returns true if folder exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function folderExists(string $path): bool
    {
        return false;
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
        return new Folders();
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
        //
    }
    
    /**
     * Returns a new instance with the specified attribute.
     *
     * @param string ...$attribute
     * @return static
     */
    public function with(string ...$attribute): static
    {
        return new static();
    }
}