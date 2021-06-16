<?php

/**
 * @file
 * Hooks provided by the Help Topics module.
 */

/**
 * @defgroup help_docs Help and documentation
 * @{
 * Documenting modules, themes, and install profiles
 *
 * @section sec_topics Help Topics
 * Modules, themes, and install profiles can have a subdirectory help_topics
 * that contains one or more Help Topics, to provide help to administrative
 * users. These are shown on the main admin/help page. See
 * @link https://www.drupal.org/docs/develop/documenting-your-project/help-topic-standards Help Topic Standards @endlink
 * for more information.
 *
 * @section sec_hook hook_help
 * Modules can implement hook_help() to provide a module overview (shown on the
 * main admin/help page). This hook implementation can also provide help text
 * that is shown in the Help block at the top of administrative pages. See the
 * hook_help() documentation and
 * @link https://www.drupal.org/docs/develop/documenting-your-project/help-text-standards Help text standards @endlink
 * for more information.
 *
 * @section sec_tour Tours
 * Modules can provide tours of administrative pages by creating tour config
 * files and placing them in their config/optional subdirectory. See
 * @link https://www.drupal.org/docs/8/api/tour-api/overview Tour API overview @endlink
 * for more information. The contributed
 * @link https://www.drupal.org/project/tour_ui Tour UI module @endlink
 * can also be used to create tour config files.
 * @}
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
