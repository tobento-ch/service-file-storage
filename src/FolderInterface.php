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

interface FolderInterface
{
    /**
     * Returns the name of the storage this folder belongs to.
     *
     * Example: "local", "s3", "public"
     *
     * @return string
     */
    public function storageName(): string;
    
    /**
     * Returns a new instance with the given storage name.
     *
     * @param string $name
     * @return static
     */
    public function withStorageName(string $name): static;
    
    /**
     * Returns the path.
     *
     * @return string
     */
    public function path(): string;
    
    /**
     * Returns the parent path.
     *
     * @return string
     */
    public function parentPath(): string;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string; 
    
    /**
     * Returns last modified.
     *
     * @return null|int
     */
    public function lastModified(): null|int;

    /**
     * Returns the metadata.
     *
     * @return array
     */
    public function metadata(): array;
}