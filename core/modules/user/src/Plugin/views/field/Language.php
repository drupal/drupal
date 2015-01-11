<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Language.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Views field handler for user language.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_language")
 */
class Language extends User {

  /**
   * {@inheritdoc}
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_user'])) {
      $uid = $this->getValue($values, 'uid');
      if ($this->view->getUser()->hasPermission('access user profiles') && $uid) {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = 'user/' . $uid;
      }
    }
    if (empty($data)) {
      $lang = language_default();
    }
    else {
      $lang = \Drupal::languageManager()->getLanguages();
      $lang = $lang[$data];
    }

    return $this->sanitizeValue($lang->getName());
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
