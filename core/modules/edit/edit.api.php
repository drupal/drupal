<?php

/**
 * @file
 * Hooks provided by the Edit module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to alter in-place editor plugin metadata.
 *
 * This hook is called after the in-place editor plugins have been discovered,
 * but before they are cached. Hence any alterations will be cached.
 *
 * @param array &$editors
 *   An array of informations on existing in-place editors, as collected by the
 *   annotation discovery mechanism.
 *
 * @see \Drupal\edit\Annotation\InPlaceEditor
 * @see \Drupal\edit\Plugin\EditorManager
 */
function hook_edit_editor_alter(&$editors) {
  // Cleanly override editor.module's in-place editor plugin.
  $editors['editor']['class'] = 'Drupal\advanced_editor\Plugin\edit\editor\AdvancedEditor';
}

/**
 * @} End of "addtogroup hooks".
 */
