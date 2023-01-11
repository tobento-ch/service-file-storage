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
 * FileWriteException
 */
class FileWriteException extends FileException
{
    /**
     * Create a new FileWriteException.
     *
     * @param string $path
     * @param mixed $content
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected string $path,
        protected mixed $content,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
    
    /**
     * Returns the content.
     *
     * @return mixed
     */
    public function content(): mixed
    {
        return $this->content;
    }
}