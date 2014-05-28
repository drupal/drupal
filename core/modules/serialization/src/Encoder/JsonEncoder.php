<?php

/**
 * @file
 * Contains \Drupal\serialization\Encoder\JsonEncoder.
 */

namespace Drupal\serialization\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;

/**
 * Adds 'ajax to the supported content types of the JSON encoder'
 */
class JsonEncoder extends BaseJsonEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  protected static $format = array('json', 'ajax');

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

}
