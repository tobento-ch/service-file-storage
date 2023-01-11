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
 * Folder
 */
class Folder implements FolderInterface
{
    /**
     * Create a new Folder.
     *
     * @param string $path
     * @param null|int $lastModified
     * @param null|string $visibility
     * @param array $metadata
     */
    public function __construct(
        protected string $path,
        protected null|int $lastModified = null,
        protected null|string $visibility = null,
        protected array $metadata = [],
    ) {}
    
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
     * Returns the visibility.
     *
     * @return null|string
     */
    public function visibility(): null|string
    {
        return $this->visibility;
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