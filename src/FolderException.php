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

use Throwable;

/**
 * FolderException
 */
class FolderException extends StorageException
{
    /**
     * Create a new FolderException.
     *
     * @param string $path
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected string $path,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
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
}