<?php

/**
 * @image
 * Contains \Drupal\image\Type\ImageItem.
 */

namespace Drupal\image\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'image_field' entity field item.
 */
class ImageItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see ImageItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['fid'] = array(
        'type' => 'integer',
        'label' => t('Referenced file id.'),
      );
      static::$propertyDefinitions['alt'] = array(
        'type' => 'boolean',
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
      static::$propertyDefinitions['entity'] = array(
        'type' => 'entity',
        'constraints' => array(
          'EntityType' => 'file',
        ),
        'label' => t('Image'),
        'description' => t('The referenced file'),
        // The entity object is computed out of the fid.
        'computed' => TRUE,
        'read-only' => FALSE,
        'settings' => array('id source' => 'fid'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the entity property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = array('entity' => $values);
    }
    // Make sure that the 'entity' property gets set as 'fid'.
    if (isset($values['fid']) && !isset($values['entity'])) {
      $values['entity'] = $values['fid'];
    }
    parent::setValue($values, $notify);
  }
}
