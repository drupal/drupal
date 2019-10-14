<?php

/**
 * @file
 * Hooks provided by the Help Topics module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on help topic definitions.
 *
 * @param array $info
 *   Array of help topic plugin definitions keyed by their plugin ID.
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
function hook_help_topics_info_alter(array &$info) {
  // Alter the help topic to be displayed on admin/help.
  $info['example.help_topic']['top_level'] = TRUE;
}

/**
 * @} End of "addtogroup hooks".
 */
