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
 */
function hook_help_topics_info_alter(array &$info) {
  // Alter the help topic to be displayed on admin/help.
  $info['example.help_topic']['top_level'] = TRUE;
}

/**
 * @} End of "addtogroup hooks".
 */
