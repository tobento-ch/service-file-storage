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
 * StoragesInterface
 */
interface StoragesInterface
{
    /**
     * Add a storage.
     *
     * @param StorageInterface $storage
     * @return static $this
     */
    public function add(StorageInterface $storage): static;
    
    /**
     * Register a storage.
     *
     * @param string $name The storage name.
     * @param callable $storage
     * @return static $this
     */
    public function register(string $name, callable $storage): static;
    
    /**
     * Returns the storage by name.
     *
     * @param string $name The storage name
     * @return StorageInterface
     * @throws StorageException
     */
    public function get(string $name): StorageInterface;
    
    /**
     * Returns true if the storage exists, otherwise false.
     *
     * @param string $name The storage name.
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Adds a default name for the specified storage.
     *
     * @param string $name The default name.
     * @param string $storage The storage name.
     * @return static $this
     */
    public function addDefault(string $name, string $storage): static;

    /**
     * Get the default storages.
     *
     * @return array<string, string> ['name' => 'storage']
     */
    public function getDefaults(): array;
    
    /**
     * Get the storage for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return StorageInterface
     * @throws StorageException
     */
    public function default(string $name): StorageInterface;
    
    /**
     * Returns true if the default storage exists, otherwise false.
     *
     * @param string $name The default name.
     * @return bool
     */
    public function hasDefault(string $name): bool;
}