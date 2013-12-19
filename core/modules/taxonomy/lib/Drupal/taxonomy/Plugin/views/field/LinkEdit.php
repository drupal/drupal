<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\LinkEdit.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present a term edit link.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("term_link_edit")
 */
class LinkEdit extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['tid'] = 'tid';
    $this->additional_fields['vid'] = 'vid';
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
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Check there is an actual value, as on a relationship there may not be.
    if ($tid = $this->getValue($values, 'tid')) {
      // Mock a term object for taxonomy_term_access(). Use machine name and
      // vid to ensure compatibility with vid based and machine name based
      // access checks. See http://drupal.org/node/995156
      $term = entity_create('taxonomy_term', array(
        'vid' => $values->{$this->aliases['vid']},
      ));
      if ($term->access('update')) {
        $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
        return l($text, 'taxonomy/term/'. $tid . '/edit', array('query' => drupal_get_destination()));
      }
    }
  }

}
