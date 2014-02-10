<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\EntityNormalizer.
 */

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes/denormalizes Drupal entity objects into an array structure.
 */
class EntityNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\EntityInterface');

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs an EntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
     * {@inheritdoc}
     */
  public function normalize($object, $format = NULL, array $context = array()) {
    $attributes = array();
    foreach ($object as $name => $field) {
      $attributes[$name] = $this->serializer->normalize($field, $format);
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (empty($context['entity_type'])) {
      throw new UnexpectedValueException('Entity type parameter must be included in context.');
    }

    $entity_type = $this->entityManager->getDefinition($context['entity_type']);

    // The bundle property behaves differently from other entity properties.
    // i.e. the nested structure with a 'value' key does not work.
    if ($entity_type->hasKey('bundle')) {
      $bundle_key = $entity_type->getKey('bundle');
      $type = $data[$bundle_key][0]['value'];
      $data[$bundle_key] = $type;
    }

    return $this->entityManager->getStorageController($context['entity_type'])->create($data);
  }

}
