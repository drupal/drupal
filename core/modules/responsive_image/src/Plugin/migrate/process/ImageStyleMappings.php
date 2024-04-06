<?php

namespace Drupal\responsive_image\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms image style mappings.
 */
#[MigrateProcess('image_style_mappings')]
class ImageStyleMappings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array');
    }

    [$mappings, $breakpoint_group] = $value;

    $new_value = [];
    foreach ($mappings as $mapping_id => $mapping) {
      // The id is in the key with the form
      // "breakpoints.theme.my_theme_id.image_style_machine_name". We want the
      // identifier after the last period.
      preg_match('/\.([a-z0-9_]+)$/', $mapping_id, $matches);
      foreach ($mapping as $multiplier => $multiplier_settings) {
        if ($multiplier_settings['mapping_type'] == '_none') {
          continue;
        }
        $image_style = [
          'breakpoint_id' => $breakpoint_group . '.' . $matches[1],
          'multiplier' => $multiplier,
          'image_mapping_type' => $multiplier_settings['mapping_type'],
          'image_mapping' => $this->getMultiplierSettings($multiplier_settings),
        ];
        $new_value[] = $image_style;
      }
    }
    return $new_value;
  }

  /**
   * Extracts multiplier settings based on its type.
   *
   * @param array[] $multiplier_settings
   *   The multiplier settings.
   *
   * @return array
   *   The multiplier settings.
   */
  protected function getMultiplierSettings(array $multiplier_settings) {
    $settings = [];

    if ($multiplier_settings['mapping_type'] == 'image_style') {
      $settings = $multiplier_settings['image_style'];
    }
    elseif ($multiplier_settings['mapping_type'] == 'sizes') {
      $settings = [
        'sizes' => $multiplier_settings['sizes'],
        'sizes_image_styles' => array_values($multiplier_settings['sizes_image_styles']),
      ];
    }

    return $settings;
  }

}
