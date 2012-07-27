<?php
/**
 * @file
 * Definition of Drupal\views\Plugins\views\field\FileSize
 */
namespace Drupal\views\Plugins\views\field;

use Drupal\views\Plugins\views\field\FieldPluginBase;

/**
 * Render a numeric value as a size.
 *
 * @ingroup views_field_handlers
 */
class FileSize extends FieldPluginBase {
  function option_definition() {
    $options = parent::option_definition();

    $options['file_size_display'] = array('default' => 'formatted');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['file_size_display'] = array(
      '#title' => t('File size display'),
      '#type' => 'select',
      '#options' => array(
        'formatted' => t('Formatted (in KB or MB)'),
        'bytes' => t('Raw bytes'),
      ),
    );
  }

  function render($values) {
    $value = $this->get_value($values);
    if ($value) {
      switch ($this->options['file_size_display']) {
        case 'bytes':
          return $value;
        case 'formatted':
        default:
          return format_size($value);
      }
    }
    else {
      return '';
    }
  }
}
