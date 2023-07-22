<?php

namespace Drupal\config_translation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for configuration entity mappers.
 */
interface ConfigEntityMapperInterface extends ConfigMapperInterface {

  /**
   * Gets the entity instance for this mapper.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The configuration entity.
   */
  public function getEntity();

  /**
   * Sets the entity instance for this mapper.
   *
   * This method can only be invoked when the concrete entity is known, that is
   * in a request for an entity translation path. After this method is called,
   * the mapper is fully populated with the proper display title and
   * configuration names to use to check permissions or display a translation
   * screen.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The configuration entity to set.
   *
   * @return bool
   *   TRUE, if the entity was set successfully; FALSE otherwise.
   */
  public function setEntity(ConfigEntityInterface $entity);

  /**
   * Set entity type for this mapper.
   *
   * This should be set in initialization. A mapper that knows its type but
   * not yet its names is still useful for router item and tab generation. The
   * concrete entity only turns out later with actual controller invocations,
   * when the setEntity() method is invoked before the rest of the methods are
   * used.
   *
   * @param string $entity_type_id
   *   The entity type ID to set.
   *
   * @return bool
   *   TRUE if the entity type ID was set correctly; FALSE otherwise.
   */
  public function setType(string $entity_type_id): bool;

  /**
   * Gets the entity type ID from this mapper.
   *
   * @return string
   *   The entity type ID.
   */
  public function getType(): string;

}
