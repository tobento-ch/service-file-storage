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
 * File
 */
class File implements FileInterface
{
    /**
     * @var string
     */
    protected string $name;
    
    /**
     * @var string
     */
    protected string $filename;
    
    /**
     * @var string
     */
    protected string $extension;
    
    /**
     * Create a new File.
     *
     * @param string $path
     * @param null|StreamInterface $stream
     * @param null|string $mimeType
     * @param null|int $size
     * @param null|int $width
     * @param null|int $height
     * @param null|int $lastModified
     * @param null|string $url
     * @param null|string $visibility
     * @param array $metadata
     */
    public function __construct(
        protected string $path,
        protected null|StreamInterface $stream = null,
        protected null|string $mimeType = null,
        protected null|int $size = null,
        protected null|int $width = null,
        protected null|int $height = null,
        protected null|int $lastModified = null,
        protected null|string $url = null,
        protected null|string $visibility = null,
        protected array $metadata = [],
    ) {
        $pathinfo = pathinfo($path);
        $this->name = $pathinfo['basename'] ?? '';
        $this->filename = $pathinfo['filename'] ?? '';
        $this->extension = isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : '';
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
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return $this->filename;
    }
    
    /**
     * Returns the extension.
     *
     * @return string
     */
    public function extension(): string
    {
        return $this->extension;
    }
    
    /**
     * Returns the folder path.
     *
     * @return string
     */
    public function folderPath(): string
    {
        $dirname = dirname($this->path);
        
        return $dirname === '.' ? '' : $dirname;
    }
    
    /**
     * Returns the stream.
     *
     * @return null|StreamInterface
     */
    public function stream(): null|StreamInterface
    {
        return $this->stream;
    }
    
    /**
     * Returns the content.
     *
     * @return null|string
     */
    public function content(): null|string
    {
        return $this->stream()?->__toString();
    }
    
    /**
     * Returns the mime type.
     *
     * @return null|string
     */
    public function mimeType(): null|string
    {
        return $this->mimeType;
    }
    
    /**
     * Returns the size.
     *
     * @return null|int
     */
    public function size(): null|int
    {
        if (is_int($this->size)) {
            return $this->size;
        }
        
        if (!is_null($this->stream())) {
            return $this->size = $this->stream()->getSize();
        }
        
        return $this->size;
    }
    
    /**
     * Returns the width.
     *
     * @return null|int
     */
    public function width(): null|int
    {
        return $this->width;
    }
    
    /**
     * Returns the height.
     *
     * @return null|int
     */
    public function height(): null|int
    {
        return $this->height;
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
     * Returns the url.
     *
     * @return null|string
     */
    public function url(): null|string
    {
        return $this->url;
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

    /**
     * Checks if it is a html image renderable with img tag.
     *
     * @return bool
     */
    public function isHtmlImage(): bool
    {
        if (is_null($this->mimeType())) {
            return false;
        }
        
        return in_array(
            $this->mimeType(),
            [
                'image/gif',
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/avif',
            ]
        );
    }
}