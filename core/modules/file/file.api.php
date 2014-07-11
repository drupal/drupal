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
 * Control download access to files.
 *
 * The hook is typically implemented to limit access based on the entity that
 * references the file; for example, only users with access to a node should be
 * allowed to download files attached to that node.
 *
 * @param $field
 *   The field to which the file belongs.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity which references the file.
 * @param \Drupal\file\FileInterface $file
 *   The file entity that is being requested.
 *
 * @return
 *   TRUE is access should be allowed by this entity or FALSE if denied. Note
 *   that denial may be overridden by another entity controller, making this
 *   grant permissive rather than restrictive.
 *
 * @see hook_entity_field_access().
 */
function hook_file_download_access($field, Drupal\Core\Entity\EntityInterface $entity, Drupal\file\FileInterface $file) {
  if ($entity->getEntityTypeId() == 'node') {
    return $entity->access('view');
  }
}

/**
 * Alter the access rules applied to a file download.
 *
 * Entities that implement file management set the access rules for their
 * individual files. Module may use this hook to create custom access rules
 * for file downloads.
 *
 * @see hook_file_download_access().
 *
 * @param $grants
 *   An array of grants gathered by hook_file_download_access(). The array is
 *   keyed by the module that defines the entity type's access control; the
 *   values are Boolean grant responses for each module.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - field: The field to which the file belongs.
 *   - entity: The entity which references the file.
 *   - file: The file entity that is being requested.
 *
 * @see hook_file_download_access().
 */
function hook_file_download_access_alter(&$grants, $context) {
  // For our example module, we always enforce the rules set by node module.
  if (isset($grants['node'])) {
    $grants = array('node' => $grants['node']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
