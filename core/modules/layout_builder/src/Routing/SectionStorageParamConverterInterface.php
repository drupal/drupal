<?php

namespace Drupal\layout_builder\Routing;

/**
 * Defines the interface of a param converter for section storage.
 *
 * A service implementing this interface must have a service ID prefixed with
 * 'layout_builder.section_storage_param_converter.', followed by the section
 * storage type.
 *
 * @see \Drupal\Core\ParamConverter\ParamConverterInterface
 * @see \Drupal\layout_builder\SectionStorageInterface::getStorageType()
 */
interface SectionStorageParamConverterInterface {

  /**
   * Converts path variables to their corresponding objects.
   *
   * @param mixed $value
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
   */
  public function convert($value, $definition, $name, array $defaults);

}
