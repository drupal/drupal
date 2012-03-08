<?php

/**
 * @file
 * Hooks provided by the Locale module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to define their own text groups that can be translated.
 *
 * @param $op
 *   Type of operation. Currently, only supports 'groups'.
 */
function hook_locale($op = 'groups') {
  switch ($op) {
    case 'groups':
      return array('custom' => t('Custom'));
  }
}

/**
 * Allow modules to react to language settings changes.
 *
 * Every module needing to act when the number of enabled languages changes
 * should implement this. This is an "internal" hook and should not be invoked
 * elsewhere. The typical implementation would trigger some kind of rebuilding,
 * this way system components could properly react to the change of the enabled
 * languages number.
 */
function hook_multilingual_settings_changed() {
  field_info_cache_clear();
}

/**
 * @} End of "addtogroup hooks".
 */
