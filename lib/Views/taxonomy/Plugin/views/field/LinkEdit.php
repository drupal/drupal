<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\field\LinkEdit.
 */

namespace Views\taxonomy\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a term edit link.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "term_link_edit",
 *   module = "taxonomy"
 * )
 */
class LinkEdit extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['tid'] = 'tid';
    $this->additional_fields['vid'] = 'vid';
    $this->additional_fields['vocabulary_machine_name'] = array(
      'table' => 'taxonomy_vocabulary',
      'field' => 'machine_name',
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {
    $this->ensureMyTable();
    $this->add_additional_fields();
  }

  function render($values) {
    // Check there is an actual value, as on a relationship there may not be.
    if ($tid = $this->get_value($values, 'tid')) {
      // Mock a term object for taxonomy_term_access(). Use machine name and
      // vid to ensure compatibility with vid based and machine name based
      // access checks. See http://drupal.org/node/995156
      $term = entity_create('taxonomy_term', array(
        'vid' => $values->{$this->aliases['vid']},
        'vocabulary_machine_name' => $values->{$this->aliases['vocabulary_machine_name']},
      ));
      if (taxonomy_term_access('edit', $term)) {
        $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
        return l($text, 'taxonomy/term/'. $tid . '/edit', array('query' => drupal_get_destination()));
      }
    }
  }

}
