<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\LanguageItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;
use Drupal\Core\Language\Language;

/**
 * Defines the 'language_field' entity field item.
 *
 * @DataType(
 *   id = "language_field",
 *   label = @Translation("Language field item"),
 *   description = @Translation("An entity field referencing a language."),
 *   list_class = "\Drupal\Core\Entity\Field\FieldItemList",
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {"Length" = {"max" = 12}}
 *     }
 *   }
 * )
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
        'type' => 'language_reference',
        'label' => t('Language object'),
        'description' => t('The referenced language'),
        // The language object is retrieved via the language code.
        'computed' => TRUE,
        'read-only' => FALSE,
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the language property, if no array
    // is given as this handles language codes and objects.
    if (isset($values) && !is_array($values)) {
      // Directly update the property instead of invoking the parent, so that
      // the language property can take care of updating the language code
      // property.
      $this->properties['language']->setValue($values, $notify);
      // If notify was FALSE, ensure the value property gets synched.
      if (!$notify) {
        $this->set('value', $this->properties['language']->getTargetIdentifier(), FALSE);
      }
    }
    else {
      // Make sure that the 'language' property gets set as 'value'.
      if (isset($values['value']) && !isset($values['language'])) {
        $values['language'] = $values['value'];
      }
      parent::setValue($values, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to LANGCODE_NOT_SPECIFIED.
    $this->setValue(array('value' => Language::LANGCODE_NOT_SPECIFIED), $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name) {
    // Make sure that the value and the language property stay in sync.
    if ($property_name == 'value') {
      $this->properties['language']->setValue($this->value, FALSE);
    }
    elseif ($property_name == 'language') {
      $this->set('value', $this->properties['language']->getTargetIdentifier(), FALSE);
    }
    parent::onChange($property_name);
  }
}
