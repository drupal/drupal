<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Language.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Views field handler for user language.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user_language")
 */
class Language extends User {

  protected function renderLink($data, ResultRow $values) {
    $uid = $this->getValue($values, 'uid');
    if (!empty($this->options['link_to_user'])) {
      $uid = $this->getValue($values, 'uid');
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

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
