<?php

/**
 * @file
 * Contains \Drupal\serialization\Encoder\JsonEncoder.
 */

namespace Drupal\serialization\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;

/**
 * Adds 'ajax to the supported content types of the JSON encoder'
 */
class JsonEncoder extends BaseJsonEncoder implements EncoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  static protected $format = array('json', 'ajax');

  /**
   * Overrides Symfony\Component\Serializer\Encoder\JsonEncoder::supportEncoding().
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }
}
