<?php

namespace Drupal\Core\TypedData;

/**
 * Interface for complex data definitions.
 *
 * @see \Drupal\Core\TypedData\ComplexDataInterface
 *
 * @ingroup typed_data
 */
interface ComplexDataDefinitionInterface extends DataDefinitionInterface {

  /**
   * Gets the definition of a contained property.
   *
   * @param string $name
   *   The name of property.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|null
   *   The definition of the property or NULL if the property does not exist.
   */
  public function getPropertyDefinition($name);

  /**
   * Gets an array of property definitions of contained properties.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   */
  public function getPropertyDefinitions();

  /**
   * Returns the name of the main property, if any.
   *
   * Some field items consist mainly of one main property, e.g. the value of a
   * text field or the @code target_id @endcode of an entity reference. If the
   * field item has no main property, the method returns NULL.
   *
   * @return string|null
   *   The name of the value property, or NULL if there is none.
   */
  public function getMainPropertyName();

}
