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

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions = parent::getPropertyDefinitions();

      static::$propertyDefinitions['alt'] = array(
        'type' => 'string',
        'label' => t("Alternative image text, for the image's 'alt' attribute."),
      );
      static::$propertyDefinitions['title'] = array(
        'type' => 'string',
        'label' => t("Image title text, for the image's 'title' attribute."),
      );
      static::$propertyDefinitions['width'] = array(
        'type' => 'integer',
        'label' => t('The width of the image in pixels.'),
      );
      static::$propertyDefinitions['height'] = array(
        'type' => 'integer',
        'label' => t('The height of the image in pixels.'),
      );
    }
    return static::$propertyDefinitions;
  }

}
