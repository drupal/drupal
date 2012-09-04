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
 * @param array $file_item
 *   The array of information about the file to check access for.
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
function hook_file_download_access($file_item, $entity_type, $entity) {
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
 * @param array $file_item
 *   The array of information about the file to alter access for.
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The $entity to which $file is referenced.
 */
function hook_file_download_access_alter(&$grants, $file_item, $entity_type, $entity) {
  // For our example module, we always enforce the rules set by node module.
  if (isset($grants['node'])) {
    $grants = array('node' => $grants['node']);
  }
}
