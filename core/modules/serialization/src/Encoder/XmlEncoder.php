<?php

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
  static protected $format = ['xml'];

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
      $this->baseEncoder->setSerializer($this->serializer);
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
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    return $this->getBaseEncoder()->encode($data, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    return $this->getBaseEncoder()->decode($data, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

}
