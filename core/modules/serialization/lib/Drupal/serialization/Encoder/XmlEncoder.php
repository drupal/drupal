<?php

/**
 * @file
 * Contains \Drupal\serialization\Encoder\XmlEncoder.
 */

namespace Drupal\serialization\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\SerializerAwareEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder as BaseXmlEncoder;

/**
 * Adds XML support for serializer.
 *
 * This acts as a wrapper class for Symfony's XmlEncoder so that it is not
 * implementing NormalizationAwareInterface, and can be normalized externally.
 */
class XmlEncoder extends SerializerAwareEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  static protected $format = array('xml');

  /**
   * An instance of the Symfony XmlEncoder to perform the actual encoding.
   *
   * @var \Symfony\Component\Serializer\Encoder\XmlEncoder
   */
  protected $baseEncoder;

  /**
   * Constucts the XmlEncoder object, creating a BaseXmlEncoder class also.
   */
  public function __construct() {
    $this->baseEncoder = new BaseXmlEncoder();
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\EncoderInterface::encode().
   */
  public function encode($data, $format, array $context = array()){
    $normalized = $this->serializer->normalize($data, $format, $context);
    return $this->baseEncoder->encode($normalized, $format, $context);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsEncoding().
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\EncoderInterface::decode().
   */
  public function decode($data, $format, array $context = array()){
    return $this->baseEncoder->decode($data, $format, $context);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsDecoding().
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }
}
