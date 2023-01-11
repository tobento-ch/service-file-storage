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
 * FolderInterface
 */
interface FolderInterface
{
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
     * Returns the visibility.
     *
     * @return null|string
     */
    public function visibility(): null|string;

    /**
     * Returns the metadata.
     *
     * @return array
     */
    public function metadata(): array;
}