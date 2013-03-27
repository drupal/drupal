<?php

/**
 * @file
 * Contains \Drupal\file\Type\FileItem.
 */

namespace Drupal\file\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

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
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the entity field, if no array
    // is given.
    if (!is_array($values)) {
      $values = array('entity' => $values);
    }

    if (isset($values['display'])) {
      $this->properties['display']->setValue($values['display']);
    }
    if (isset($values['description'])) {
      $this->properties['description']->setValue($values['description']);
    }

    // Entity is computed out of the ID, so we only need to update the ID. Only
    // set the entity field if no ID is given.
    if (isset($values['fid'])) {
      $this->properties['fid']->setValue($values['fid']);
    }
    elseif (isset($values['entity'])) {
      $this->properties['entity']->setValue($values['entity']);
    }
    else {
      $this->properties['entity']->setValue(NULL);
    }
    unset($values['entity'], $values['fid']);
    // @todo These properties are sometimes set due to being present in form
    //   values. Needs to be cleaned up somewhere.
    unset($values['display'], $values['description'], $values['upload']);
    if ($values) {
      throw new \InvalidArgumentException('Property ' . key($values) . ' is unknown.');
    }
  }

}
