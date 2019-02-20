<?php

/**
 * @file
 * Post update functions for Rest.
 */

use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Create REST resource configuration entities.
 *
 * @see rest_update_8201()
 * @see https://www.drupal.org/node/2308745
 */
function rest_post_update_create_rest_resource_config_entities() {
  $resources = \Drupal::state()->get('rest_update_8201_resources', []);
  foreach ($resources as $key => $resource) {
    $resource = RestResourceConfig::create([
      'id' => str_replace(':', '.', $key),
      'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
      'configuration' => $resource,
    ]);
    $resource->save();
  }
}

/**
 * Simplify method-granularity REST resource config to resource-granularity.
 *
 * @see https://www.drupal.org/node/2721595
 */
function rest_post_update_resource_granularity() {
  /** @var \Drupal\rest\RestResourceConfigInterface[] $resource_config_entities */
  $resource_config_entities = RestResourceConfig::loadMultiple();

  foreach ($resource_config_entities as $resource_config_entity) {
    if ($resource_config_entity->get('granularity') === RestResourceConfigInterface::METHOD_GRANULARITY) {
      $configuration = $resource_config_entity->get('configuration');

      $format_and_auth_configuration = [];
      foreach (array_keys($configuration) as $method) {
        $format_and_auth_configuration['format'][$method] = implode(',', $configuration[$method]['supported_formats']);
        $format_and_auth_configuration['auth'][$method] = implode(',', $configuration[$method]['supported_auth']);
      }

      // If each method has the same formats and the same authentication
      // providers configured, convert it to 'granularity: resource', which has
      // a simpler/less verbose configuration.
      if (count(array_unique($format_and_auth_configuration['format'])) === 1 && count(array_unique($format_and_auth_configuration['auth'])) === 1) {
        $first_method = array_keys($configuration)[0];
        $resource_config_entity->set('configuration', [
          'methods' => array_keys($configuration),
          'formats' => $configuration[$first_method]['supported_formats'],
          'authentication' => $configuration[$first_method]['supported_auth'],
        ]);
        $resource_config_entity->set('granularity', RestResourceConfigInterface::RESOURCE_GRANULARITY);
        $resource_config_entity->save();
      }
    }
  }
}

/**
 * Clear caches due to changes in route definitions.
 */
function rest_post_update_161923() {
  // Empty post-update hook.
}
