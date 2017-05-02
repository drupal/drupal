<?php

namespace Drupal\hal\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $format = ['hal_json'];

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL) {
    if (isset($this->formats)) {
      @trigger_error('::formats is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Use ::$format instead. See https://www.drupal.org/node/2868275', E_USER_DEPRECATED);

      $this->format = $this->formats;
    }

    return parent::checkFormat($format);
  }

}
