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

namespace Tobento\Service\FileStorage\Test\Flysystem;

use League\Flysystem\FileAttributes;
use Tobento\Service\FileStorage\File;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\Flysystem\FileFactoryInterface;

class TestFileFactory implements FileFactoryInterface
{
    public function __construct(
        protected FileFactoryInterface $inner,
        protected array $metadataByPath = []
    ) {}

    public function createFileFromPath(string $path, array $with = []): FileInterface
    {
        // Let the real factory create the base file
        $file = $this->inner->createFileFromPath($path, $with);

        // Merge custom metadata
        $metadata = array_merge(
            $file->metadata(),
            $this->metadataByPath[$path] ?? []
        );

        // Return a new File with merged metadata
        return new File(
            storageName: $file->storageName(),
            path: $file->path(),
            stream: $file->stream(),
            mimeType: $file->mimeType(),
            size: $file->size(),
            width: $file->width(),
            height: $file->height(),
            lastModified: $file->lastModified(),
            url: $file->url(),
            metadata: $metadata,
        );
    }

    public function createFileFromFileAttributes(FileAttributes $attributes, array $with = []): FileInterface
    {
        $file = $this->inner->createFileFromFileAttributes($attributes, $with);

        $metadata = array_merge(
            $file->metadata(),
            $this->metadataByPath[$attributes->path()] ?? []
        );

        return new File(
            storageName: $file->storageName(),
            path: $file->path(),
            stream: $file->stream(),
            mimeType: $file->mimeType(),
            size: $file->size(),
            width: $file->width(),
            height: $file->height(),
            lastModified: $file->lastModified(),
            url: $file->url(),
            metadata: $metadata,
        );
    }
}