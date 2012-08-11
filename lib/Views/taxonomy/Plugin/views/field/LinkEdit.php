<?php

/**
 * @file
 * Definition of views_handler_field_term_link_edit.
 */

namespace Views\taxonomy\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a term edit link.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   id = "term_link_edit"
 * )
 */
class LinkEdit extends FieldPluginBase {
  function construct() {
    parent::construct();
    $this->additional_fields['tid'] = 'tid';
    $this->additional_fields['vid'] = 'vid';
    $this->additional_fields['vocabulary_machine_name'] = array(
      'table' => 'taxonomy_vocabulary',
      'field' => 'machine_name',
    );
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::options_form($form, $form_state);
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    // Mock a term object for taxonomy_term_access(). Use machine name and
    // vid to ensure compatibility with vid based and machine name based
    // access checks. See http://drupal.org/node/995156
    $term = entity_create('taxonomy_term', array(
      'vid' => $values->{$this->aliases['vid']},
      'vocabulary_machine_name' => $values->{$this->aliases['vocabulary_machine_name']},
    ));
    if (taxonomy_term_access('edit', $term)) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
      $tid = $this->get_value($values, 'tid');
      return l($text, 'taxonomy/term/'. $tid . '/edit', array('query' => drupal_get_destination()));
    }
  }
}
