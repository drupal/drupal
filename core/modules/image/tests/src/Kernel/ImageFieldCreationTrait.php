<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a helper method for creating Image fields.
 */
trait ImageFieldCreationTrait {

  /**
   * Create a new image field.
   *
   * @param string $field_name
   *   The name of the new field (all lowercase). The Field UI 'field_' prefix
   *   is not added to the field name.
   * @param string $entity_type
   *   The entity type that this field will be added to.
   *   For backwards-compatibility, a bundle is also accepted in this parameter.
   * @param string $bundle
   *   The bundle this field will be added to.
   * @param array $storage_settings
   *   (optional) A list of field storage settings that will be added to the
   *   defaults.
   * @param array $field_settings
   *   (optional) A list of instance settings that will be added to the instance
   *   defaults.
   * @param array $widget_settings
   *   (optional) Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   (optional) Formatter settings to be added to the formatter defaults.
   * @param string $description
   *   (optional) A description for the field. Defaults to ''.
   */
  protected function createImageField($field_name, $entity_type, $bundle = '', $storage_settings = [], $field_settings = [], $widget_settings = [], $formatter_settings = [], $description = '') {
    // Previously, nodes were the only entity type supported, For backwards
    // compatibility, the $entity_type parameter is used as the $bundle in
    // cases where only one machine name is passed.
    if (func_num_args() == 2) {
      @trigger_error('Calling ' . __METHOD__ . '() with only two arguments is deprecated in drupal:10.3.0 and three arguments will be required in drupal:11.0.0. See https://www.drupal.org/node/3441322', E_USER_DEPRECATED);

      $bundle = $entity_type;
      $entity_type = 'node';
    }
    elseif (is_array($bundle)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_type argument is deprecated in drupal:10.3.0 and this argument will be required in drupal:11.0.0. See https://www.drupal.org/node/3441322', E_USER_DEPRECATED);

      return $this->createImageField($field_name, 'node', $entity_type, $bundle, $storage_settings, $field_settings, $widget_settings, $formatter_settings, $description);
    }

    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $field_name,
      'label' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay($entity_type, $bundle)
      ->setComponent($field_name, [
        'type' => 'image_image',
        'settings' => $widget_settings,
      ])
      ->save();
    $display_repository->getViewDisplay($entity_type, $bundle)
      ->setComponent($field_name, [
        'type' => 'image',
        'settings' => $formatter_settings,
      ])
      ->save();

    return $field_config;
  }

}
