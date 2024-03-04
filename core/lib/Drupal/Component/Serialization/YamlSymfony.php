<?php

namespace Drupal\Component\Serialization;

@trigger_error('The ' . __NAMESPACE__ . '\YamlSymfony is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml instead. See https://www.drupal.org/node/3415489', E_USER_DEPRECATED);

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Default serialization for YAML using the Symfony component.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 *   \Drupal\Component\Serialization\Yaml instead.
 *
 * @see https://www.drupal.org/node/3415489
 */
class YamlSymfony implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::encode() instead. See https://www.drupal.org/node/3415489', E_USER_DEPRECATED);
    try {
      // Set the indentation to 2 to match Drupal's coding standards.
      $yaml = new Dumper(2);
      return $yaml->dump($data, PHP_INT_MAX, 0, SymfonyYaml::DUMP_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489', E_USER_DEPRECATED);
    try {
      $yaml = new Parser();
      // Make sure we have a single trailing newline. A very simple config like
      // 'foo: bar' with no newline will fail to parse otherwise.
      return $yaml->parse($raw, SymfonyYaml::PARSE_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::PARSE_CUSTOM_TAGS);
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::getFileExtension() instead. See https://www.drupal.org/node/3415489', E_USER_DEPRECATED);
    return 'yml';
  }

}
