<?php

/**
 * @file
 * Definition of views_handler_field_locale_group.
 */

namespace Views\locale\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to translate a group into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "locale_group",
 *   module = "locale"
 * )
 */
class Group extends FieldPluginBase {

  function render($values) {
    $groups = module_invoke_all('locale', 'groups');
    // Sort the list.
    asort($groups);
    $value = $this->get_value($values);
    return isset($groups[$value]) ? $groups[$value] : '';
  }

}
