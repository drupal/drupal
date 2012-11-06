<?php

/**
 * @file
 * Definition of Drupal\jsonld\DrupalJsonldEntityWrapper.
 */

namespace Drupal\jsonld;

/**
 * Provide an interface for DrupalJsonldNormalizer to get required properties.
 */
class DrupalJsonldEntityWrapper extends JsonldEntityWrapper {

  /**
   * Get properties, excluding JSON-LD specific properties.
   *
   * Format Entity properties for consumption by other Drupal sites. In
   * Drupal's vendor specific JSON-LD, fields which correspond to primitives
   * have an intermediary data structure between the entity and the value.
   */
  public function getProperties() {
    // Properties to skip.
    $skip = array('id');

    // Create language map property structure.
    foreach ($this->entity->getTranslationLanguages() as $langcode => $language) {
      foreach ($this->entity->getTranslation($langcode) as $name => $field) {
        $definition = $this->entity->getPropertyDefinition($name);
        $langKey = empty($definition['translatable']) ? 'und' : $langcode;
        if (!$field->isEmpty()) {
          $properties[$name][$langKey] = $field->getValue();
        }
      }
    }

    // Only return properties which are not in the $skip array.
    return array_diff_key($properties, array_fill_keys($skip, ''));
  }

}
