<?php

/**
 * @file
 * Describes hooks provided by the HAL module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the HAL type URI.
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
function hook_hal_type_uri_alter(&$uri, $context = []) {
  if ($context['mymodule'] == TRUE) {
    $base = \Drupal::config('hal.settings')->get('link_domain');
    $uri = str_replace($base, 'http://mymodule.domain', $uri);
  }
}

/**
 * Alter the HAL relation URI.
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
function hook_hal_relation_uri_alter(&$uri, $context = []) {
  if ($context['mymodule'] == TRUE) {
    $base = \Drupal::config('hal.settings')->get('link_domain');
    $uri = str_replace($base, 'http://mymodule.domain', $uri);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
