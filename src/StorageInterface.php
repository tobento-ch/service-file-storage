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
 * StorageInterface
 */
interface StorageInterface
{
    /**
     * Returns the storage name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the storage visibility type.
     *
     * Supported values:
     * - 'public'
     * - 'private'
     *
     * @return string  The visibility type of the storage.
     */
    public function type(): string;

    /**
     * Returns true if the storage is public.
     *
     * Public storages expose files via direct URLs and are suitable
     * for features such as responsive images, variants, and public downloads.
     *
     * @return bool
     */
    public function isPublic(): bool;

    /**
     * Returns true if the storage is private.
     *
     * Private storages do not expose direct URLs. Files must be accessed
     * through signed URLs or application-controlled routes.
     *
     * @return bool
     */
    public function isPrivate(): bool;

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param mixed $content
     * @return void
     * @throws FileWriteException
     */
    public function write(string $path, mixed $content): void;
    
    /**
     * Returns true if file exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool;
    
    /**
     * Returns the file.
     *
     * @param string $path
     * @return FileInterface
     * @throws FileNotFoundException
     */
    public function file(string $path): FileInterface;
    
    /**
     * Returns the files from the specified path.
     *
     * @param string $path
     * @param bool $recursive
     * @return FilesInterface
     */
    public function files(string $path, bool $recursive = false): FilesInterface;
    
    /**
     * Delete file at the specified path.
     *
     * @param string $path
     * @return void
     * @throws FileException
     */
    public function delete(string $path): void;
    
    /**
     * Move a file to a new destination.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws FileException
     */
    public function move(string $from, string $to): void;
    
    /**
     * Copy a file to a new destination.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws FileException
     */
    public function copy(string $from, string $to): void;
    
    /**
     * Create a folder.
     *
     * @param string $path
     * @return void
     * @throws FolderException
     */
    public function createFolder(string $path): void;
    
    /**
     * Returns true if folder exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function folderExists(string $path): bool;
    
    /**
     * Returns the folders from the specified path.
     *
     * @param string $path
     * @return FoldersInterface
     * @throws StorageException
     */
    public function folders(string $path, bool $recursive = false): FoldersInterface;
    
    /**
     * Delete folder at the specified path.
     *
     * @param string $path
     * @return void
     * @throws FolderException
     */
    public function deleteFolder(string $path): void;
    
    /**
     * Returns a new instance with the specified attribute.
     *
     * @param string ...$attribute
     * @return static
     */
    public function with(string ...$attribute): static;
}