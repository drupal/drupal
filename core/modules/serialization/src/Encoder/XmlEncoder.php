<?php

/**
 * @file
 * Contains \Drupal\serialization\Encoder\XmlEncoder.
 */

namespace Drupal\serialization\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder as BaseXmlEncoder;

/**
 * Adds XML support for serializer.
 *
 * This acts as a wrapper class for Symfony's XmlEncoder so that it is not
 * implementing NormalizationAwareInterface, and can be normalized externally.
 */
class XmlEncoder implements EncoderInterface, DecoderInterface {

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
   * Gets the base encoder instance.
   *
   * @return \Symfony\Component\Serializer\Encoder\XmlEncoder
   *   The base encoder.
   */
  public function getBaseEncoder() {
    if (!isset($this->baseEncoder)) {
      $this->baseEncoder = new BaseXmlEncoder();
    }

    return $this->baseEncoder;
  }

  /**
   * Sets the base encoder instance.
   *
   * @param \Symfony\Component\Serializer\Encoder\XmlEncoder $encoder
   */
  public function setBaseEncoder($encoder) {
    $this->baseEncoder = $encoder;
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\EncoderInterface::encode().
   */
  public function encode($data, $format, array $context = array()){
    return $this->getBaseEncoder()->encode($data, $format, $context);
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
    return $this->getBaseEncoder()->decode($data, $format, $context);
  }

  /**
   * Implements \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsDecoding().
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }
}
