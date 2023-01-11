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

namespace Tobento\Service\FileStorage\Flysystem;

use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\FilesInterface;
use Tobento\Service\FileStorage\Files;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\File;
use Tobento\Service\FileStorage\FoldersInterface;
use Tobento\Service\FileStorage\Folders;
use Tobento\Service\FileStorage\FolderInterface;
use Tobento\Service\FileStorage\Folder;
use Tobento\Service\FileStorage\Visibility;
use Tobento\Service\FileStorage\StorageException;
use Tobento\Service\FileStorage\FileException;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\FileWriteException;
use Tobento\Service\FileStorage\FolderException;
use Tobento\Service\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use Stringable;

/**
 * Storage
 */
class Storage implements StorageInterface
{
    /**
     * @var array<int, string>
     */
    protected array $fileAttributes = [];
    
    /**
     * Create a new Storage.
     *
     * @param string $name
     * @param FilesystemOperator $flysystem
     * @param FileFactoryInterface $fileFactory,
     */
    public function __construct(
        protected string $name,
        protected FilesystemOperator $flysystem,
        protected FileFactoryInterface $fileFactory,
    ) {}
    
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
     * Write the contents of a file.
     *
     * @param string $path
     * @param mixed $content
     * @param null|string $visibility
     * @return void
     * @throws FileWriteException
     */
    public function write(string $path, mixed $content, null|string $visibility = null): void
    {
        try {
            $config = [];
            
            if ($visibility) {
                $visibility = $visibility === Visibility::PUBLIC
                    ? \League\Flysystem\Visibility::PUBLIC
                    : \League\Flysystem\Visibility::PRIVATE;
                
                $config['visibility'] = $visibility;
            }
            
            switch (true) {
                case $content instanceof Stringable:
                case is_string($content):
                    $this->flysystem->write($path, (string)$content, $config);
                    break;
                case is_resource($content):
                    $this->flysystem->writeStream($path, $content, $config);
                    break;
                case $content instanceof Filesystem\File:
                    $this->flysystem->write($path, $content->getContent(), $config);
                    break;                    
                default:
                    throw new FileWriteException($path, $content, 'Unsupported content passed');
            }
            
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            throw new FileWriteException($path, $content, 'Writing to storage failed', 0, $e);
        }
    }
    
    /**
     * Returns true if file exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->flysystem->fileExists($path);
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
        return $this->fileFactory->createFileFromPath($path, $this->fileAttributes);
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
        $files = $this->flysystem->listContents($path, $recursive)
            ->filter(function(StorageAttributes $attributes) {
                return $attributes->isFile();
            })
            ->map(function(StorageAttributes $attributes): FileInterface {
                return $this->fileFactory->createFileFromFileAttributes($attributes, $this->fileAttributes);
            });
        
        return new Files($files);
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
        try {
            $this->flysystem->delete($path);
        } catch (UnableToDeleteFile $e) {
            throw new FileException($path, 'Deleting file failed', 0, $e);
        }
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
        try {
            $this->flysystem->move($from, $to);
        } catch (UnableToMoveFile $e) {
            throw new FileException($from, 'Moving file failed', 0, $e);
        }
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
        try {
            $this->flysystem->copy($from, $to);
        } catch (UnableToCopyFile $e) {
            throw new FileException($from, 'Copying file failed', 0, $e);
        }
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
        try {
            $this->flysystem->createDirectory($path);
        } catch (UnableToCreateDirectory $e) {
            throw new FolderException($path, 'Creating folder failed', 0, $e);
        }
    }
    
    /**
     * Returns true if folder exists, otherwise false.
     *
     * @param string $path
     * @return bool
     */
    public function folderExists(string $path): bool
    {
        return $this->flysystem->directoryExists($path);
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
        $folders = $this->flysystem->listContents($path, $recursive)
            ->filter(function (StorageAttributes $attributes) {
                return $attributes->isDir();
            })
            ->map(function (StorageAttributes $attributes) {                
                return new Folder(
                    path: $attributes->path(),
                    lastModified: $attributes->lastModified(),
                    visibility: $attributes->visibility(),
                    metadata: $attributes->extraMetadata(),
                );
            });
        
        return new Folders($folders);
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
        try {
            $this->flysystem->deleteDirectory($path);
        } catch (UnableToDeleteDirectory $e) {
            throw new FolderException($path, 'Deleting folder failed', 0, $e);
        }
    }
    
    /**
     * Set the visibility for the specified path.
     *
     * @param string $path
     * @param string $visibility
     * @return void
     * @throws StorageException
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $visibility = $visibility === Visibility::PUBLIC
            ? \League\Flysystem\Visibility::PUBLIC
            : \League\Flysystem\Visibility::PRIVATE;
        
        try {
            $this->flysystem->setVisibility($path, $visibility);
        } catch (UnableToSetVisibility $e) {
            throw new StorageException('Setting visibility failed', 0, $e);
        }
    }
    
    /**
     * Returns a new instance with the specified attribute.
     *
     * @param string ...$attribute
     * @return static
     */
    public function with(string ...$attribute): static
    {
        $new = clone $this;
        $new->fileAttributes = $attribute;
        return $new;
    }
}