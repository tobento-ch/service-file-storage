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

use Psr\Http\Message\StreamInterface;

/**
 * FileInterface
 */
interface FileInterface
{
    /**
     * Returns the path.
     *
     * @return string
     */
    public function path(): string;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the filename.
     *
     * @return string
     */
    public function filename(): string;
    
    /**
     * Returns the extension.
     *
     * @return string
     */
    public function extension(): string;
    
    /**
     * Returns the folder path.
     *
     * @return string
     */
    public function folderPath(): string;
    
    /**
     * Returns the stream.
     *
     * @return null|StreamInterface
     */
    public function stream(): null|StreamInterface;
    
    /**
     * Returns the content.
     *
     * @return null|string
     */
    public function content(): null|string;
    
    /**
     * Returns the mime type.
     *
     * @return null|string
     */
    public function mimeType(): null|string;
    
    /**
     * Returns the size.
     *
     * @return null|int
     */
    public function size(): null|int;
    
    /**
     * Returns the width.
     *
     * @return null|int
     */
    public function width(): null|int;
    
    /**
     * Returns the height.
     *
     * @return null|int
     */
    public function height(): null|int;
    
    /**
     * Returns last modified.
     *
     * @return null|int
     */
    public function lastModified(): null|int;
    
    /**
     * Returns the url.
     *
     * @return null|string
     */
    public function url(): null|string;
    
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

    /**
     * Checks if it is a html image renderable with img tag.
     *
     * @return bool
     */
    public function isHtmlImage(): bool;
}