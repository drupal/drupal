<?php

/**
 * @file
 * Contains \Drupal\Component\Serialization\Yaml.
 */

namespace Drupal\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Symfony\Component\Yaml\Yaml as Symfony;

/**
 * Default serialization for YAML using the Symfony component.
 */
class Yaml implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    try {
      return Symfony::dump($data, PHP_INT_MAX, 2, TRUE);
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    try {
      return Symfony::parse($raw, TRUE);
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'yml';
  }

}
