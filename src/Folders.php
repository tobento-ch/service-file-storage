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

use Tobento\Service\Iterable\Iter;
use Generator;

/**
 * Folders
 */
class Folders implements FoldersInterface
{
    /**
     * @var array<int, FolderInterface> The folders.
     */    
    protected array $folders = [];
    
    /**
     * Create a new Files.
     *
     * @param iterable $folders
     */
    public function __construct(
        iterable $folders = [],
    ) {
        $this->folders = Iter::toArray(iterable: $folders);
    }
    
    /**
     * Returns a new instance with the filtered folders.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->folders = array_filter($this->folders, $callback);
        return $new;
    }
    
    /**
     * Returns a new instance with the folders sorted.
     *
     * @param callable $callback
     * @return static
     */
    public function sort(callable $callback): static
    {
        $folders = $this->all();
        usort($folders, $callback);
        
        $new = clone $this;
        $new->folders = $folders;
        return $new;
    }
    
    /**
     * Returns all folders.
     *
     * @return array<int, FolderInterface>
     */
    public function all(): array
    {
        return $this->folders;
    }

    /**
     * Returns an iterator for the folders.
     *
     * @return Generator
     */
    public function getIterator(): Generator
    {
        foreach($this->folders as $key => $folder) {
            yield $key => $folder;
        }
    }
}