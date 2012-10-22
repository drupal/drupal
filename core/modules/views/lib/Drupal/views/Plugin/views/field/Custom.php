<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Custom.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "custom"
 * )
 */
class Custom extends FieldPluginBase {

  public function query() {
    // do nothing -- to override the parent query.
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    // Override the alter text option to always alter the text.
    $options['alter']['contains']['alter_text'] = array('default' => TRUE, 'bool' => TRUE);
    $options['hide_alter_empty'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the checkbox
    unset($form['alter']['alter_text']);
    unset($form['alter']['text']['#states']);
    unset($form['alter']['help']['#states']);
    $form['#pre_render'][] = 'views_handler_field_custom_pre_render_move_text';
  }

  function render($values) {
    // Return the text, so the code never thinks the value is empty.
    return $this->options['alter']['text'];
  }

}
