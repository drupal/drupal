<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\SynchronizableInterface;

/**
 * Defines a common interface for configuration entities.
 *
 * @ingroup config_api
 * @ingroup entity_api
 */
interface ConfigEntityInterface extends EntityInterface, ThirdPartySettingsInterface, SynchronizableInterface {

  /**
   * Enables the configuration entity.
   *
   * @return $this
   */
  public function enable();

  /**
   * Disables the configuration entity.
   *
   * @return $this
   */
  public function disable();

  /**
   * Sets the status of the configuration entity.
   *
   * @param bool $status
   *   The status of the configuration entity.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Returns whether the configuration entity is enabled.
   *
   * Status implementations for configuration entities should follow these
   * general rules:
   *   - Status does not affect the loading of entities. I.e. Disabling
   *     configuration entities should only have UI/access implications.
   *   - It should only take effect when a 'status' key is explicitly declared
   *     in the entity_keys info of a configuration entity's annotation data.
   *   - Each entity implementation (entity/controller) is responsible for
   *     checking and managing the status.
   *
   * @return bool
   *   Whether the entity is enabled or not.
   */
  public function status();

  /**
   * Returns whether this entity is being changed during the uninstall process.
   *
   * If you are writing code that responds to a change in this entity (insert,
   * update, delete, presave, etc.), and your code would result in a
   * configuration change (whether related to this configuration entity, another
   * configuration entity, or non-entity configuration) or your code would
   * result in a change to this entity itself, you need to check and see if this
   * entity change is part of an uninstall process, and skip executing your code
   * if that is the case.
   *
   * For example, \Drupal\language\Entity\ConfigurableLanguage::preDelete()
   * prevents the API from deleting the default language. However during an
   * uninstall of the language module it is expected that the default language
   * should be deleted.
   *
   * @return bool
   *   TRUE if the configuration entity is being changed during the uninstall
   *   process, FALSE otherwise.
   */
  public function isUninstalling();

  /**
   * Returns the value of a property.
   *
   * @param string $property_name
   *   The name of the property that should be returned.
   *
   * @return mixed
   *   The property if it exists, or NULL otherwise.
   */
  public function get($property_name);

  /**
   * Sets the value of a property.
   *
   * @param string $property_name
   *   The name of the property that should be set.
   * @param mixed $value
   *   The value the property should be set to.
   *
   * @return $this
   */
  public function set($property_name, $value);

  /**
   * Calculates dependencies and stores them in the dependency property.
   *
   * @return $this
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  public function calculateDependencies();

  /**
   * Informs the entity that entities it depends on will be deleted.
   *
   * This method allows configuration entities to remove dependencies instead
   * of being deleted themselves. Configuration entities can use this method to
   * avoid being unnecessarily deleted during an extension uninstallation.
   * For example, entity displays remove references to widgets and formatters if
   * the plugin that supplies them depends on a module that is being
   * uninstalled.
   *
   * If this method returns TRUE then the entity needs to be re-saved by the
   * caller for the changes to take effect. Implementations should not save the
   * entity.
   *
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return bool
   *   TRUE if the entity has been changed as a result, FALSE if not.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   * @see \Drupal\Core\Config\ConfigEntityBase::preDelete()
   * @see \Drupal\Core\Config\ConfigManager::uninstall()
   * @see \Drupal\Core\Entity\EntityDisplayBase::onDependencyRemoval()
   */
  public function onDependencyRemoval(array $dependencies);

  /**
   * Gets the configuration dependencies.
   *
   * @return array
   *   An array of dependencies, keyed by $type.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  public function getDependencies();

  /**
   * Checks whether this entity is installable.
   *
   * For example, a default view might not be installable if the base table
   * doesn't exist.
   *
   * @return bool
   *   TRUE if the entity is installable, FALSE otherwise.
   */
  public function isInstallable();

  /**
   * Sets that the data should be trusted.
   *
   * If the data is trusted then dependencies will not be calculated on save and
   * schema will not be used to cast the values. Generally this is only used
   * during module and theme installation. Once the config entity has been saved
   * the data will no longer be marked as trusted. This is an optimization for
   * creation of configuration during installation.
   *
   * @return $this
   *
   * @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
   */
  public function trustData();

  /**
   * Gets whether on not the data is trusted.
   *
   * @return bool
   *   TRUE if the configuration data is trusted, FALSE if not.
   */
  public function hasTrustedData();

}
