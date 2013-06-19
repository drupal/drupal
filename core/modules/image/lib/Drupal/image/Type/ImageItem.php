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
    // Treat the values as value of the entity property, if no array is
    // given as this handles entity IDs and objects.
    if (isset($values) && !is_array($values)) {
      // Directly update the property instead of invoking the parent, so that
      // the entity property can take care of updating the ID property.
      $this->properties['entity']->setValue($values, $notify);
    }
    else {
      // Make sure that the 'entity' property gets set as 'target_id'.
      if (isset($values['fid']) && !isset($values['entity'])) {
        $values['entity'] = $values['fid'];
      }
      parent::setValue($values, $notify);
    }
  }
}
