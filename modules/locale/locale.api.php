<?php
// $Id: locale.api.php,v 1.1 2008/11/25 02:37:32 webchick Exp $

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
 * @} End of "addtogroup hooks".
 */
