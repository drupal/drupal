<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\NormalizerBase.
 */

namespace Drupal\hal\Normalizer;

use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase implements DenormalizerInterface {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $formats = array('hal_json');

  /**
   * The entity resolver.
   *
   * @var \Drupal\serialization\EntityResolver\EntityResolverInterface
   */
  protected $entityResolver;

  /**
   * The hypermedia link manager.
   *
   * @var \Drupal\rest\LinkManager\LinkManager
   */
  protected $linkManager;

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization().
   */
  public function supportsNormalization($data, $format = NULL) {
    return in_array($format, $this->formats) && parent::supportsNormalization($data, $format);
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    if (in_array($format, $this->formats)) {
      $target = new \ReflectionClass($type);
      $supported = new \ReflectionClass($this->supportedInterfaceOrClass);
      if ($supported->isInterface()) {
        return $target->implementsInterface($this->supportedInterfaceOrClass);
      }
      else {
        return ($target->getName() == $this->supportedInterfaceOrClass || $target->isSubclassOf($this->supportedInterfaceOrClass));
      }
    }
  }

  /**
   * Sets the link manager.
   *
   * The link manager determines the hypermedia type and relation links which
   * correspond to different bundles and fields.
   *
   * @param \Drupal\rest\LinkManager\LinkManager $link_manager
   */
  public function setLinkManager($link_manager) {
    $this->linkManager = $link_manager;
  }

  /**
   * Sets the entity resolver.
   *
   * The entity resolver is used to
   *
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $entity_resolver
   */
  public function setEntityResolver(EntityResolverInterface $entity_resolver) {
    $this->entityResolver = $entity_resolver;
  }

}
