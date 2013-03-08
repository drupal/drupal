<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Language.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Annotation\Plugin;

/**
 * Views field handler for user language.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_language",
 *   module = "user"
 * )
 */
class Language extends User {

  function render_link($data, $values) {
    $uid = $this->get_value($values, 'uid');
    if (!empty($this->options['link_to_user'])) {
      $uid = $this->get_value($values, 'uid');
      if (user_access('access user profiles') && $uid) {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = 'user/' . $uid;
      }
    }
    if (empty($data)) {
      $lang = language_default();
    }
    else {
      $lang = language_list();
      $lang = $lang[$data];
    }

    return $this->sanitizeValue($lang->name);
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
