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
    $definitions['entity:node']['class'] = 'Drupal\mymodule\Plugin\rest\resource\NodeResource';
    // Serialized nodes should be expanded to my specific node class.
    $definitions['entity:node']['serialization_class'] = 'Drupal\mymodule\Entity\MyNode';
  }
  // We don't want Views to show up in the array of plugins at all.
  unset($definitions['entity:view']);
}

/**
 * Alter the REST type URI.
 *
 * Modules may wish to alter the type URI generated for a resource based on the
 * context of the serializer/normalizer operation.
 *
 * @param string $uri
 *   The URI to alter.
 * @param array $context
 *   The context from the serializer/normalizer operation.
 *
 * @see \Symfony\Component\Serializer\SerializerInterface::serialize()
 * @see \Symfony\Component\Serializer\SerializerInterface::deserialize()
 * @see \Symfony\Component\Serializer\NormalizerInterface::normalize()
 * @see \Symfony\Component\Serializer\DenormalizerInterface::denormalize()
 */
function hook_rest_type_uri_alter(&$uri, $context = array()) {
  if ($context['mymodule'] == TRUE) {
    $base = \Drupal::config('rest.settings')->get('link_domain');
    $uri = str_replace($base, 'http://mymodule.domain', $uri);
  }
}


/**
 * Alter the REST relation URI.
 *
 * Modules may wish to alter the relation URI generated for a resource based on
 * the context of the serializer/normalizer operation.
 *
 * @param string $uri
 *   The URI to alter.
 * @param array $context
 *   The context from the serializer/normalizer operation.
 *
 * @see \Symfony\Component\Serializer\SerializerInterface::serialize()
 * @see \Symfony\Component\Serializer\SerializerInterface::deserialize()
 * @see \Symfony\Component\Serializer\NormalizerInterface::normalize()
 * @see \Symfony\Component\Serializer\DenormalizerInterface::denormalize()
 */
function hook_rest_relation_uri_alter(&$uri, $context = array()) {
  if ($context['mymodule'] == TRUE) {
    $base = \Drupal::config('rest.settings')->get('link_domain');
    $uri = str_replace($base, 'http://mymodule.domain', $uri);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
