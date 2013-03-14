<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\EntityNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityNG;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class EntityNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\EntityInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    // Create the array of normalized properties, starting with the URI.
    $normalized = array(
      '_links' => array(
        'self' => array(
          'href' => $this->getEntityUri($entity),
        ),
        'type' => array(
          'href' => $this->linkManager->getTypeUri($entity->entityType(), $entity->bundle()),
        ),
      ),
    );

    // If the properties to use were specified, only output those properties.
    // Otherwise, output all properties except internal ID.
    if (isset($context['included_fields'])) {
      foreach ($context['included_fields'] as $property_name) {
        $properties[] = $entity->get($property_name);
      }
    }
    else {
      $properties = $entity->getProperties();
    }
    foreach ($properties as $property) {
      // In some cases, Entity API will return NULL array items. Ensure this is
      // a real property and that it is not the internal id.
      if (!is_object($property) || $property->getName() == 'id') {
        continue;
      }
      $normalized_property = $this->serializer->normalize($property, $format, $context);
      $normalized = NestedArray::mergeDeep($normalized, $normalized_property);
    }

    return $normalized;
  }

  /**
   * Constructs the entity URI.
   *
   * @param $entity
   *   The entity.
   *
   * @return string
   *   The entity URI.
   */
  protected function getEntityUri($entity) {
    $uri_info = $entity->uri();
    return url($uri_info['path'], array('absolute' => TRUE));
  }

}
