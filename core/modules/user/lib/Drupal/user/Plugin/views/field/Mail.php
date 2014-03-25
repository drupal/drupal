<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Mail.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to provide access control for the email field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_mail")
 */
class Mail extends User {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_user'] = array('default' => 'mailto');
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['link_to_user'] = array(
      '#title' => t('Link this field'),
      '#type' => 'radios',
      '#options' => array(
        0 => t('No link'),
        'user' => t('To the user'),
        'mailto' => t("With a mailto:"),
      ),
      '#default_value' => $this->options['link_to_user'],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink($data, ResultRow $values) {
    parent::renderLink($data, $values);

    if ($this->options['link_to_user'] == 'mailto') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "mailto:" . $data;
    }

    return $data;
  }

}
