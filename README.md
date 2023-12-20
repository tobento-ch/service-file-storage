# File Storage Service

File storage interface for PHP applications using [Flysystem](https://github.com/thephpleague/flysystem) as default implementation.

## Table of Contents

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Highlights](#highlights)
- [Documentation](#documentation)
    - [Create Storage](#create-storage)
    - [File](#file)
        - [Write File](#write-file)
        - [File Exists](#file-exists)
        - [Retrieve File](#retrieve-file)
        - [Retrieve Files](#retrieve-files)
        - [Delete File](#delete-file)
        - [Move File](#move-file)
        - [Copy File](#copy-file)
        - [Available File Attributes](#available-file-attributes)
    - [Folder](#folder)
        - [Create Folder](#create-folder)
        - [Folder Exists](#folder-exists)
        - [Retrieve Folders](#retrieve-folders)
        - [Delete Folder](#delete-folder)
    - [Visibility](#visibility)
        - [Set Visibility](#set-visibility)
    - [Storages](#storages)
        - [Create Storages](#create-storages)
        - [Add Storages](#add-storages)
        - [Get Storage](#get-storage)
        - [Default Storages](#default-storages)
    - [Interfaces](#interfaces)
        - [Storage Factory Interface](#storage-factory-interface)
        - [Storage Interface](#storage-interface)
        - [Storages Interfaces](#storages-interface)
        - [File Interface](#file-interface)
        - [Files Interface](#files-interface)
        - [Folder Interface](#folder-interface)
        - [Folders Interface](#folders-interface)
    - [Flysystem](#flysystem)
        - [Flysystem Storage](#flysystem-storage)
- [Credits](#credits)
___

# Getting started

Add the latest version of the file storage service project running this command.

```
composer require tobento/service-file-storage
```

## Requirements

- PHP 8.0 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design

# Documentation

## Create Storage

Check out the [Flysystem Storage](#flysystem-storage) to create the storage.

## File

### Write File

```php
use Tobento\Service\FileStorage\FileWriteException;

try {
    $storage->write(
        path: 'folder/file.txt',
        content: 'message',
    );
} catch (FileWriteException $e) {
    //
}
```

**supported content**

* ```string```
* ```resource```
* any object implementing ```Stringable```
* ```Psr\Http\Message\StreamInterface```
* ```Tobento\Service\Filesystem\File```

### File Exists

Returns ```true``` if file exists, otherwise ```false```.

```php
$exists = $storage->exists(path: 'folder/image.jpg');

var_dump($exists);
// bool(true)
```

### Retrieve File

Use the with method to retrieve specific file attributes. Check out the [Available File Attributes](#available-file-attributes) for more detail.

```php
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\FileNotFoundException;

try {
    $file = $storage
        ->with('stream', 'mimeType')
        ->file(path: 'folder/image.jpg');

    var_dump($file instanceof FileInterface);
    // bool(true)
} catch (FileNotFoundException $e) {
    //
}
```

Check out the [File Interface](#file-interface) to learn more about it.

### Retrieve Files

Use the with method to retrieve specific file attributes. Check out the [Available File Attributes](#available-file-attributes) for more detail.

```php
use Tobento\Service\FileStorage\FilesInterface;

$files = $storage->with('stream', 'mimeType')->files(
    path: 'folder',
    recursive: false // is default
);

var_dump($files instanceof FilesInterface);
// bool(true)
```

Check out the [Files Interface](#files-interface) to learn more about it.

### Delete File

```php
use Tobento\Service\FileStorage\FileException;

try {
    $storage->delete(path: 'folder/image.jpg');
} catch (FileException $e) {
    // could not delete file
}
```

### Move File

```php
use Tobento\Service\FileStorage\FileException;

try {
    $storage->move(from: 'old/image.jpg', to: 'new/image.jpg');
} catch (FileException $e) {
    // could not move file
}
```

### Copy File

```php
use Tobento\Service\FileStorage\FileException;

try {
    $storage->copy(from: 'old/image.jpg', to: 'new/image.jpg');
} catch (FileException $e) {
    // could not copy file
}
```

### Available File Attributes

```php
$file = $storage
    ->with(
        'stream',
        'mimeType',
        'size', // not needed if stream is set as it can get size from stream.
        'width', 'height', // ignored if not image.
        'lastModified',
        'url',
        'visibility',
    )
    ->file(path: 'folder/image.jpg');

$stream = $file->stream();
$mimeType = $file->mimeType();
$size = $file->size();
$width = $file->width();
$height = $file->height();
$lastModified = $file->lastModified();
$url = $file->url();
$visibility = $file->visibility();
```

Check out the [File Interface](#file-interface) to learn more about it.

## Folder

### Create Folder

```php
use Tobento\Service\FileStorage\FolderException;

try {
    $storage->createFolder(path: 'folder/name');
} catch (FolderException $e) {
    // could not create folder
}
```

### Folder Exists

Returns ```true``` if folder exists, otherwise ```false```.

```php
$exists = $storage->folderExists(path: 'folder/name');

var_dump($exists);
// bool(true)
```

### Retrieve Folders

```php
use Tobento\Service\FileStorage\FoldersInterface;

$folders = $storage->folders(
    path: '',
    recursive: false // is default
);

var_dump($folders instanceof FoldersInterface);
// bool(true)
```

Check out the [Folders Interface](#folders-interface) to learn more about it.

### Delete Folder

Deleting a folder will delete the specified folder and all of its files.

```php
use Tobento\Service\FileStorage\FolderException;

try {
    $storage->deleteFolder(path: 'folder/name');
} catch (FolderException $e) {
    // could not delete folder
}
```

## Visibility

### Set Visibility

```php
use Tobento\Service\FileStorage\Visibility;

$storage->setVisibility(
    path: 'folder/image.jpg',
    visibility: Visibility::PRIVATE // or Visibility::PUBLIC
);
```

## Storages

### Create Storages

```php
use Tobento\Service\FileStorage\Storages;
use Tobento\Service\FileStorage\StoragesInterface;

$storages = new Storages();

var_dump($storages instanceof StoragesInterface);
// bool(true)
```

### Add Storages

**add**

```php
use Tobento\Service\FileStorage\StorageInterface;

$storages->add($storage); // StorageInterface
```

**register**

You may use the register method to only create the storage if requested.

```php
use Tobento\Service\FileStorage\StorageInterface;

$storages->register(
    'name',
    function(string $name): StorageInterface {
        // create storage:
        return $storage;
    }
);
```

### Get Storage

If the storage does not exist or could not get created it throws a StorageException.

```php
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StorageException;

$storage = $storages->get('name');

var_dump($storage instanceof StorageInterface);
// bool(true)

$storages->get('unknown');
// throws StorageException
```

You may use the ```has``` method to check if a storage exists.

```php
var_dump($storages->has('name'));
// bool(false)
```

### Default Storages

You may add default storages for your application design.

```php
use Tobento\Service\FileStorage\Storages;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StorageException;

$storages = new Storages();

// add "locale" storage:
$storages->add($storage);

// add default:
$storages->addDefault(name: 'primary', storage: 'local');

// get default storage for the specified name.
$primaryStorage = $storages->default('primary');

var_dump($primaryStorage instanceof StorageInterface);
// bool(true)

var_dump($storages->hasDefault('primary'));
// bool(true)

var_dump($storages->getDefaults());
// array(1) { ["primary"]=> string(5) "local" }

$storages->default('unknown');
// throws StorageException
```

## Interfaces

### Storage Factory Interface

You may use the storage factory interface for creating storages.

```php
use Tobento\Service\FileStorage\StorageFactoryInterface;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StorageException;

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
```

### Storage Interface

All methods from:

- [File](#file)
- [Folder](#folder)
- [Visibility](#visibility)

**name**

Returns the storage name.

```php
var_dump($storage->name());
// string(5) "local"
```

### Storages Interface

All methods from:

- [Add Storages](#add-storages)
- [Get Storage](#get-storage)
- [Default Storages](#default-storages)

### File Interface

```php
use Tobento\Service\FileStorage\FileInterface;

$file = $storage
    ->with(
        'stream', 'mimeType', 'size', 'width',
        'lastModified', 'url', 'visibility',
    )
    ->file(path: 'folder/image.jpg');
    
var_dump($file instanceof FileInterface);
// bool(true)
```

**Methods**

```php
var_dump($file->path());
// string(16) "folder/image.jpg"

var_dump($file->name());
// string(9) "image.jpg"

var_dump($file->filename());
// string(5) "image"

var_dump($file->extension());
// string(3) "jpg"

var_dump($file->folderPath());
// string(6) "folder"

var_dump($file->stream() instanceof \Psr\Http\Message\StreamInterface);
// bool(true) or NULL

var_dump($file->content());
// string(...) or NULL

var_dump($file->mimeType());
// string(10) "image/jpeg" or NULL

var_dump($file->size());
// int(20042) or NULL

var_dump($file->width());
// int(450) or NULL

var_dump($file->height());
// int(450) or NULL

var_dump($file->lastModified());
// int(1672822057) or NULL

var_dump($file->url());
// string(40) "https://www.example.com/folder/image.jpg" or NULL

var_dump($file->visibility());
// string(6) "public" or NULL

var_dump($file->metadata());
// array(0) { }

var_dump($file->isHtmlImage());
// bool(true)
```

### Files Interface

```php
use Tobento\Service\FileStorage\FilesInterface;

$files = $storage->with('stream', 'mimeType')->files(
    path: 'folder',
    recursive: false // is default
);

var_dump($files instanceof FilesInterface);
// bool(true)

var_dump($files instanceof \IteratorAggregate);
// bool(true)
```

**filter**

Returns a new instance with the filtered files.

```php
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\Visibility;

$files = $files->filter(
    fn(FileInterface $f): bool => $f->visibility() === Visibility::PUBLIC
);
```

**sort**

Returns a new instance with the files sorted.

```php
use Tobento\Service\FileStorage\FileInterface;

$files = $files->sort(
    fn(FileInterface $a, FileInterface $b) => $a->path() <=> $b->path()
);
```

**all**

Returns all files.

```php
use Tobento\Service\FileStorage\FileInterface;

foreach($files->all() as $file) {
    var_dump($file instanceof FileInterface);
    // bool(true)
}

// or just
foreach($files as $file) {}
```

### Folder Interface

```php
use Tobento\Service\FileStorage\FolderInterface;

foreach($storage->folders(path: 'foo') as $folder) {
    var_dump($folder instanceof FolderInterface);
    // bool(true)
}
```

**Methods**

```php
var_dump($folder->path());
// string(7) "foo/bar"

var_dump($folder->parentPath());
// string(3) "foo"

var_dump($folder->name());
// string(3) "bar"

var_dump($folder->lastModified());
// int(1671889402) or NULL

var_dump($folder->visibility());
// string(6) "public" or NULL

var_dump($folder->metadata());
// array(0) { }
```

### Folders Interface

```php
use Tobento\Service\FileStorage\FoldersInterface;

$folders = $storage->folders(path: '');

var_dump($folders instanceof FoldersInterface);
// bool(true)
```

**filter**

Returns a new instance with the filtered folders.

```php
use Tobento\Service\FileStorage\FolderInterface;
use Tobento\Service\FileStorage\Visibility;

$folders = $folders->filter(
    fn(FolderInterface $f): bool => $f->visibility() === Visibility::PUBLIC
);
```

**sort**

Returns a new instance with the folders sorted.

```php
use Tobento\Service\FileStorage\FolderInterface;

$folders = $folders->sort(
    fn(FolderInterface $a, FolderInterface $b) => $a->path() <=> $b->path()
);
```

**first**

Returns the first folder or null if none.

```php
use Tobento\Service\FileStorage\FolderInterface;

$folder = $folders->first();

// null|FolderInterface
```

**get**

Returns the folder by path or null if not exists.

```php
use Tobento\Service\FileStorage\FolderInterface;

$folder = $folders->get(path: 'foo');

// null|FolderInterface
```

**all**

Returns all folders.

```php
use Tobento\Service\FileStorage\FolderInterface;

foreach($folders->all() as $folder) {
    var_dump($folder instanceof FolderInterface);
    // bool(true)
}

// or just
foreach($folders as $folder) {}
```

## Flysystem

Check out the [League Flysystem](https://github.com/thephpleague/flysystem) to learn more about it.

### Flysystem Storage

```php
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\FileStorage\StorageInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

$filesystem = new \League\Flysystem\Filesystem(
    adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
        location: __DIR__.'/root/directory/'
    )
);

$storage = new Flysystem\Storage(
    name: 'local',
    flysystem: $filesystem,
    fileFactory: new Flysystem\FileFactory(
        flysystem: $filesystem,
        streamFactory: new Psr17Factory()
    ),
);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
- [League Flysystem](https://github.com/thephpleague/flysystem)