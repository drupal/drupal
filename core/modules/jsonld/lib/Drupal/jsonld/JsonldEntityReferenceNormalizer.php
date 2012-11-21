<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityReferenceNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\Field\Type\EntityReferenceItem;
use Drupal\jsonld\JsonldNormalizerBase;
use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Converts an EntityReferenceItem to a JSON-LD array structure.
 */
class JsonldEntityReferenceNormalizer extends JsonldNormalizerBase {

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($object, $format = NULL) {
    // @todo If an $options parameter is added to the serialize signature, as
    // requested in https://github.com/symfony/symfony/pull/4938, then instead
    // of creating the array of properties, we could simply call normalize and
    // pass in the referenced entity with a flag that ensures it is rendered as
    // a node reference and not a node definition.
    $entityWrapper = new JsonldEntityWrapper($object->entity, $format, $this->serializer);
    return array(
      '@id' => $entityWrapper->getId(),
    );
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization()
   */
  public function supportsNormalization($data, $format = NULL) {
    return parent::supportsNormalization($data, $format) && ($data instanceof EntityReferenceItem);
  }

}
