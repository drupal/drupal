<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Provides the interface for a plugin manager of section storage types.
 */
interface SectionStorageManagerInterface extends DiscoveryInterface {

  /**
   * Loads a section storage with the provided contexts applied.
   *
   * @param string $type
   *   The section storage type.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   (optional) The contexts available for this storage to use.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage or NULL if its context requirements are not met.
   */
  public function load($type, array $contexts = []);

  /**
   * Finds the section storage to load based on available contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   The contexts which should be used to determine which storage to return.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   Refinable cacheability object, which will be populated based on the
   *   cacheability of each section storage candidate. After calling this method
   *   this parameter will reflect the cacheability information used to
   *   determine the correct section storage. This must be associated with any
   *   output that uses the result of this method.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage if one matched all contexts, or NULL otherwise.
   *
   * @see \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   */
  public function findByContext(array $contexts, RefinableCacheableDependencyInterface $cacheability);

  /**
   * Loads a section storage with no associated section list.
   *
   * @param string $type
   *   The type of the section storage being instantiated.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage.
   *
   * @internal
   *   Section storage relies on context to load section lists. Use ::load()
   *   when that context is available. This method is intended for use by
   *   collaborators of the plugins in build-time situations when section
   *   storage type must be consulted.
   */
  public function loadEmpty($type);

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
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0.
   *   \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::load()
   *   should be used instead. See https://www.drupal.org/node/3012353.
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
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0.
   *   \Drupal\layout_builder\SectionStorageInterface::deriveContextsFromRoute()
   *   and \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::load()
   *   should be used instead. See https://www.drupal.org/node/3012353.
   */
  public function loadFromRoute($type, $value, $definition, $name, array $defaults);

}
