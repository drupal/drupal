<?php

/**
 * @file
 * Hooks for file module.
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
 * @return
 *   An array of error messages. If there are no problems with the file return
 *   an empty array.
 *
 * @see file_validate()
 */
function hook_file_validate(Drupal\file\FileInterface $file) {
  $errors = array();

  if (!$file->getFilename()) {
    $errors[] = t("The file's name is empty. Please give a name to the file.");
  }
  if (strlen($file->getFilename()) > 255) {
    $errors[] = t("The file's name exceeds the 255 characters limit. Please rename the file and try again.");
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
 * @see file_copy()
 */
function hook_file_copy(Drupal\file\FileInterface $file, Drupal\file\FileInterface $source) {
  // Make sure that the file name starts with the owner's user name.
  if (strpos($file->getFilename(), $file->getOwner()->name) !== 0) {
    $file->setFilename($file->getOwner()->name . '_' . $file->getFilename());
    $file->save();

    \Drupal::logger('file')->notice('Copied file %source has been renamed to %destination', array('%source' => $source->filename, '%destination' => $file->getFilename()));
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
 * @see file_move()
 */
function hook_file_move(Drupal\file\FileInterface $file, Drupal\file\FileInterface $source) {
  // Make sure that the file name starts with the owner's user name.
  if (strpos($file->getFilename(), $file->getOwner()->name) !== 0) {
    $file->setFilename($file->getOwner()->name . '_' . $file->getFilename());
    $file->save();

    \Drupal::logger('file')->notice('Moved file %source has been renamed to %destination', array('%source' => $source->filename, '%destination' => $file->getFilename()));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
