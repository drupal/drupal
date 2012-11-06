<?php

/**
 * @file
 * Definition of Drupal\jsonld\DrupalJsonldEncoder.
 */

namespace Drupal\jsonld;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Encodes JSON-LD data.
 *
 * Simply respond to JSON-LD requests using the JSON encoder.
 */
class DrupalJsonldEncoder extends JsonldEncoder implements EncoderInterface {

  /**
   * The format that this Encoder supports.
   *
   * @var string
   */
  static protected $format = 'drupal_jsonld';

}
