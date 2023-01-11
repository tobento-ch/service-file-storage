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
 * Files
 */
class Files implements FilesInterface
{
    /**
     * @var array The files.
     */    
    protected array $files = [];
    
    /**
     * Create a new Files.
     *
     * @param iterable $files
     */
    public function __construct(
        iterable $files = [],
    ) {
        $this->files = Iter::toArray(iterable: $files);
    }
    
    /**
     * Returns a new instance with the filtered files.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->files = array_filter($this->files, $callback);
        return $new;
    }
    
    /**
     * Returns a new instance with the files sorted.
     *
     * @param callable $callback
     * @return static
     */
    public function sort(callable $callback): static
    {
        $files = $this->all();
        usort($files, $callback);
        
        $new = clone $this;
        $new->files = $files;
        return $new;
    }
    
    /**
     * Returns all files.
     *
     * @return array<int, FileInterface>
     */
    public function all(): array
    {
        return $this->files;
    }

    /**
     * Returns an iterator for the files.
     *
     * @return Generator
     */
    public function getIterator(): Generator
    {
        foreach($this->files as $key => $file) {
            yield $key => $file;
        }
    }
}