<?php

/**
 * @file
 * Definition of Views\locale\Plugin\views\field\LinkEdit.
 */

namespace Views\locale\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to edit a translation.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "locale_link_edit",
 *   module = "locale"
 * )
 */
class LinkEdit extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\HandlerBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['lid'] = 'lid';
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

  public function access() {
    // Ensure user has access to edit translations.
    return user_access('translate interface');
  }

  function render($values) {
    $value = $this->get_value($values, 'lid');
    return $this->render_link($this->sanitizeValue($value), $values);
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'admin/build/translate/edit/' . $data;
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }

}
