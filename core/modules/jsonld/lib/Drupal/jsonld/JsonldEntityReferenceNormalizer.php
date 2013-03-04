<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityReferenceNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Entity\EntityNG;
use Drupal\jsonld\JsonldNormalizerBase;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use ReflectionClass;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts an EntityReferenceItem to a JSON-LD array structure.
 */
class JsonldEntityReferenceNormalizer extends JsonldNormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\Field\Type\EntityReferenceItem';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    // @todo If an $options parameter is added to the serialize signature, as
    // requested in https://github.com/symfony/symfony/pull/4938, then instead
    // of creating the array of properties, we could simply call normalize and
    // pass in the referenced entity with a flag that ensures it is rendered as
    // a node reference and not a node definition.
    $entity_wrapper = new JsonldEntityWrapper($object->entity, $format, $this->serializer, $this->siteSchemaManager);
    return array(
      '@id' => $entity_wrapper->getId(),
    );
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize()
   */
  public function denormalize($data, $class, $format = null, array $context = array()) {
    // @todo Support denormalization for Entity Reference.
    return array();
  }

  /**
   * Overrides \Drupal\jsonld\JsonldNormalizerBase::supportsDenormalization()
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    $reflection = new ReflectionClass($type);
    return in_array($format, static::$format) && ($reflection->getName() == $this->supportedInterfaceOrClass || $reflection->isSubclassOf($this->supportedInterfaceOrClass));
  }

}
