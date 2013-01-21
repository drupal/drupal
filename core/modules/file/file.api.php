<?php

/**
 * @file
 * Hooks for file module.
 */


/**
 * Act on a newly created file.
 *
 * This hook runs after a new file object has just been instantiated. It can be
 * used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\file\Plugin\Core\Entity\File $file
 *   The file object.
 */
function hook_file_create(\Drupal\file\Plugin\Core\Entity\File $file) {
  if (!isset($file->foo)) {
    $file->foo = 'some_initial_value';
  }
}

/**
 * Load additional information into file entities.
 *
 * file_load_multiple() calls this hook to allow modules to load
 * additional information into each file.
 *
 * @param $files
 *   An array of file entities, indexed by fid.
 *
 * @see file_load_multiple()
 * @see file_load()
 */
function hook_file_load($files) {
  // Add the upload specific data into the file entity.
  $result = db_query('SELECT * FROM {upload} u WHERE u.fid IN (:fids)', array(':fids' => array_keys($files)))->fetchAll(PDO::FETCH_ASSOC);
  foreach ($result as $record) {
    foreach ($record as $key => $value) {
      $files[$record['fid']]->$key = $value;
    }
  }
}

/**
 * Check that files meet a given criteria.
 *
 * This hook lets modules perform additional validation on files. They're able
 * to report a failure by returning one or more error messages.
 *
 * @param Drupal\file\File $file
 *   The file entity being validated.
 * @return
 *   An array of error messages. If there are no problems with the file return
 *   an empty array.
 *
 * @see file_validate()
 */
function hook_file_validate(Drupal\file\File $file) {
  $errors = array();

  if (empty($file->filename)) {
    $errors[] = t("The file's name is empty. Please give a name to the file.");
  }
  if (strlen($file->filename) > 255) {
    $errors[] = t("The file's name exceeds the 255 characters limit. Please rename the file and try again.");
  }

  return $errors;
}

/**
 * Act on a file being inserted or updated.
 *
 * This hook is called when a file has been added to the database. The hook
 * doesn't distinguish between files created as a result of a copy or those
 * created by an upload.
 *
 * @param Drupal\file\File $file
 *   The file entity that is about to be created or updated.
 */
function hook_file_presave(Drupal\file\File $file) {
  // Change the file timestamp to an hour prior.
  $file->timestamp -= 3600;
}

/**
 * Respond to a file being added.
 *
 * This hook is called after a file has been added to the database. The hook
 * doesn't distinguish between files created as a result of a copy or those
 * created by an upload.
 *
 * @param Drupal\file\File $file
 *   The file that has been added.
 */
function hook_file_insert(Drupal\file\File $file) {
  // Add a message to the log, if the file is a jpg
  $validate = file_validate_extensions($file, 'jpg');
  if (empty($validate)) {
    watchdog('file', 'A jpg has been added.');
  }
}

/**
 * Respond to a file being updated.
 *
 * This hook is called when an existing file is saved.
 *
 * @param Drupal\file\File $file
 *   The file that has just been updated.
 */
function hook_file_update(Drupal\file\File $file) {
  $file_user = user_load($file->uid);
  // Make sure that the file name starts with the owner's user name.
  if (strpos($file->filename, $file_user->name) !== 0) {
    $old_filename = $file->filename;
    $file->filename = $file_user->name . '_' . $file->filename;
    $file->save();

    watchdog('file', t('%source has been renamed to %destination', array('%source' => $old_filename, '%destination' => $file->filename)));
  }
}

/**
 * Respond to a file that has been copied.
 *
 * @param Drupal\file\File $file
 *   The newly copied file entity.
 * @param Drupal\file\File $source
 *   The original file before the copy.
 *
 * @see file_copy()
 */
function hook_file_copy(Drupal\file\File $file, Drupal\file\File $source) {
  $file_user = user_load($file->uid);
  // Make sure that the file name starts with the owner's user name.
  if (strpos($file->filename, $file_user->name) !== 0) {
    $file->filename = $file_user->name . '_' . $file->filename;
    $file->save();

    watchdog('file', t('Copied file %source has been renamed to %destination', array('%source' => $source->filename, '%destination' => $file->filename)));
  }
}

/**
 * Respond to a file that has been moved.
 *
 * @param Drupal\file\File $file
 *   The updated file entity after the move.
 * @param Drupal\file\File $source
 *   The original file entity before the move.
 *
 * @see file_move()
 */
function hook_file_move(Drupal\file\File $file, Drupal\file\File $source) {
  $file_user = user_load($file->uid);
  // Make sure that the file name starts with the owner's user name.
  if (strpos($file->filename, $file_user->name) !== 0) {
    $file->filename = $file_user->name . '_' . $file->filename;
    $file->save();

    watchdog('file', t('Moved file %source has been renamed to %destination', array('%source' => $source->filename, '%destination' => $file->filename)));
  }
}

/**
 * Act prior to file deletion.
 *
 * This hook is invoked when deleting a file before the file is removed from the
 * filesystem and before its records are removed from the database.
 *
 * @param Drupal\file\File $file
 *   The file that is about to be deleted.
 *
 * @see hook_file_delete()
 * @see Drupal\file\FileStorageController::delete()
 * @see upload_file_delete()
 */
function hook_file_predelete(Drupal\file\File $file) {
  // Delete all information associated with the file.
  db_delete('upload')->condition('fid', $file->fid)->execute();
}

/**
 * Respond to file deletion.
 *
 * This hook is invoked after the file has been removed from
 * the filesystem and after its records have been removed from the database.
 *
 * @param Drupal\file\File $file
 *   The file that has just been deleted.
 *
 * @see hook_file_predelete()
 * @see Drupal\file\FileStorageController::delete()
 */
function hook_file_delete(Drupal\file\File $file) {
  // Delete all information associated with the file.
  db_delete('upload')->condition('fid', $file->fid)->execute();
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
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity which references the file.
 * @param Drupal\file\File $file
 *   The file entity that is being requested.
 *
 * @return
 *   TRUE is access should be allowed by this entity or FALSE if denied. Note
 *   that denial may be overridden by another entity controller, making this
 *   grant permissive rather than restrictive.
 *
 * @see hook_field_access().
 */
function hook_file_download_access($field, Drupal\Core\Entity\EntityInterface $entity, Drupal\file\File $file) {
  if ($entity->entityType() == 'node') {
    return node_access('view', $entity);
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
