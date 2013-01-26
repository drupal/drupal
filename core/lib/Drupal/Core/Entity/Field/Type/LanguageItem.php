<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\LanguageItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;
use InvalidArgumentException;

/**
 * Defines the 'language_field' entity field item.
 */
class LanguageItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see LanguageItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('Language code'),
      );
      static::$propertyDefinitions['language'] = array(
        'type' => 'language',
        'label' => t('Language object'),
        // The language object is retrieved via the language code.
        'computed' => TRUE,
        'read-only' => FALSE,
        'settings' => array('langcode source' => 'value'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides FieldItemBase::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the object property, if no array
    // is given. That way we support setting the field by language code or
    // object.
    if (!is_array($values)) {
      $values = array('language' => $values);
    }

    // Language is computed out of the langcode, so we only need to update the
    // langcode. Only set the language property if no langcode is given.
    if (!empty($values['value'])) {
      $this->properties['value']->setValue($values['value']);
    }
    elseif (isset($values['language'])) {
      $this->properties['language']->setValue($values['language']);
    }
    else {
      $this->properties['language']->setValue(NULL);
    }
    unset($values['language'], $values['value']);
    if ($values) {
      throw new InvalidArgumentException('Property ' . key($values) . ' is unknown.');
    }
  }
}
