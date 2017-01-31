<?php

/**
 * @file
 * Describes hooks provided by the Serialization module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the serialization type URI.
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
function hook_serialization_type_uri_alter(&$uri, $context = array()) {
  if ($context['mymodule'] == TRUE) {
    $base = \Drupal::config('serialization.settings')->get('link_domain');
    $uri = str_replace($base, 'http://mymodule.domain', $uri);
  }
}


/**
 * Alter the serialization relation URI.
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
function hook_serialization_relation_uri_alter(&$uri, $context = array()) {
  if ($context['mymodule'] == TRUE) {
    $base = \Drupal::config('serialization.settings')->get('link_domain');
    $uri = str_replace($base, 'http://mymodule.domain', $uri);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
