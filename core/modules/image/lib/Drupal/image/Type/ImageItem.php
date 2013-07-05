<?php

/**
 * @image
 * Contains \Drupal\image\Type\ImageItem.
 */

namespace Drupal\image\Type;

use Drupal\field\Plugin\Type\FieldType\ConfigEntityReferenceItemBase;

/**
 * Defines the 'image_field' entity field item.
 */
class ImageItem extends ConfigEntityReferenceItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see ImageItem::getPropertyDefinitions()
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

      static::$propertyDefinitions[$key]['alt'] = array(
        'type' => 'string',
        'label' => t("Alternative image text, for the image's 'alt' attribute."),
      );
      static::$propertyDefinitions[$key]['title'] = array(
        'type' => 'string',
        'label' => t("Image title text, for the image's 'title' attribute."),
      );
      static::$propertyDefinitions[$key]['width'] = array(
        'type' => 'integer',
        'label' => t('The width of the image in pixels.'),
      );
      static::$propertyDefinitions[$key]['height'] = array(
        'type' => 'integer',
        'label' => t('The height of the image in pixels.'),
      );
    }
    return static::$propertyDefinitions[$key];
  }

}
