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
 * FilesInterface
 */
interface FilesInterface extends IteratorAggregate
{
    /**
     * Returns a new instance with the filtered files.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;
    
    /**
     * Returns a new instance with the files sorted.
     *
     * @param callable $callback
     * @return static
     */
    public function sort(callable $callback): static;
    
    /**
     * Returns all files.
     *
     * @return array<int, FileInterface>
     */
    public function all(): array;
}