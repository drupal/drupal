<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

// cspell:ignore imagefield

/**
 * Determines the default field settings.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('d6_field_instance_defaults')]
class FieldInstanceDefaults extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * Set the field instance defaults.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$widget_type, $widget_settings] = $value;
    $default = [];

    switch ($widget_type) {
      case 'text_textfield':
      case 'number':
      case 'phone_textfield':
        if (!empty($widget_settings['default_value'][0]['value'])) {
          $default['value'] = $widget_settings['default_value'][0]['value'];
        }
        break;

      case 'imagefield_widget':
        // @todo load the image and populate the defaults.
        // $default['default_image'] = $widget_settings['default_image'];
        break;

      case 'date_select':
        if (!empty($widget_settings['default_value'])) {
          $default['default_date_type'] = 'relative';
          $default['default_date'] = $widget_settings['default_value'];
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
          $default['uri'] = $widget_settings['default_value'][0]['url'];
          $default['options'] = ['attributes' => []];
        }
        break;
    }
    if (!empty($default)) {
      $default = [$default];
    }
    return $default;
  }

}
