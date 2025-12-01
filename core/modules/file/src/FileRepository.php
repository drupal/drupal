<?php

namespace Drupal\file;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Provides a file entity repository.
 */
class FileRepository implements FileRepositoryInterface {

  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileUsageInterface $fileUsage,
    protected AccountInterface $currentUser,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function writeData(string $data, string $destination, FileExists|int $fileExists = FileExists::Rename): FileInterface {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    if (!$this->streamWrapperManager->isValidUri($destination)) {
      throw new InvalidStreamWrapperException("Invalid stream wrapper: {$destination}");
    }
    $uri = $this->fileSystem->saveData($data, $destination, $fileExists);
    return $this->createOrUpdate($uri, $destination, $fileExists === FileExists::Rename);
  }

  /**
   * Create a file entity or update if it exists.
   *
   * @param string $uri
   *   The file URI.
   * @param string $destination
   *   The destination URI.
   * @param bool $rename
   *   Whether to rename the file.
   *
   * @return \Drupal\file\Entity\File|\Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   */
  protected function createOrUpdate(string $uri, string $destination, bool $rename): FileInterface {
    $file = $this->loadByUri($uri);
    if ($file === NULL) {
      $file = File::create(['uri' => $uri]);
      $file->setOwnerId($this->currentUser->id());
    }

    if ($rename && is_file($destination)) {
      $file->setFilename(basename($destination));
    }

    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function copy(FileInterface $source, string $destination, FileExists|int $fileExists = FileExists::Rename): FileInterface {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    if (!$this->streamWrapperManager->isValidUri($destination)) {
      throw new InvalidStreamWrapperException("Invalid stream wrapper: {$destination}");
    }
    $uri = $this->fileSystem->copy($source->getFileUri(), $destination, $fileExists);

    // If we are replacing an existing file, load it.
    if ($fileExists === FileExists::Replace && $existing = $this->loadByUri($uri)) {
      $file = $existing;
    }
    else {
      $file = $source->createDuplicate();
      $file->setFileUri($uri);

      // If we are renaming around an existing file (rather than a directory),
      // use its basename for the filename.
      if ($fileExists === FileExists::Rename && is_file($destination)) {
        $file->setFilename(basename($destination));
      }
      else {
        $file->setFilename(basename($uri));
      }
    }
    $file->save();

    // Inform modules that the file has been copied.
    $this->moduleHandler->invokeAll('file_copy', [$file, $source]);

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function move(FileInterface $source, string $destination, FileExists|int $fileExists = FileExists::Rename): FileInterface {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    if (!$this->streamWrapperManager->isValidUri($destination)) {
      throw new InvalidStreamWrapperException("Invalid stream wrapper: {$destination}");
    }
    $uri = $this->fileSystem->move($source->getFileUri(), $destination, $fileExists);
    $delete_source = FALSE;

    $file = clone $source;
    $file->setFileUri($uri);
    // If we are replacing an existing file re-use its database record.
    if ($fileExists === FileExists::Replace) {
      if ($existing = $this->loadByUri($uri)) {
        $delete_source = TRUE;
        $file->fid = $existing->id();
        $file->uuid = $existing->uuid();
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    elseif ($fileExists === FileExists::Rename && is_file($destination)) {
      $file->setFilename(basename($destination));
    }

    $file->save();

    // Inform modules that the file has been moved.
    $this->moduleHandler->invokeAll('file_move', [$file, $source]);

    // Delete the original if it's not in use elsewhere.
    if ($delete_source && !$this->fileUsage->listUsage($source)) {
      $source->delete();
    }

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUri(string $uri): ?FileInterface {
    $fileStorage = $this->entityTypeManager->getStorage('file');
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $fileStorage->loadByProperties(['uri' => $uri]);
    if (count($files)) {
      foreach ($files as $item) {
        // Since some database servers sometimes use a case-insensitive
        // comparison by default, double check that the filename is an exact
        // match.
        if ($item->getFileUri() === $uri) {
          return $item;
        }
      }
    }
    return NULL;
  }

}
