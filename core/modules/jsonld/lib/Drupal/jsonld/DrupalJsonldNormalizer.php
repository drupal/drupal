<?php

/**
 * @file
 * Definition of Drupal\jsonld\DrupalJsonldNormalizer.
 */

namespace Drupal\jsonld;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts the Drupal entity object structure to JSON-LD array structure.
 */
class DrupalJsonldNormalizer extends JsonldNormalizer implements NormalizerInterface {

  /**
   * The format that this Normalizer supports.
   *
   * @var string
   */
  static protected $format = 'drupal_jsonld';

  /**
   * The class to use for the entity wrapper object.
   *
   * @var string
   */
  protected $entityWrapperClass = 'Drupal\jsonld\DrupalJsonldEntityWrapper';

}
