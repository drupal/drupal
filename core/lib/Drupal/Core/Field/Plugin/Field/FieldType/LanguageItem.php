<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;

/**
 * Defines the 'language' entity field item.
 *
 * @FieldType(
 *   id = "language",
 *   label = @Translation("Language"),
 *   description = @Translation("An entity field referencing a language."),
 *   default_widget = "language_select",
 *   default_formatter = "language",
 *   no_ui = TRUE,
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Length" = {"max" = 12},
 *         "AllowedValues" = {"callback" = "\Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem::getAllowedLanguageCodes" }
 *       }
 *     }
 *   }
 * )
 *
 * @todo Define the AllowedValues constraint via an options provider once
 *   https://www.drupal.org/node/2329937 is completed.
 */
class LanguageItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Language code'))
      ->setSetting('is_ascii', TRUE)
      ->setRequired(TRUE);

    $properties['language'] = DataReferenceDefinition::create('language')
      ->setLabel(t('Language object'))
      ->setDescription(t('The referenced language'))
      // The language object is retrieved via the language code.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * Defines allowed language codes for the field's AllowedValues constraint.
   *
   * @return string[]
   *   The allowed values.
   */
  public static function getAllowedLanguageCodes() {
    return array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 12,
          'is_ascii' => TRUE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the language property, if no array
    // is given as this handles language codes and objects.
    if (isset($values) && !is_array($values)) {
      $this->set('language', $values, $notify);
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
    // Default to the site's default language. When language module is enabled,
    // this behavior is configurable, see language_field_info_alter().
    $this->setValue(array('value' => \Drupal::languageManager()->getDefaultLanguage()->getId()), $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the value and the language property stay in sync.
    if ($property_name == 'value') {
      $this->writePropertyValue('language', $this->value);
    }
    elseif ($property_name == 'language') {
      $this->writePropertyValue('value', $this->get('language')->getTargetIdentifier());
    }
    parent::onChange($property_name, $notify);
  }

}
