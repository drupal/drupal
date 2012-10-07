<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Username.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to allow linking to a user account or homepage.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment_username",
 *   module = "comment"
 * )
 */
class Username extends FieldPluginBase {

  /**
   * Override init function to add uid and homepage fields.
   */
  public function init(ViewExecutable $view, &$data) {
    parent::init($view, $data);
    $this->additional_fields['uid'] = 'uid';
    $this->additional_fields['homepage'] = 'homepage';
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_user'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_user'] = array(
      '#title' => t("Link this field to its user or an author's homepage"),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_user'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_user'])) {
      $account = entity_create('user', array());
      $account->uid = $this->get_value($values, 'uid');
      $account->name = $this->get_value($values);
      $account->homepage = $this->get_value($values, 'homepage');

      return theme('username', array(
        'account' => $account
      ));
    }
    else {
      return $data;
    }
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
