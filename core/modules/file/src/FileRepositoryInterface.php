<?php

namespace Drupal\file;

use Drupal\Core\File\FileExists;

/**
 * Performs file system operations and updates database records accordingly.
 */
interface FileRepositoryInterface {

  /**
   * Writes a file to the specified destination and creates a file entity.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param string $destination
   *   A string containing the destination URI. This must be a stream
   *   wrapper URI.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   (optional) The replace behavior when the destination file already exists.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Thrown when there is an error writing to the file system.
   * @throws \Drupal\Core\File\Exception\FileExistsException
   *   Thrown when the destination exists and $replace is set to
   *   FileExists::Error.
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   Thrown when the destination is an invalid stream wrapper.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   *
   * @see \Drupal\Core\File\FileSystemInterface::saveData()
   */
  public function writeData(string $data, string $destination, FileExists|int $fileExists = FileExists::Rename): FileInterface;

  /**
   * Copies a file to a new location and adds a file record to the database.
   *
   * This function should be used when manipulating files that have records
   * stored in the database. This is a powerful function that in many ways
   * performs like an advanced version of copy().
   * - Checks if $source and $destination are valid and readable/writable.
   * - If file already exists in $destination either the call will error out,
   *   replace the file or rename the file based on the $replace parameter.
   * - If the $source and $destination are equal, the behavior depends on the
   *   $replace parameter. FileExists::Replace will error out.
   *   FileExists::Rename will rename the file until the
   *   $destination is unique.
   * - Adds the new file to the files database. If the source file is a
   *   temporary file, the resulting file will also be a temporary file. See
   *   file_save_upload() for details on temporary files.
   *
   * @param \Drupal\file\FileInterface $source
   *   A file entity.
   * @param string $destination
   *   A string containing the destination that $source should be
   *   copied to. This must be a stream wrapper URI.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   (optional) Replace behavior when the destination file already exists.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Thrown when there is an error writing to the file system.
   * @throws \Drupal\Core\File\Exception\FileExistsException
   *   Thrown when the destination exists and $replace is set to
   *   FileExists::Error.
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   Thrown when the destination is an invalid stream wrapper.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   *
   * @see \Drupal\Core\File\FileSystemInterface::copy()
   * @see hook_file_copy()
   */
  public function copy(FileInterface $source, string $destination, FileExists|int $fileExists = FileExists::Rename): FileInterface;

  /**
   * Moves a file to a new location and update the file's database entry.
   *
   * - Checks if $source and $destination are valid and readable/writable.
   * - Performs a file move if $source is not equal to $destination.
   * - If file already exists in $destination either the call will error out,
   *   replace the file or rename the file based on the $replace parameter.
   * - Adds the new file to the files database.
   *
   * @param \Drupal\file\FileInterface $source
   *   A file entity.
   * @param string $destination
   *   A string containing the destination that $source should be moved
   *   to. This must be a stream wrapper URI.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   (optional) The replace behavior when the destination file already exists.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Thrown when there is an error writing to the file system.
   * @throws \Drupal\Core\File\Exception\FileExistsException
   *   Thrown when the destination exists and $replace is set to
   *   FileExists::Error.
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   Thrown when the destination is an invalid stream wrapper.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   *
   * @see \Drupal\Core\File\FileSystemInterface::move()
   * @see hook_file_move()
   */
  public function move(FileInterface $source, string $destination, FileExists|int $fileExists = FileExists::Rename): FileInterface;

  /**
   * Loads the first File entity found with the specified URI.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return \Drupal\file\FileInterface|null
   *   The first file with the matched URI if found, NULL otherwise.
   */
  public function loadByUri(string $uri): ?FileInterface;

}
