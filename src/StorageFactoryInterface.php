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
 * StorageFactoryInterface
 */
interface StorageFactoryInterface
{
    /**
     * Create a new Storage based on the configuration.
     *
     * @param string $name Any storage name.
     * @param array $config Configuration data.
     * @return StorageInterface
     * @throws StorageException
     */
    public function createStorage(string $name, array $config = []): StorageInterface;
}