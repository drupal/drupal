<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\process\d6\FieldInstanceWidgetSettings.
 */

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get the field instance widget settings.
 *
 * @MigrateProcessPlugin(
 *   id = "field_instance_widget_settings"
 * )
 */
class FieldInstanceWidgetSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Get the field instance default/mapped widget settings.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($widget_type, $widget_settings) = $value;
    return $this->getSettings($widget_type, $widget_settings);
  }

  /**
   * Merge the default D8 and specified D6 settings for a widget type.
   *
   * @param string $widget_type
   *   The widget type.
   * @param array $widget_settings
   *   The widget settings from D6 for this widget.
   *
   * @return array
   *   A valid array of settings.
   */
  public function getSettings($widget_type, $widget_settings) {
    $progress = isset($widget_settings['progress_indicator']) ? $widget_settings['progress_indicator'] : 'throbber';
    $size = isset($widget_settings['size']) ? $widget_settings['size'] : 60;
    $rows = isset($widget_settings['rows']) ? $widget_settings['rows'] : 5;

    $settings = array(
      'text_textfield' => array(
        'size' => $size,
        'placeholder' => '',
      ),
      'text_textarea' => array(
        'rows' => $rows,
        'placeholder' => '',
      ),
      'number' => array(
        'placeholder' => '',
      ),
      'email_textfield' => array(
        'placeholder' => '',
      ),
      'link' => array(
        'placeholder_url' => '',
        'placeholder_title' => '',
      ),
      'filefield_widget' => array(
        'progress_indicator' => $progress,
      ),
      'imagefield_widget' => array(
        'progress_indicator' => $progress,
        'preview_image_style' => 'thumbnail',
      ),
      'optionwidgets_onoff' => array(
        'display_label' => FALSE,
      ),
      'phone_textfield' => array(
        'placeholder' => '',
      ),
    );

    return isset($settings[$widget_type]) ? $settings[$widget_type] : array();
  }

}
