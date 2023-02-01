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

use IteratorAggregate;

/**
 * FoldersInterface
 */
interface FoldersInterface extends IteratorAggregate
{
    /**
     * Returns a new instance with the filtered folders.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;
    
    /**
     * Returns a new instance with the folders sorted.
     *
     * @param callable $callback
     * @return static
     */
    public function sort(callable $callback): static;

    /**
     * Returns the first folder or null if none.
     *
     * @return null|FolderInterface
     */
    public function first(): null|FolderInterface;
    
    /**
     * Returns the folder by path or null if not exists.
     *
     * @return null|FolderInterface
     */
    public function get(string $path): null|FolderInterface;
    
    /**
     * Returns all folders.
     *
     * @return array<int, FolderInterface>
     */
    public function all(): array;
}