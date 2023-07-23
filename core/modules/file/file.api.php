<?php

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
 * - Files are validated by hook implementations of hook_file_validate().
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
 *     'file_validate_extensions' => [
 *       'png gif jpg',
 *     ],
 *   ],
 * ];
 * @endcode
 * - Use file_save_upload() to trigger the FileUploadSanitizeNameEvent event and
 *   hook_file_validate().
 *
 * Important considerations, regardless of the form element used:
 * - Always use and validate against a list of allowed extensions.
 * - If the configuration system.file:allow_insecure_uploads is set to TRUE
 *   then potentially insecure files will not be renamed. This setting is not
 *   recommended.
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
 * @see \hook_file_validate()
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
 * Check that files meet a given criteria.
 *
 * This hook lets modules perform additional validation on files. They're able
 * to report a failure by returning one or more error messages.
 *
 * @param \Drupal\file\FileInterface $file
 *   The file entity being validated.
 *
 * @return array
 *   An array of error messages. If there are no problems with the file return
 *   an empty array.
 *
 * @see file_validate()
 */
function hook_file_validate(\Drupal\file\FileInterface $file) {
  $errors = [];

  if (!$file->getFilename()) {
    $errors[] = t("The file's name is empty. Give a name to the file.");
  }
  if (strlen($file->getFilename()) > 255) {
    $errors[] = t("The file's name exceeds the 255 characters limit. Rename the file and try again.");
  }

  return $errors;
}

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
function hook_file_copy(\Drupal\file\FileInterface $file, \Drupal\file\FileInterface $source) {
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
function hook_file_move(\Drupal\file\FileInterface $file, \Drupal\file\FileInterface $source) {
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
