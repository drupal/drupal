<?php

/**
 * @file
 * Definition of Views\locale\Plugin\views\field\NodeLanguage.
 */

namespace Views\locale\Plugin\views\field;

use Views\node\Plugin\views\field\Node;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to translate a language into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_language",
 *   module = "locale"
 * )
 */
class NodeLanguage extends Node {

  function option_definition() {
    $options = parent::option_definition();
    $options['native_language'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['native_language'] = array(
      '#title' => t('Native language'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['native_language'],
      '#description' => t('If enabled, the native name of the language will be displayed'),
    );
  }

  function render($values) {
    $languages = views_language_list(empty($this->options['native_language']) ? 'name' : 'native');
    $value = $this->get_value($values);
    $value = isset($languages[$value]) ? $languages[$value] : '';
    return $this->render_link($value, $values);
  }

}
