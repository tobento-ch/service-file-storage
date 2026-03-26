# File Storage Service

File storage interface for PHP applications using [Flysystem](https://github.com/thephpleague/flysystem) as default implementation.

## Table of Contents

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Highlights](#highlights)
- [Documentation](#documentation)
    - [Create Storage](#create-storage)
        - [Public Storage](#public-storage)
        - [Private Storage](#private-storage)
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
    - [Storages](#storages)
        - [Create Storages](#create-storages)
        - [Add Storages](#add-storages)
        - [Get Storage](#get-storage)
        - [Default Storages](#default-storages)
    - [Available Storages](#available-storages)
        - [Flysystem Storage](#flysystem-storage)
        - [Null Storage](#null-storage)
        - [Read Only Storage Adapter](#read-only-storage-adapter)
    - [Interfaces](#interfaces)
        - [Storage Factory Interface](#storage-factory-interface)
        - [Storage Interface](#storage-interface)
        - [Storages Interfaces](#storages-interface)
        - [File Interface](#file-interface)
        - [Files Interface](#files-interface)
        - [Folder Interface](#folder-interface)
        - [Folders Interface](#folders-interface)
    - [Repositories](#repositories)
        - [File Repository](#file-repository)
        - [Folder Repository](#folder-repository)
        - [File and Folder Repository](#file-and-folder-repository)
        - [Using Repositories with App CRUD](#using-repositories-with-app-crud)
- [Credits](#credits)
___

# Getting started

Add the latest version of the file storage service project running this command.

```
composer require tobento/service-file-storage
```

## Requirements

- PHP 8.4 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design

# Documentation

## Create Storage

Check out the [Available Storages](#available-storages) section to create storages.

### Public Storage

Public storages are intended for assets that can be accessed directly by end-users.

- **URL available** (when configured, e.g. via `public_url`)
- **No signing required**
- **For public assets**, such as:
  - website images
  - CSS / JS files
  - thumbnails
  - media intended for direct embedding

Public storage is ideal when files should be openly accessible without authentication.

**Create Public Storage**

Check out the [Available Storages](#available-storages) section to create storages using the appropriate storage adapter.  
A public storage is created by setting its `type` to `public`.  
If the storage adapter supports direct URL generation (for example in the [Flysystem Storage](#flysystem-storage)),  
you may configure a `public_url` to enable public file URLs.

**Note**

The storage type does **not** automatically make files public.  
It is a semantic flag that your application and configuration must handle correctly  
(for example by defining a `public_url` or implementing access control).


#### Private Storage

Private storages are intended for files that must **not** be directly exposed.

- **No direct URL**
- **Access only through your application**
- **Can generate signed URLs** (if your app implements this)
- **Used for**, for example:
  - user uploads
  - original images (before processing)
  - protected downloads
  - documents behind authentication
  - editor-only or unpublished assets

Private storage is ideal when you need full control over who can access a file.

**Create Private Storage**

Check out the [Available Storages](#available-storages) section to create storages using the appropriate storage adapter.  
A private storage is created by setting its `type` to `private`.

**Note**

The storage type does **not** automatically make files private.  
It is a semantic flag that your application and configuration must handle correctly.

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
    )
    ->file(path: 'folder/image.jpg');

$stream = $file->stream();
$mimeType = $file->mimeType();
$size = $file->size();
$width = $file->width();
$height = $file->height();
$lastModified = $file->lastModified();
$url = $file->url();
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

## Available Storages

### Flysystem Storage

Check out the [League Flysystem](https://github.com/thephpleague/flysystem) to learn more about it.

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
    type: 'private', // or 'public'
);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

### Null Storage

```php
use Tobento\Service\FileStorage\NullStorage;
use Tobento\Service\FileStorage\StorageInterface;

$storage = new NullStorage(
    name: 'null',
    type: 'private', // or 'public'
);

var_dump($storage instanceof StorageInterface);
// bool(true)
```

### Read Only Storage Adapter

Any storage implementing the ```StorageInterface::class``` can be made read-only by decorating them using the ```ReadOnlyStorageAdapter```:

```php
use Tobento\Service\FileStorage\ReadOnlyStorageAdapter;
use Tobento\Service\FileStorage\StorageInterface;

$storage = new ReadOnlyStorageAdapter(
    storage: $storage, // StorageInterface
    
    // You may throw exeptions if files are not found
    // otherwise an "empty" file is returned.
    throw: true, // false is default
);

var_dump($storage instanceof StorageInterface);
// bool(true)
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

**name**

Returns the storage name.

```php
var_dump($storage->name());
// string(5) "local"
```

**type**

Returns the storage type (`public` or `private`).

```php
var_dump($storage->type());
// string(6) "public"
```

**isPublic**

Returns `true` if the storage is public.

```php
var_dump($storage->isPublic());
// bool(true)
```

**isPrivate**

Returns `true` if the storage is private.

```php
var_dump($storage->isPrivate());
// bool(false)
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
        'lastModified', 'url',
    )
    ->file(path: 'folder/image.jpg');
    
var_dump($file instanceof FileInterface);
// bool(true)
```

**Methods**

```php
var_dump($file->storageName());
// string(5) "local"

// Modify storage name returning a new instance:
var_dump($file->withStorageName(name: 'foo'));
// string(3) "foo"

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

var_dump($file->humanSize());
// string(5) "15 KB"

var_dump($file->width());
// int(450) or NULL

var_dump($file->height());
// int(450) or NULL

var_dump($file->lastModified());
// int(1672822057) or NULL

var_dump($file->url());
// string(40) "https://www.example.com/folder/image.jpg" or NULL

// Modify url returning a new instance:
$file = $file->withUrl('https://www.example.com/folder/image.jpg');

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

$files = $files->filter(
    fn(FileInterface $f): bool => in_array($f->mimeType(), ['image/jpeg'])
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
var_dump($folder->storageName());
// string(5) "local"

// Modify storage name returning a new instance:
var_dump($folder->withStorageName(name: 'foo'));
// string(3) "foo"

var_dump($folder->path());
// string(7) "foo/bar"

var_dump($folder->parentPath());
// string(3) "foo"

var_dump($folder->name());
// string(3) "bar"

var_dump($folder->lastModified());
// int(1671889402) or NULL

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

$folders = $folders->filter(
    fn(FolderInterface $f): bool => in_array($f->storageName(), ['local'])
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

## Repositories

The file storage service provides optional **repository abstractions** for querying files and folders using a consistent, storage-agnostic API.  
Repositories allow you to filter, sort, and retrieve filesystem items in a structured way, similar to database repositories.

Using repositories is **optional**.  
To enable them, install the following packages:

- [tobento/service-repository](https://github.com/tobento-ch/service-repository)  
  Provides the base repository interfaces and exceptions.

- [tobento/service-repository-storage](https://github.com/tobento-ch/service-repository-storage)  
  Provides repository implementations backed by storage services.

- [tobento/service-storage](https://github.com/tobento-ch/service-storage)  
  Required for storage-based repositories (files, folders, etc.).

```
composer require tobento/service-repository tobento/service-repository-storage tobento/service-storage
```

Repositories can also be used together with  
[tobento/app-crud](https://github.com/tobento-ch/app-crud)  
to build CRUD interfaces or file manager UIs, as the repository API is fully compatible out of the box.  
This integration is optional and not required to use repositories.

Repositories also integrate seamlessly with  
[tobento/app-search](https://github.com/tobento-ch/app-search),  
allowing you to make files and folders searchable using the `RepositorySearchable` adapter.  
This integration is optional and not required to use repositories.

### File Repository

The **File Repository** offers a structured, storage-agnostic way to query files from a storage location.  
It supports filtering, sorting, limits, recursive traversal, and root folder scoping.

The repository is provided by  
[tobento/service-repository-storage](https://github.com/tobento-ch/service-repository-storage),  
which contains the full repository documentation, and relies on  
[tobento/service-repository](https://github.com/tobento-ch/service-repository)  
for the base repository interfaces.

```php
use Tobento\Service\FileStorage\Repository\FileRepository;

$repository = new FileRepository(storage: $storage);

// Configure repository returing a new instance:
$repository = $repository->withStorage($anotherStorage);

$repository = $repository->withRootFolder('images/'); // default = ''

$repository = $repository->withFileAttributes(['size', 'lastModified']);

$repository = $repository->withRecursive(true); // default false
```

By default, all file attributes are loaded, and recursive mode is disabled (`false`).  
See the list of available attributes under  
[Available File Attributes](#available-file-attributes).

#### Creation Behavior

**1. FileSource-driven creation (no `content` provided)**

If the `content` attribute is not provided, the repository assumes that:

- a [FileSource Field - App CRUD](https://github.com/tobento-ch/app-crud#filesource-field) or another external component has already written the file to storage
- the repository should only resolve and return the file entity

```php
$file = $repository->create([
    'path' => 'images/photo.jpg',
]);
```

In this mode:
- No write operation is performed
- The repository attempts to load the file using `findById()`
- If the file does not exist, a `RepositoryCreateException` is thrown

This is ideal for upload pipelines where the file is already stored before the repository is invoked.

**2. Repository-driven creation (with `content`)**

If the `content` attribute is provided, the repository writes the file to storage:

```php
$file = $repository->create([
    'path' => 'docs/readme.txt',
    'content' => 'Hello World',
]);
```

Steps performed:
1. The repository writes the file using `StorageInterface::write()`
2. It then resolves the file entity via `findById()`
3. If the file cannot be resolved, a `RepositoryCreateException` is thrown

### Folder Repository

The **Folder Repository** provides a structured, storage-agnostic way to query folders from a storage location.  
It supports filtering, sorting, limits, recursive traversal, and root folder scoping.

The repository is provided by  
[tobento/service-repository-storage](https://github.com/tobento-ch/service-repository-storage),  
which contains the full repository documentation, and relies on  
[tobento/service-repository](https://github.com/tobento-ch/service-repository)  
for the base repository interfaces.

```php
use Tobento\Service\FileStorage\Repository\FolderRepository;

$repository = new FolderRepository(storage: $storage);

// Configure repository returning a new instance:
$repository = $repository->withStorage($anotherStorage);

$repository = $repository->withRootFolder('images/'); // default = ''

$repository = $repository->withRecursive(true); // default false
```

By default, all folder data is loaded, and recursive mode is disabled (`false`).

#### Creation Behavior

The repository creates folders using the underlying storage's `createFolder()` method.
After creation, the repository resolves the folder entity using `findById()` to ensure it exists and is fully hydrated.

```php
$folder = $repository->create([
    'path' => 'images/gallery/',
]);
```

Steps performed:
1. The repository validates that a valid `path` is provided
2. It creates the folder using `StorageInterface::createFolder()`
3. It resolves the folder entity via `findById()`
4. If the folder cannot be resolved, a `RepositoryCreateException` is thrown

### File and Folder Repository

The **File and Folder Repository** provides a unified way to query both files and folders from a storage location.  
It supports filtering, sorting, limits, recursive traversal, and root folder scoping, returning a mixed collection of filesystem items.

The repository is provided by  
[tobento/service-repository-storage](https://github.com/tobento-ch/service-repository-storage),  
which contains the full repository documentation, and relies on  
[tobento/service-repository](https://github.com/tobento-ch/service-repository)  
for the base repository interfaces.

```php
use Tobento\Service\FileStorage\Repository\FileRepository;
use Tobento\Service\FileStorage\Repository\FolderRepository;
use Tobento\Service\FileStorage\Repository\FileFolderRepository;

$fileRepo = new FileRepository(storage: $storage);
$folderRepo = new FolderRepository(storage: $storage);

$repository = new FileFolderRepository(
    fileRepository: $fileRepo,
    folderRepository: $folderRepo,
);

// Configure repository returning a new instance:
$repository = $repository->withStorage($anotherStorage);

$repository = $repository->withRootFolder('images/'); // default = ''

$repository = $repository->withRecursive(true); // default false
```

By default, recursive mode is disabled (`false`), and all file and folder data is loaded.

#### Creation Behavior

**1. Explicit type (`file` or `folder`)**

If the `type` attribute is provided, it takes precedence:

```php
$repository->create([
    'type' => 'folder',
    'path' => 'projects/2025/',
]);
```

This directly delegates to either:
- `FileRepository::create()`
- `FolderRepository::create()`

**2. Content provided: `file`**

If the `content` attribute exists, the repository treats the entity as a file:

```php
$repository->create([
    'path' => 'docs/readme.txt',
    'content' => 'Hello World',
]);
```

This mirrors the behavior of the `FileRepository`.

**3. Path contains a file extension: `file`**

If no explicit `type` is given and no `content` is provided, the repository inspects the `path`:

```php
$repository->create([
    'path' => 'images/photo.jpg',
]);
```

If the `path` contains a file extension (e.g. .jpg, .png, .txt), the repository assumes it is a file.

This makes the API intuitive and filesystem-friendly.

**4. Default behavior: `folder`**

If none of the above conditions apply, the repository treats the entity as a folder:

```php
$repository->create([
    'path' => 'archives/2024/',
]);
```

This mirrors the behavior of the FolderRepository.

### Using Repositories with App CRUD

The File, Folder, and FileFolder repositories can be used directly with [tobento/app-crud](https://github.com/tobento-ch/app-crud) to build full CRUD interfaces for file storage. However, because file paths behave differently from typical numeric IDs, there are two important considerations.

#### 1. Routing for Recursive Paths

File and folder identifiers are paths, not integers.
When recursive mode is enabled, IDs may contain slashes:

```
folder/subfolder/file.txt
```

This requires a custom route parameter pattern.

**Default CRUD routing is not sufficient**

```
/files/{id}
```

will stop at the first slash and break for nested paths.

**Correct routing configuration**

```php
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\Service\Routing\RouterInterface;

class RoutesBoot extends Boot
{
    public const BOOT = [
        // you may ensure the crud boot:
        Crud::class,
    ];
    
    public function boot(Crud $crud, RouterInterface $router)
    {
        $crud->routeController(
            controller: App\FileStorageController::class,
            whereId: '[\pL\pN _\-%\.]+', // for non-recursive
        );
        
        // Required for recursive paths (folder/file.txt):
        $router->get('{?locale}/files/{id*}', [App\FileStorageController::class, 'show'])
            ->name('files.show');
    }
}
```

**Why this is needed**

- `{id*}` allows the ID to contain slashes
- Without it, CRUD cannot resolve nested file paths
- This applies to `show`, `edit`, `update`, and `delete` routes
- This applies to all routes that accept an entity ID - typically `show`, and `delete`. (Edit and update are not supported for file storage repositories.)

#### 2. Entity ID Name and Entity Mapping

CRUD needs to know which attribute represents the entity's primary key.
For file storage, this is not always the filename.

```php
class FileStorageController extends AbstractCrudController
{
    public const RESOURCE_NAME = 'files';

    public function __construct(
        FileStorageRepository $repository,
    ) {
        $this->repository = $repository;
    }

    public function entityIdName(): string
    {
        // The full path is the only reliable unique identifier.
        // In recursive mode, filenames repeat across folders:
        //   "folder1/image.jpg"
        //   "folder2/image.jpg"
        // Using 'name' would cause collisions.
        //
        // Therefore, always use 'path' as the entity ID.
        return 'path';

        // If recursion is disabled, you *may* use 'name' instead:
        // return 'name';
        //
        // But this only works when all files are in a single directory.
    }
    
    public function createEntityFromObject(object $object): EntityInterface
    {
        // Files and Folders:
        $attributes = [
            'path' => $object->path(),
            'name' => $object->name(),
            'last_modified' => $object->lastModified(),
        ];

        // File-specific attributes:
        if ($object instanceof \Tobento\Service\FileStorage\File) {
            $attributes['extension'] = $object->extension();
            $attributes['mimetype'] = $object->mimeType();
            $attributes['size'] = $object->humanSize();
        }

        return new Entity(
            attributes: $attributes,
            idAttributeName: $this->entityIdName(),
        );
    }
}
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
- [League Flysystem](https://github.com/thephpleague/flysystem)