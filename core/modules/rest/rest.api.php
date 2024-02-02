<?php

/**
 * @file
 * Describes hooks provided by the RESTful Web Services module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the resource plugin definitions.
 *
 * @param array $definitions
 *   The collection of resource definitions.
 */
function hook_rest_resource_alter(&$definitions) {
  if (isset($definitions['entity:node'])) {
    // We want to handle REST requests regarding nodes with our own plugin
    // class.
    $definitions['entity:node']['class'] = 'Drupal\my_module\Plugin\rest\resource\NodeResource';
    // Serialized nodes should be expanded to my specific node class.
    $definitions['entity:node']['serialization_class'] = 'Drupal\my_module\Entity\MyNode';
  }
  // We don't want Views to show up in the array of plugins at all.
  unset($definitions['entity:view']);
}

/**
 * @} End of "addtogroup hooks".
 */
