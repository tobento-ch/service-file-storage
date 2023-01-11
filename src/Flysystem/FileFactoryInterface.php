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

use Tobento\Service\FileStorage\FileFactoryInterface as BaseFileFactoryInterface;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\FileCreateException;
use League\Flysystem\FileAttributes;

/**
 * FileFactoryInterface
 */
interface FileFactoryInterface extends BaseFileFactoryInterface
{
    /**
     * Create a new File from the file attributes.
     *
     * @param FileAttributes $attributes
     * @param array<int, string> $with
     * @return FileInterface
     * @throws FileCreateException
     */
    public function createFileFromFileAttributes(FileAttributes $attributes, array $with = []): FileInterface;
}