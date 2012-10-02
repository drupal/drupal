<?php

/**
 * @file
 * Definition of Views\filter\Plugin\views\field\FormatName.
 */

namespace Views\filter\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to output the name of an input format.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "filter_format_name",
 *   module = "filter"
 * )
 */
class FormatName extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

    // Be explicit about the table we are using.
    $this->additional_fields['name'] = array('table' => 'filter_formats', 'field' => 'name');
  }

  public function query() {
    $this->ensureMyTable();
    $this->add_additional_fields();
  }

  function render($values) {
    $format_name = $this->get_value($values, 'name');
    if (!$format_name) {
      // Default or invalid input format.
      // filter_formats() will reliably return the default format even if the
      // current user is unprivileged.
      $format = filter_formats(filter_default_format());
      return $this->sanitizeValue($format->name);
    }
    return $this->sanitizeValue($format_name);
  }

}
