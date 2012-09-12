<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTest.
 */

namespace Drupal\entity_test;

use InvalidArgumentException;

use Drupal\Core\Entity\Entity;

/**
 * Defines the test entity class.
 */
class EntityTest extends Entity {

  /**
   * An array keyed by language code where the entity properties are stored.
   *
   * @var array
   */
  protected $properties;

  /**
   * An array of allowed language codes.
   *
   * @var array
   */
  protected static $langcodes;

  /**
   * Constructs a new entity object.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (!isset(self::$langcodes)) {
      // The allowed languages are simply all the available ones in the system.
      self::$langcodes = drupal_map_assoc(array_keys(language_list(LANGUAGE_ALL)));
    }

    // Initialize the original entity language with the provided value or fall
    // back to LANGUAGE_NOT_SPECIFIED if none was specified. We do not check
    // against allowed languages here, since throwing an exception would make an
    // entity created in a subsequently uninstalled language not instantiable.
    $this->langcode = !empty($values['langcode']) ? $values['langcode'] : LANGUAGE_NOT_SPECIFIED;

    // Set initial values ensuring that only real properties are stored.
    // @todo For now we have no way to mark a property as multlingual hence we
    // just assume that all of them are.
    unset($values['id'], $values['uuid'], $values['default_langcode']);
    $this->setProperties($values, $this->langcode);
  }

  /**
   * Sets the entity original langcode.
   *
   * @param $langcode
   */
  public function setLangcode($langcode) {
    // If the original language is changed the related properties must change
    // their language accordingly.
    $prev_langcode = $this->langcode;
    if (isset($this->properties[$prev_langcode])) {
      $this->properties[$langcode] = $this->properties[$prev_langcode];
      unset($this->properties[$prev_langcode]);
    }
    $this->langcode = $langcode;
  }

  /**
   * Overrides EntityInterface::get().
   */
  public function get($property_name, $langcode = NULL) {
    $langcode = !empty($langcode) ? $langcode : $this->langcode;
    $entity_info = $this->entityInfo();
    if ($entity_info['fieldable'] && field_info_instance($this->entityType, $property_name, $this->bundle())) {
      return parent::get($property_name, $langcode);
    }
    elseif (isset($this->properties[$langcode][$property_name])) {
      return $this->properties[$langcode][$property_name];
    }
    else {
      // @todo Remove this. All properties should be stored in the $properties
      // array once we have a Property API in place.
      return property_exists($this, $property_name) ? $this->{$property_name} : NULL;
    }
  }

  /**
   * Overrides EntityInterface::set().
   */
  public function set($property_name, $value, $langcode = NULL) {
    $langcode = !empty($langcode) ? $langcode : $this->langcode;
    if (!isset(self::$langcodes[$langcode])) {
      throw new InvalidArgumentException("Detected an invalid language '$langcode' while setting '$property_name' to '$value'.");
    }
    $entity_info = $this->entityInfo();
    if ($entity_info['fieldable'] && field_info_instance($this->entityType, $property_name, $this->bundle())) {
      parent::set($property_name, $value, $langcode);
    }
    else {
      $this->properties[$langcode][$property_name] = $value;
    }
  }

  /**
   * Overrides EntityInterface::translations().
   */
  public function translations() {
    $translations = !empty($this->properties) ? $this->properties : array();
    $languages = array_intersect_key(self::$langcodes, $translations);
    unset($languages[$this->langcode]);
    return $languages + parent::translations();
  }

  /**
   * Returns the property array for the given language.
   *
   * @param string $langcode
   *   (optional) The language code to be used to retrieve the properties.
   */
  public function getProperties($langcode = NULL) {
    $langcode = !empty($langcode) ? $langcode : $this->langcode;
    return isset($this->properties[$langcode]) ? $this->properties[$langcode] : array();
  }

  /**
   * Sets the property array for the given language.
   *
   * @param array $properties
   *   A keyed array of properties to be set with their 'langcode' as one of the
   *   keys. If no language is provided the entity language is used.
   * @param string $langcode
   *   (optional) The language code to be used to set the properties.
   */
  public function setProperties(array $properties, $langcode = NULL) {
    $langcode = !empty($langcode) ? $langcode : $this->langcode;
    $this->properties[$langcode] = $properties;
  }
}
