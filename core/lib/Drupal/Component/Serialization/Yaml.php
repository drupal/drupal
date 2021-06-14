<?php

namespace Drupal\Component\Serialization;

/**
 * Provides a YAML serialization implementation.
 *
 * Proxy implementation that will choose the best library based on availability.
 */
class Yaml implements SerializationInterface {

  /**
   * The YAML implementation to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected static $serializer;

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    // Instead of using \Drupal\Component\Serialization\Yaml::getSerializer(),
    // always using Symfony for writing the data, to reduce the risk of having
    // differences if different environments (like production and development)
    // do not match in terms of what YAML implementation is available.
    return YamlSymfony::encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    $serializer = static::getSerializer();
    return $serializer::decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Determines which implementation to use for parsing YAML.
   */
  protected static function getSerializer() {

    if (!isset(static::$serializer)) {
      // Use the PECL YAML extension if it is available. It has better
      // performance for file reads and is YAML compliant.
      if (extension_loaded('yaml')) {
        static::$serializer = YamlPecl::class;
      }
      else {
        // Otherwise, fallback to the Symfony implementation.
        static::$serializer = YamlSymfony::class;
      }
    }
    return static::$serializer;
  }

}
