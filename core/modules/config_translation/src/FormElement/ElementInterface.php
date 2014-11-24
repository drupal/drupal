<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\ElementInterface.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\language\Config\LanguageConfigOverride;

/**
 * Provides an interface for configuration translation form elements.
 */
interface ElementInterface {

  /**
   * Creates a form element instance from a schema definition.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $schema
   *   The configuration schema.
   *
   * @return static
   */
  public static function create(TypedDataInterface $schema);

  /**
   * Builds a render array containg the source and translation form elements.
   *
   * @param \Drupal\Core\Language\LanguageInterface $source_language
   *   The source language of the configuration object.
   * @param \Drupal\Core\Language\LanguageInterface $translation_language
   *   The language to display the translation form for.
   * @param mixed $source_config
   *   The configuration value of the element in the source language.
   * @param mixed $translation_config
   *   The configuration value of the element in the language to translate to.
   * @param array $parents
   *   Parents array for the element in the form.
   * @param string|null $base_key
   *   (optional) Base key to be used for the elements in the form. NULL for
   *   top-level form elements.
   *
   * @return array
   *   A render array consisting of the source and translation elements for the
   *   source value.
   */
  public function getTranslationBuild(LanguageInterface $source_language, LanguageInterface $translation_language, $source_config, $translation_config, array $parents, $base_key = NULL);

  /**
   * Sets configuration based on a nested form value array.
   *
   * If the configuration values are the same as the source configuration, the
   * override should be removed from the translation configuration.
   *
   * @param \Drupal\Core\Config\Config $base_config
   *   Base configuration values, in the source language.
   * @param \Drupal\language\Config\LanguageConfigOverride $config_translation
   *   Translation configuration override data.
   * @param mixed $config_values
   *   The configuration value of the element taken from the form values.
   * @param string|null $base_key
   *   (optional) The base key that the schema and the configuration values
   *   belong to. This should be NULL for the top-level configuration object and
   *   be populated consecutively when recursing into the configuration
   *   structure.
   */
  public function setConfig(Config $base_config, LanguageConfigOverride $config_translation, $config_values, $base_key = NULL);

}
