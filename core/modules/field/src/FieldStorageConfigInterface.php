<?php

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides an interface defining a field storage entity.
 */
interface FieldStorageConfigInterface extends ConfigEntityInterface, FieldStorageDefinitionInterface {

  /**
   * Returns the field type.
   *
   * @return string
   *   The field type, i.e. the id of a field type plugin. For example 'text'.
   */
  public function getType();

  /**
   * Returns the name of the module providing the field type.
   *
   * @return string
   *   The name of the module that provides the field type.
   */
  public function getTypeProvider();

  /**
   * Returns the list of bundles where the field storage has fields.
   *
   * @return array
   *   An array of bundle names.
   */
  public function getBundles();

  /**
   * Checks if the field storage can be deleted.
   *
   * @return bool
   *   TRUE if the field storage can be deleted.
   */
  public function isDeletable();

  /**
   * Returns whether the field storage is locked or not.
   *
   * @return bool
   *   TRUE if the field storage is locked.
   */
  public function isLocked();

  /**
   * Sets the locked flag.
   *
   * @param bool $locked
   *   Sets value of locked flag.
   *
   * @return $this
   */
  public function setLocked($locked);

  /**
   * Sets the maximum number of items allowed for the field.
   *
   * @param int $cardinality
   *   The cardinality value.
   *
   * @return $this
   */
  public function setCardinality($cardinality);

  /**
   * Sets the value for a field setting by name.
   *
   * @param string $setting_name
   *   The name of the setting.
   * @param mixed $value
   *   The value of the setting.
   *
   * @return $this
   */
  public function setSetting($setting_name, $value);

  /**
   * Sets field storage settings.
   *
   * Note that the method does not unset existing settings not specified in the
   * incoming $settings array.
   *
   * For example:
   * @code
   *   // Given these are the default settings.
   *   $storage_definition->getSettings() === [
   *     'fruit' => 'apple',
   *     'season' => 'summer',
   *   ];
   *   // Change only the 'fruit' setting.
   *   $storage_definition->setSettings(['fruit' => 'banana']);
   *   // The 'season' setting persists unchanged.
   *   $storage_definition->getSettings() === [
   *     'fruit' => 'banana',
   *     'season' => 'summer',
   *   ];
   * @endcode
   *
   * For clarity, it is preferred to use setSetting() if not all available
   * settings are supplied.
   *
   * @param array $settings
   *   The array of storage settings.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Sets whether the field is translatable.
   *
   * @param bool $translatable
   *   Whether the field is translatable.
   *
   * @return $this
   */
  public function setTranslatable($translatable);

  /**
   * Returns the custom storage indexes for the field data storage.
   *
   * @return array
   *   An array of custom indexes.
   */
  public function getIndexes();

  /**
   * Sets the custom storage indexes for the field data storage..
   *
   * @param array $indexes
   *   The array of custom indexes.
   *
   * @return $this
   */
  public function setIndexes(array $indexes);

}
