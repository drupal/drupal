<?php
/**
 * @file
 * Contains Drupal\migrate_drupal\Plugin\migrate\d6\FieldInstanceDefaults
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_field_instance_defaults"
 * )
 */
class FieldInstanceDefaults extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Set the field instance defaults.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($widget_type, $widget_settings) = $value;
    $default = array();

    switch ($widget_type) {
      case 'text_textfield':
      case 'number':
      case 'phone_textfield':
        if (!empty($widget_settings['default_value'][0]['value'])) {
          $default['value'] = $widget_settings['default_value'][0]['value'];
        }
        break;

      case 'imagefield_widget':
        // @todo, load the image and populate the defaults.
        // $default['default_image'] = $widget_settings['default_image'];
        break;

      case 'date_select':
        if (!empty($widget_settings['default_value'])) {
          $default['value'] = $widget_settings['default_value'];
        }
        break;

      case 'email_textfield':
        if (!empty($widget_settings['default_value'][0]['email'])) {
          $default['value'] = $widget_settings['default_value'][0]['email'];
        }
        break;

      case 'link':
        if (!empty($widget_settings['default_value'][0]['url'])) {
          $default['title'] = $widget_settings['default_value'][0]['title'];
          $default['url'] = $widget_settings['default_value'][0]['url'];
        }
        break;
    }
    if (!empty($default)) {
      $default = array($default);
    }
    return $default;
  }

}
