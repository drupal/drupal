<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;

/**
 * Provides a param converter for overrides-based section storage.
 */
class SectionStorageOverridesParamConverter extends EntityConverter implements SectionStorageParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_id = $this->getEntityIdFromDefaults($value, $defaults);
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    if (!$entity_id || !$entity_type_id) {
      return NULL;
    }

    $entity = parent::convert($entity_id, $definition, $name, $defaults);
    if ($entity instanceof FieldableEntityInterface && $entity->hasField('layout_builder__layout')) {
      return $entity->get('layout_builder__layout');
    }
  }

  /**
   * Determines the entity ID given a parameter value and route defaults.
   *
   * @param string $value
   *   The parameter value.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string|null
   *   The entity ID if it exists, NULL otherwise.
   */
  protected function getEntityIdFromDefaults($value, array $defaults) {
    $entity_id = NULL;
    // Layout Builder routes will have this parameter in the form of
    // 'entity_type_id:entity_id'.
    if (strpos($value, ':') !== FALSE) {
      list(, $entity_id) = explode(':', $value);
    }
    // Overridden routes have the entity ID available in the defaults.
    elseif (isset($defaults['entity_type_id']) && !empty($defaults[$defaults['entity_type_id']])) {
      $entity_id = $defaults[$defaults['entity_type_id']];
    }
    return $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeFromDefaults($definition, $name, array $defaults) {
    // Layout Builder routes will have this parameter in the form of
    // 'entity_type_id:entity_id'.
    if (isset($defaults[$name]) && strpos($defaults[$name], ':') !== FALSE) {
      list($entity_type_id) = explode(':', $defaults[$name], 2);
      return $entity_type_id;
    }
    // Overridden routes have the entity type ID available in the defaults.
    elseif (isset($defaults['entity_type_id'])) {
      return $defaults['entity_type_id'];
    }
  }

}
