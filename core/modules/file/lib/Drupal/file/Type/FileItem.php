<?php

/**
 * @file
 * Contains \Drupal\file\Type\FileItem.
 */

namespace Drupal\file\Type;

use Drupal\Core\Entity\Field\FieldItemBase;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines the 'file_field' entity field item.
 */
class FileItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see FileItem::getPropertyDefinitions()
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
      static::$propertyDefinitions['display'] = array(
        'type' => 'boolean',
        'label' => t('Flag to control whether this file should be displayed when viewing content.'),
      );
      static::$propertyDefinitions['description'] = array(
        'type' => 'string',
        'label' => t('A description of the file.'),
      );
      static::$propertyDefinitions['entity'] = array(
        'type' => 'entity',
        'constraints' => array(
          'EntityType' => 'file',
        ),
        'label' => t('File'),
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
