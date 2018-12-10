<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Provides the interface for a plugin manager of section storage types.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface SectionStorageManagerInterface extends DiscoveryInterface {

  /**
   * Loads a section storage with no associated section list.
   *
   * @param string $id
   *   The ID of the section storage being instantiated.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage.
   */
  public function loadEmpty($id);

  /**
   * Loads a section storage populated with an existing section list.
   *
   * @param string $type
   *   The section storage type.
   * @param string $id
   *   The section list ID.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the ID is invalid.
   */
  public function loadFromStorageId($type, $id);

  /**
   * Loads a section storage populated with a section list derived from a route.
   *
   * @param string $type
   *   The section storage type.
   * @param string $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage if it could be loaded, or NULL otherwise.
   *
   * @see \Drupal\Core\ParamConverter\ParamConverterInterface::convert()
   */
  public function loadFromRoute($type, $value, $definition, $name, array $defaults);

}
