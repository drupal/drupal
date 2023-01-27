<?php

namespace Drupal\Core\ParamConverter;

/**
 * Provides a trait to replace dynamic entity types in routes.
 */
trait DynamicEntityTypeParamConverterTrait {

  /**
   * Determines the entity type ID given a route definition and route defaults.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   Thrown when the dynamic entity type is not found in the route defaults.
   */
  protected function getEntityTypeFromDefaults($definition, $name, array $defaults) {
    $type_part = strstr($definition['type'], ':');
    if (!$type_part) {
      throw new ParamNotConvertedException(sprintf('The type definition "%s" is invalid. The expected format is "entity_revision:<entity_type_id>".', $definition['type']));
    }
    $entity_type_id = substr($type_part, 1);

    // If the entity type is dynamic, it will be pulled from the route defaults.
    if (str_starts_with($entity_type_id, '{')) {
      $entity_type_slug = substr($entity_type_id, 1, -1);
      if (!isset($defaults[$entity_type_slug])) {
        throw new ParamNotConvertedException(sprintf('The "%s" parameter was not converted because the "%s" parameter is missing.', $name, $entity_type_slug));
      }
      $entity_type_id = $defaults[$entity_type_slug];
    }
    return $entity_type_id;
  }

}
