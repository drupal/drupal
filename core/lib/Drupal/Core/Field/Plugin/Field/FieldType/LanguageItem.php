<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

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
 *         "Length" = {"max" = 12}
 *       }
 *     }
 *   }
 * )
 */
class LanguageItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Language code'))
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
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 12,
        ],
      ],
    ];
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
    $this->setValue(['value' => \Drupal::languageManager()->getDefaultLanguage()->getId()], $notify);
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

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Defer to the callback in the item definition as it can be overridden.
    $constraint = $field_definition->getItemDefinition()->getConstraint('ComplexData');
    if (isset($constraint['value']['AllowedValues']['callback'])) {
      $languages = call_user_func($constraint['value']['AllowedValues']['callback']);
    }
    else {
      $languages = array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));
    }
    $values['value'] = $languages[array_rand($languages)];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    return array_map(function (LanguageInterface $language) {
      return $language->getName();
    }, $languages);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return $this->getPossibleValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleValues($account);
  }

}
