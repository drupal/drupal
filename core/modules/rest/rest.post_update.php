<?php

/**
 * @file
 * Post update functions for Rest.
 */

use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * @addtogroup updates-8.1.x-to-8.2.x
 * @{
 */

/**
 * Create REST resource configuration entities.
 *
 * @todo https://www.drupal.org/node/2721595 Automatically upgrade those REST
 *   resource config entities that have the same formats/auth mechanisms for all
 *   methods to "granular: resource".
 *
 * @see rest_update_8201()
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
 * @} End of "addtogroup updates-8.1.x-to-8.2.x".
 */
