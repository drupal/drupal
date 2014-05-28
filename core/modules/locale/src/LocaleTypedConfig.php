<?php

/**
 * @file
 * Contains \Drupal\locale\LocaleTypedConfig.
 */

namespace Drupal\locale;

use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Config\Schema\ArrayElement;

/**
 * Defines the locale configuration wrapper object.
 */
class LocaleTypedConfig extends Element {

  /**
   * The typed configuration data.
   *
   * @var \Drupal\Core\Config\Schema\Element
   */
  protected $typedConfig;

  /**
   * The language code for which this is a translation.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The locale configuration manager object.
   *
   * @var \Drupal\locale\LocaleConfigManager
   */
  protected $localeConfig;

  /**
   * Constructs a configuration wrapper object.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $name
   *   The configuration object name.
   * @param string $langcode
   *   Language code for the source configuration data.
   * @param \Drupal\locale\LocaleConfigManager $localeConfig;
   *   The locale configuration manager object.
   */
  public function __construct($definition, $name, $langcode, LocaleConfigManager $localeConfig) {
    parent::__construct($definition, $name);
    $this->langcode = $langcode;
    $this->localeConfig = $localeConfig;
  }

  /**
   * Gets wrapped typed config object.
   */
  public function getTypedConfig() {
    return $this->localeConfig->create($this->definition, $this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslation($langcode) {
    $options = array(
      'source' => $this->langcode,
      'target' => $langcode,
    );
    $data = $this->getElementTranslation($this->getTypedConfig(), $options);
    return $this->localeConfig->create($this->definition, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return language_load($this->langcode);
  }

  /**
   * Checks whether we can translate these languages.
   *
   * @param string $from_langcode
   *   Source language code.
   * @param string $to_langcode
   *   Destination language code.
   *
   * @return bool
   *   TRUE if this translator supports translations for these languages.
   */
  protected function canTranslate($from_langcode, $to_langcode) {
    if ($from_langcode == 'en') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets translated configuration data for a typed configuration element.
   *
   * @param mixed $element
   *   Typed configuration element, either \Drupal\Core\Config\Schema\Element or
   *   \Drupal\Core\Config\Schema\ArrayElement.
   * @param array $options
   *   Array with translation options that must contain the keys defined in
   *   \Drupal\locale\LocaleTypedConfig::translateElement()
   *
   * @return array
   *   Configuration data translated to the requested language if available,
   *   an empty array otherwise.
   */
  protected function getElementTranslation($element, array $options) {
    $translation = array();
    if ($element instanceof ArrayElement) {
      $translation = $this->getArrayTranslation($element, $options);
    }
    elseif ($this->translateElement($element, $options)) {
      $translation = $element->getValue();
    }
    return $translation;
  }

  /**
   * Gets translated configuration data for an element of type ArrayElement.
   *
   * @param \Drupal\Core\Config\Schema\ArrayElement $element
   *   Typed configuration array element.
   * @param array $options
   *   Array with translation options that must contain the keys defined in
   *   \Drupal\locale\LocaleTypedConfig::translateElement()
   *
   * @return array
   *   Configuration data translated to the requested language.
   */
  protected function getArrayTranslation(ArrayElement $element, array $options) {
    $translation = array();
    foreach ($element as $key => $property) {
      $value = $this->getElementTranslation($property, $options);
      if (!empty($value)) {
        $translation[$key] = $value;
      }
    }
    return $translation;
  }

  /**
   * Translates element's value if it fits our translation criteria.
   *
   * For an element to be translatable by locale module it needs to be of base
   * type 'string' and have 'translatable = TRUE' in the element's definition.
   * Translatable elements may use these additional keys in their data
   * definition:
   * - 'translatable', FALSE to opt out of translation.
   * - 'locale context', to define the string context.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   Configuration element.
   * @param array $options
   *   Array with translation options that must contain the following keys:
   *   - 'source', Source language code.
   *   - 'target', Target language code.
   *
   * @return bool
   *   Whether the element fits the translation criteria.
   */
  protected function translateElement(\Drupal\Core\TypedData\TypedDataInterface $element, array $options) {
    if ($this->canTranslate($options['source'], $options['target'])) {
      $definition = $element->getDataDefinition();
      $value = $element->getValue();
      if ($value && !empty($definition['translatable'])) {
        $context = isset($definition['locale context']) ? $definition['locale context'] : '';
        if ($translation = $this->localeConfig->translateString($this->name, $options['target'], $value, $context)) {
          $element->setValue($translation);
          return TRUE;
        }
      }
    }
    // The element does not have a translation.
    return FALSE;
  }

}
