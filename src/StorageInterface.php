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
     * Write the contents of a file.
     *
     * @param string $path
     * @param mixed $content
     * @param null|string $visibility
     * @return void
     * @throws FileWriteException
     */
    public function write(string $path, mixed $content, null|string $visibility = null): void;
    
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
     * Set the visibility for the specified path.
     *
     * @param string $path
     * @param string $visibility
     * @return void
     * @throws StorageException
     */
    public function setVisibility(string $path, string $visibility): void;
    
    /**
     * Returns a new instance with the specified attribute.
     *
     * @param string ...$attribute
     * @return static
     */
    public function with(string ...$attribute): static;
}