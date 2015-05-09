<?php

/**
 * @file
 * Contains \Drupal\Component\Serialization\SerializationInterface.
 */

namespace Drupal\Component\Serialization;

/**
 * Defines an interface for serialization formats.
 */
interface SerializationInterface {

  /**
   * Encodes data into the serialization format.
   *
   * @param mixed $data
   *   The data to encode.
   *
   * @return string
   *   The encoded data.
   */
  public static function encode($data);

  /**
   * Decodes data from the serialization format.
   *
   * @param string $raw
   *   The raw data string to decode.
   *
   * @return mixed
   *   The decoded data.
   */
  public static function decode($raw);

  /**
   * Gets the file extension for this serialization format.
   *
   * @return string
   *   The file extension, without leading dot.
   */
  public static function getFileExtension();

}
