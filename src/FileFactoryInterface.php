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

/**
 * FileFactoryInterface
 */
interface FileFactoryInterface
{
    /**
     * Create a new File from the path.
     *
     * @param string $path
     * @param array<int, string> $with
     * @return FileInterface
     * @throws FileCreateException
     */
    public function createFileFromPath(string $path, array $with = []): FileInterface;
}