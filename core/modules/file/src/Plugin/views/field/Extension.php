<?php

/**
 * @file
 * Definition of views_handler_field_file_extension.
 */

namespace Drupal\file\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Returns a pure file extension of the file, for example 'module'.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_extension")
 */
class Extension extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['extension_detect_tar'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['extension_detect_tar'] = array(
      '#type' => 'checkbox',
      '#title' => t('Detect if tar is part of the extension'),
      '#description' => t("See if the previous extension is '.tar' and if so, add that, so we see 'tar.gz' or 'tar.bz2' instead of just 'gz'."),
      '#default_value' => $this->options['extension_detect_tar'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!$this->options['extension_detect_tar']) {
      if (preg_match('/\.([^\.]+)$/', $value, $match)) {
        return $this->sanitizeValue($match[1]);
      }
    }
    else {
      $file_parts = explode('.', basename($value));
      // If there is an extension.
      if (count($file_parts) > 1) {
        $extension = array_pop($file_parts);
        $last_part_in_name = array_pop($file_parts);
        if ($last_part_in_name === 'tar') {
          $extension = 'tar.' . $extension;
        }
        return $this->sanitizeValue($extension);
      }
    }
  }

}
