<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\ContentTranslationLocalTasks.
 */

namespace Drupal\content_translation\Plugin;

use Drupal\Core\Menu\LocalTaskDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route parameter manipulation for content translation local tasks.
 */
class ContentTranslationLocalTasks extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(Request $request) {
    $parameters = parent::getRouteParameters($request);
    $entity_type = $this->pluginDefinition['entity_type'];
    if ($raw_variables = $request->attributes->get('_raw_variables')) {
      // When the entity type is in the path, populate 'entity' for any dynamic
      // local tasks.
      if ($raw_variables->has($entity_type)) {
        $entity = $raw_variables->get($entity_type);
        $parameters['entity'] = $entity;
      }
      // When 'entity' is in the path, populate the parameters with the value
      // for the actual entity type.
      elseif ($raw_variables->has('entity')) {
        $entity = $raw_variables->get('entity');
        $parameters[$entity_type] = $entity;
      }
    }
    return $parameters;
  }

}
