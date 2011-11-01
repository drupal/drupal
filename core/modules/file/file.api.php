<?php

/**
 * @file
 * Hooks for file module.
 */

/**
 * Control download access to files.
 *
 * The hook is typically implemented to limit access based on the entity the
 * file is referenced, e.g., only users with access to a node should be allowed
 * to download files attached to that node.
 *
 * @param $field
 *   The field to which the file belongs.
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The $entity to which $file is referenced.
 *
 * @return
 *   TRUE is access should be allowed by this entity or FALSE if denied. Note
 *   that denial may be overridden by another entity controller, making this
 *   grant permissive rather than restrictive.
 *
 * @see hook_field_access().
 */
function hook_file_download_access($field, $entity_type, $entity) {
  if ($entity_type == 'node') {
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
 * @param $field
 *   The field to which the file belongs.
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The $entity to which $file is referenced.
 *
 * @return
 *   An array of grants, keyed by module name, each with a Boolean grant value.
 *   Return an empty array to assert FALSE. You may choose to return your own
 *   module's value in addition to other grants or to overwrite the values set by
 *   other modules.
 */
function hook_file_download_access_alter(&$grants, $field, $entity_type, $entity) {
  // For our example module, we always enforce the rules set by node module.
  if (isset($grants['node'])) {
    $grants = array('node' => $grants['node']);
  }
}
