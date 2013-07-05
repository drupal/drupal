<?php

/**
 * @file
 * Contains \Drupal\file\Type\FileItem.
 */

namespace Drupal\file\Type;

use Drupal\field\Plugin\Type\FieldType\ConfigEntityReferenceItemBase;

/**
 * Defines the 'file_field' entity field item.
 */
class FileItem extends ConfigEntityReferenceItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see FileItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $this->definition['settings']['target_type'] = 'file';
    // Definitions vary by entity type and bundle, so key them accordingly.
    $key = $this->definition['settings']['target_type'] . ':';
    $key .= isset($this->definition['settings']['target_bundle']) ? $this->definition['settings']['target_bundle'] : '';

    if (!isset(static::$propertyDefinitions[$key])) {
      static::$propertyDefinitions[$key] = parent::getPropertyDefinitions();

      static::$propertyDefinitions[$key]['display'] = array(
        'type' => 'boolean',
        'label' => t('Flag to control whether this file should be displayed when viewing content.'),
      );
      static::$propertyDefinitions[$key]['description'] = array(
        'type' => 'string',
        'label' => t('A description of the file.'),
      );
    }
    return static::$propertyDefinitions[$key];
  }

}
