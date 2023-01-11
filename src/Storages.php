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
 * Storages
 */
class Storages implements StoragesInterface
{
    /**
     * @var array<string, callable|StorageInterface>
     */
    protected array $storages = [];
    
    /**
     * @var array<string, string> The default storages. ['pdo' => 'storage']
     */
    protected array $defaults = [];

    /**
     * Create a new Storages.
     *
     * @param StorageInterface ...$storages
     */
    public function __construct(
        StorageInterface ...$storages,
    ) {
        foreach($storages as $storage) {
            $this->add($storage);
        }
    }
    
    /**
     * Add a storage.
     *
     * @param StorageInterface $storage
     * @return static $this
     */
    public function add(StorageInterface $storage): static
    {
        $this->storages[$storage->name()] = $storage;
        return $this;
    }
    
    /**
     * Register a storage.
     *
     * @param string $name The storage name.
     * @param callable $storage
     * @return static $this
     */
    public function register(string $name, callable $storage): static
    {
        $this->storages[$name] = $storage;
        return $this;
    }
    
    /**
     * Returns the storage by name.
     *
     * @param string $name The storage name
     * @return StorageInterface
     * @throws StorageException
     */
    public function get(string $name): StorageInterface
    {
        if (!$this->has($name))
        {
            throw new StorageException('Storage ['.$name.'] not found!');
        }
        
        if (! $this->storages[$name] instanceof StorageInterface)
        {
            try {
                $this->storages[$name] = $this->createStorage($name, $this->storages[$name]);
            } catch(Throwable $e) {
                throw new StorageException($e->getMessage(), 0, $e);
            }
        }
        
        return $this->storages[$name];
    }
    
    /**
     * Returns true if the storage exists, otherwise false.
     *
     * @param string $name The storage name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->storages);
    }

    /**
     * Adds a default name for the specified storage.
     *
     * @param string $name The default name.
     * @param string $storage The storage name.
     * @return static $this
     */
    public function addDefault(string $name, string $storage): static
    {
        $this->defaults[$name] = $storage;
        return $this;
    }

    /**
     * Get the default storages.
     *
     * @return array<string, string> ['name' => 'storage']
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }
    
    /**
     * Get the storage for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return StorageInterface
     * @throws StorageException
     */
    public function default(string $name): StorageInterface
    {
        if (!$this->hasDefault($name)) {
            throw new StorageException('Default storage ['.$name.'] not found!');
        }
        
        return $this->get($this->defaults[$name]);
    }
    
    /**
     * Returns true if the default storage exists, otherwise false.
     *
     * @param string $name The default name.
     * @return bool
     */
    public function hasDefault(string $name): bool
    {
        return array_key_exists($name, $this->defaults);
    }
    
    /**
     * Create a new Storage.
     *
     * @param string $name
     * @param callable $factory
     * @return StorageInterface
     */
    protected function createStorage(string $name, callable $factory): StorageInterface
    {
        return call_user_func_array($factory, [$name]);
    }
}