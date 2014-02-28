<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\ConfigEntityNormalizer.
 */

namespace Drupal\serialization\Normalizer;

/**
 * Normalizes/denormalizes Drupal config entity objects into an array structure.
 */
class ConfigEntityNormalizer extends EntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = array('Drupal\Core\Config\Entity\ConfigEntityInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    return $object->getExportProperties();
  }

}
