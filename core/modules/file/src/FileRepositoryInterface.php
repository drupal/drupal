<?php

namespace Drupal\file;

use Drupal\Core\File\FileSystemInterface;

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
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and throw a
   *     \Drupal\Core\File\Exception\FileExistsException.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Thrown when there is an error writing to the file system.
   * @throws \Drupal\Core\File\Exception\FileExistsException
   *   Thrown when the destination exists and $replace is set to
   *   FileSystemInterface::EXISTS_ERROR.
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   Thrown when the destination is an invalid stream wrapper.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   *
   * @see \Drupal\Core\File\FileSystemInterface::saveData()
   */
  public function writeData(string $data, string $destination, int $replace = FileSystemInterface::EXISTS_RENAME): FileInterface;

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
   *   $replace parameter. FileSystemInterface::EXISTS_REPLACE will error out.
   *   FileSystemInterface::EXISTS_RENAME will rename the file until the
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
   * @param int $replace
   *   (optional) Replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and throw a
   *     \Drupal\Core\File\Exception\FileExistsException.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Thrown when there is an error writing to the file system.
   * @throws \Drupal\Core\File\Exception\FileExistsException
   *   Thrown when the destination exists and $replace is set to
   *   FileSystemInterface::EXISTS_ERROR.
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   Thrown when the destination is an invalid stream wrapper.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   *
   * @see \Drupal\Core\File\FileSystemInterface::copy()
   * @see hook_file_copy()
   */
  public function copy(FileInterface $source, string $destination, int $replace = FileSystemInterface::EXISTS_RENAME): FileInterface;

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
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists then its database entry
   *     will be updated and $source->delete() called after invoking
   *     hook_file_move(). If no database entry is found, then the source files
   *     record will be updated.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and throw a
   *     \Drupal\Core\File\Exception\FileExistsException.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   Thrown when there is an error writing to the file system.
   * @throws \Drupal\Core\File\Exception\FileExistsException
   *   Thrown when the destination exists and $replace is set to
   *   FileSystemInterface::EXISTS_ERROR.
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   Thrown when the destination is an invalid stream wrapper.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when there is an error saving the file.
   *
   * @see \Drupal\Core\File\FileSystemInterface::move()
   * @see hook_file_move()
   */
  public function move(FileInterface $source, string $destination, int $replace = FileSystemInterface::EXISTS_RENAME): FileInterface;

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
