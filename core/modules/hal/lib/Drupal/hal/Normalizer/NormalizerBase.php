<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\NormalizerBase.
 */

namespace Drupal\hal\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $formats = array('hal_json');

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

}
