<?php

namespace Drupal\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;

/**
 * Provides default serialization for YAML using the PECL extension.
 */
class YamlPecl implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    static $init;
    if (!isset($init)) {
      ini_set('yaml.output_indent', 2);
      // Do not break lines at 80 characters.
      ini_set('yaml.output_width', -1);
      $init = TRUE;
    }
    return yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    // yaml_parse() will error with an empty value.
    if (!trim($raw)) {
      return NULL;
    }
    // @todo Use ErrorExceptions when https://drupal.org/node/1247666 is in.
    // yaml_parse() will throw errors instead of raising an exception. Until
    // such time as Drupal supports native PHP ErrorExceptions as the error
    // handler, we need to temporarily set the error handler as ::errorHandler()
    // and then restore it after decoding has occurred. This allows us to turn
    // parsing errors into a throwable exception.
    // @see Drupal\Component\Serialization\Exception\InvalidDataTypeException
    // @see http://php.net/manual/en/class.errorexception.php
    set_error_handler([__CLASS__, 'errorHandler']);
    $ndocs = 0;
    $data = yaml_parse($raw, 0, $ndocs, [
      YAML_BOOL_TAG => '\Drupal\Component\Serialization\YamlPecl::applyBooleanCallbacks',
    ]);
    restore_error_handler();
    return $data;
  }

  /**
   * Handles errors for \Drupal\Component\Serialization\YamlPecl::decode().
   *
   * @param int $severity
   *   The severity level of the error.
   * @param string $message
   *   The error message to display.
   *
   * @see \Drupal\Component\Serialization\YamlPecl::decode()
   */
  public static function errorHandler($severity, $message) {
    restore_error_handler();
    throw new InvalidDataTypeException($message, $severity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Applies callbacks after parsing to ignore 1.1 style booleans.
   *
   * @param mixed $value
   *   Value from YAML file.
   * @param string $tag
   *   Tag that triggered the callback.
   * @param int $flags
   *   Scalar entity style flags.
   *
   * @return string|bool
   *   FALSE, false, TRUE and true are returned as booleans, everything else is
   *   returned as a string.
   */
  public static function applyBooleanCallbacks($value, $tag, $flags) {
    // YAML 1.1 spec dictates that 'Y', 'N', 'y' and 'n' are booleans. But, we
    // want the 1.2 behavior, so we only consider 'false', 'FALSE', 'true' and
    // 'TRUE' as booleans.
    if (!in_array(strtolower($value), ['false', 'true'], TRUE)) {
      return $value;
    }
    $map = [
      'false' => FALSE,
      'true' => TRUE,
    ];
    return $map[strtolower($value)];
  }

}
