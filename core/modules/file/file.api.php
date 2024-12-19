<?php

/**
 * @file
 */

use Drupal\file\FileInterface;

/**
 * @file
 * Hooks for file module.
 */

/**
 * @defgroup file File interface
 * @{
 * Common file handling functions.
 *
 * @section file_security Uploading files and security considerations
 *
 * Using \Drupal\file\Element\ManagedFile field with a defined list of allowed
 * extensions is best way to provide a file upload field. It will ensure that:
 * - File names are sanitized by the FileUploadSanitizeNameEvent event.
 * - Files are validated by \Drupal\file\Validation\FileValidatorInterface().
 * - Files with insecure extensions will be blocked by default even if they are
 *   listed. If .txt is an allowed extension such files will be renamed.
 *
 * The \Drupal\Core\Render\Element\File field requires the developer to ensure
 * security concerns are taken care of. To do this, a developer should:
 * - Add the #upload_validators property to the form element. For example,
 * @code
 * $form['file_upload'] = [
 *   '#type' => 'file',
 *   '#title' => $this->t('Upload file'),
 *   '#upload_validators' => [
 *     'FileExtension' => [
 *        'extensions' => 'png gif jpg',
 *       ],
 *     ],
 *   ],
 * ];
 * @endcode
 * - Use file_save_upload() to trigger the FileUploadSanitizeNameEvent event and
 *   \Drupal\file\Validation\FileValidatorInterface::validate().
 *
 * Important considerations, regardless of the form element used:
 * - Always use and validate against a list of allowed extensions.
 * - If the configuration system.file:allow_insecure_uploads is set to TRUE
 *   then potentially insecure files will not be renamed. This setting is not
 *   recommended.
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
 * @see \Drupal\file\Validation\FileValidatorInterface
 * @see file_save_upload()
 * @see \Drupal\Core\File\Event\FileUploadSanitizeNameEvent
 * @see \Drupal\system\EventSubscriber\SecurityFileUploadEventSubscriber
 * @see \Drupal\file\Element\ManagedFile
 * @see \Drupal\Core\Render\Element\File
 *
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Respond to a file that has been copied.
 *
 * @param \Drupal\file\FileInterface $file
 *   The newly copied file entity.
 * @param \Drupal\file\FileInterface $source
 *   The original file before the copy.
 *
 * @see \Drupal\file\FileRepositoryInterface::copy()
 */
function hook_file_copy(FileInterface $file, FileInterface $source): void {
  // Make sure that the file name starts with the owner's user name.
  if (!str_starts_with($file->getFilename(), $file->getOwner()->name)) {
    $file->setFilename($file->getOwner()->name . '_' . $file->getFilename());
    $file->save();

    \Drupal::logger('file')->notice('Copied file %source has been renamed to %destination', ['%source' => $source->filename, '%destination' => $file->getFilename()]);
  }
}

/**
 * Respond to a file that has been moved.
 *
 * @param \Drupal\file\FileInterface $file
 *   The updated file entity after the move.
 * @param \Drupal\file\FileInterface $source
 *   The original file entity before the move.
 *
 * @see \Drupal\file\FileRepositoryInterface::move()
 */
function hook_file_move(FileInterface $file, FileInterface $source): void {
  // Make sure that the file name starts with the owner's user name.
  if (!str_starts_with($file->getFilename(), $file->getOwner()->name)) {
    $file->setFilename($file->getOwner()->name . '_' . $file->getFilename());
    $file->save();

    \Drupal::logger('file')->notice('Moved file %source has been renamed to %destination', ['%source' => $source->filename, '%destination' => $file->getFilename()]);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
