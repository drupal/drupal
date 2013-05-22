<?php

/**
 * @file
 * Hooks provided by the Filter module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on filter definitions.
 *
 * @param $info
 *   Array of information on filters exposed by filter plugins.
 */
function hook_filter_info_alter(&$info) {
  // Alter the default settings of the URL filter provided by core.
  $info['filter_url']['default_settings'] = array(
    'filter_url_length' => 100,
  );
}

/**
 * Perform actions when a text format has been disabled.
 *
 * @param $format
 *   The format object of the format being disabled.
 */
function hook_filter_format_disable($format) {
  mymodule_cache_rebuild();
}

/**
 * @} End of "addtogroup hooks".
 */
