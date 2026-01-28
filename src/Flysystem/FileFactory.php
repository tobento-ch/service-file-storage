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

namespace Tobento\Service\FileStorage\Flysystem;

use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\File;
use Tobento\Service\FileStorage\FileCreateException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToGeneratePublicUrl;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class FileFactory implements FileFactoryInterface
{
    /**
     * @var PathNormalizer
     */
    protected PathNormalizer $pathNormalizer;
    
    /**
     * Create a new FileFactory.
     *
     * @param FilesystemOperator $flysystem
     * @param StreamFactoryInterface $streamFactory
     * @param null|PathNormalizer $pathNormalizer
     */
    public function __construct(
        protected FilesystemOperator $flysystem,
        protected StreamFactoryInterface $streamFactory,
        null|PathNormalizer $pathNormalizer = null,
    ) {
        $this->pathNormalizer = $pathNormalizer ?: new WhitespacePathNormalizer();
    }
    
    /**
     * Create a new File from the path.
     *
     * @param string $path
     * @param array<int, string> $with
     * @return FileInterface
     * @throws FileCreateException
     */
    public function createFileFromPath(string $path, array $with = []): FileInterface
    {
        if (in_array('width', $with) || in_array('height', $with)) {
            $with[] = 'mimeType';
            $with[] = 'stream';
        }
        
        // handle mime type:
        $mimeType = $this->getMimeType($with, $path);
        
        // handle url:
        $url = $this->getUrl($with, $path);
        
        // handle stream:
        $stream = $this->getStream($with, $path);        
        
        // we check if file exist if stream is null:
        if (is_null($stream)) {
            try {
                if (! $this->flysystem->fileExists($path)) {
                    throw new FileCreateException($path);
                }
            } catch (UnableToCheckExistence $e) {
                throw new FileCreateException($path, '', 0, $e);
            }
        }
        
        // handle image width and height:
        [$width, $height] = $this->getImageWidthAndHeight($with, $mimeType, $url, $stream);
        
        // handle size:
        $size = null;
        
        if ($stream) {
            $size = $stream->getSize();
        } elseif (in_array('size', $with)) {
            try {
                $size = $this->flysystem->fileSize($path);
            } catch (FilesystemException|UnableToRetrieveMetadata $e) {
                $size = null;
            }
        }
        
        return new File(
            storageName: '',
            path: $this->pathNormalizer->normalizePath($path),
            stream: $stream,
            mimeType: $mimeType,
            size: $size,
            width: $width,
            height: $height,
            lastModified: $this->getLastModified($with, $path),
            url: $url,
        );
    }
    
    /**
     * Create a new File from the file attributes.
     *
     * @param FileAttributes $attributes
     * @param array<int, string> $with
     * @return FileInterface
     * @throws FileCreateException
     */
    public function createFileFromFileAttributes(FileAttributes $attributes, array $with = []): FileInterface
    {
        if (in_array('width', $with) || in_array('height', $with)) {
            $with[] = 'mimeType';
            $with[] = 'stream';
        }
        
        if (in_array('size', $with)) {
            $with[] = 'stream';
        }
        
        // handle mimeType:
        $mimeType = $attributes->mimeType() ?: $this->getMimeType($with, $attributes->path());

        // handle url:
        $url = $this->getUrl($with, $attributes->path());
        
        // handle stream:
        try {
            $stream = $this->getStream($with, $attributes->path());
        } catch (FileCreateException $e) {
            $stream = null;
        }
        
        // handle image width and height:
        [$width, $height] = $this->getImageWidthAndHeight($with, $mimeType, $url, $stream);

        return new File(
            storageName: '',
            path: $attributes->path(),
            stream: $stream,
            mimeType: $mimeType,
            size: $attributes->fileSize(),
            width: $width,
            height: $height,
            lastModified: $attributes->lastModified(),
            url: $url,
            metadata: $attributes->extraMetadata(),
        );
    }
    
    /**
     * Returns the stream.
     *
     * @param array<int, string> $with
     * @param string $path
     * @return null|StreamInterface
     */
    protected function getStream(array $with, string $path): null|StreamInterface
    {
        if (!in_array('stream', $with)) {
            return null;
        }
        
        try {
            $resource = $this->flysystem->readStream($path);
            return $this->streamFactory->createStreamFromResource($resource);
        } catch (UnableToReadFile $e) {
            throw new FileCreateException($path, '', 0, $e);
        }
    }

    /**
     * Returns the lastModified.
     *
     * @param array<int, string> $with
     * @param string $path
     * @return null|int
     */
    protected function getLastModified(array $with, string $path): null|int
    {
        if (!in_array('lastModified', $with)) {
            return null;
        }
        
        try {
            return $this->flysystem->lastModified($path);
        } catch (UnableToRetrieveMetadata $e) {
            return null;
        }
    }
    
    /**
     * Returns the mime type.
     *
     * @param array<int, string> $with
     * @param string $path
     * @return null|string
     */
    protected function getMimeType(array $with, string $path): null|string
    {
        if (!in_array('mimeType', $with)) {
            return null;
        }
        
        try {
            return $this->flysystem->mimeType($path);
        } catch (UnableToRetrieveMetadata $e) {
            return null;
        }
    }
    
    /**
     * Returns the url.
     *
     * @param array<int, string> $with
     * @param string $path
     * @return null|string
     */
    protected function getUrl(array $with, string $path): null|string
    {
        if (!in_array('url', $with)) {
            return null;
        }
        
        try {
            return $this->flysystem->publicUrl($path);
        } catch (UnableToGeneratePublicUrl $e) {
            return null;
        }
    }
    
    /**
     * Returns the image width and height.
     *
     * @param array<int, string> $with
     * @param null|string $mimeType
     * @param null|string $url
     * @param null|StreamInterface $stream
     * @return array
     */
    protected function getImageWidthAndHeight(
        array $with,
        null|string $mimeType,
        null|string $url,
        null|StreamInterface $stream,
    ): array {
        if (is_null($mimeType)) {
            return [null, null];
        }

        if (!in_array('width', $with) && !in_array('height', $with)) {
            return [null, null];
        }
        
        if (!in_array(
            $mimeType,
            [
                'image/gif',
                'image/jpeg',
                'image/pjpeg',
                'image/png',
                'image/webp',
                'image/avif',
                'image/tiff',
                'image/bmp',
            ]
        )) {
            return [null, null];
        }

        if ($stream) {
            // this might be quite slow!
            $imageSize = @getimagesizefromstring((string)$stream);
            return is_array($imageSize) ? $imageSize : [null, null];
        }
        
        if (!empty($url)) {
            // this might be quite slow!
            $imageSize = @getimagesize($url);
            return is_array($imageSize) ? $imageSize : [null, null];
        }
        
        return [null, null];
    }
}