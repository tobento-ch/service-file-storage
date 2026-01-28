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

class Folder implements FolderInterface
{
    /**
     * Create a new Folder.
     *
     * @param string $storageName
     * @param string $path
     * @param null|int $lastModified
     * @param array $metadata
     */
    public function __construct(
        protected string $storageName,
        protected string $path,
        protected null|int $lastModified = null,
        protected array $metadata = [],
    ) {}
    
    /**
     * Returns the name of the storage this folder belongs to.
     *
     * Example: "local", "s3", "public"
     *
     * @return string
     */
    public function storageName(): string
    {
        return $this->storageName;
    }

    /**
     * Returns a new instance with the given storage name.
     *
     * @param string $name
     * @return static
     */
    public function withStorageName(string $name): static
    {
        $new = clone $this;
        $new->storageName = $name;
        return $new;
    }
    
    /**
     * Returns the path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }
    
    /**
     * Returns the parent path.
     *
     * @return string
     */
    public function parentPath(): string
    {
        $dirname = dirname($this->path);
        
        return $dirname === '.' ? '' : $dirname;
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return basename($this->path);
    }
    
    /**
     * Returns last modified.
     *
     * @return null|int
     */
    public function lastModified(): null|int
    {
        return $this->lastModified;
    }

    /**
     * Returns the metadata.
     *
     * @return array
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}