<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigBase.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use \Drupal\Core\DependencyInjection\DependencySerialization;

/**
 * Provides a base class for configuration objects with get/set support.
 *
 * Encapsulates all capabilities needed for runtime configuration handling for
 * a specific configuration object.
 *
 * Extend directly from this class for non-storable configuration where the
 * configuration API is desired but storage is not possible; for example, if
 * the data is derived at runtime. For storable configuration, extend
 * \Drupal\Core\Config\StorableConfigBase.
 *
 * @see \Drupal\Core\Config\StorableConfigBase
 * @see \Drupal\Core\Config\Config
 * @see \Drupal\Core\Theme\ThemeSettings
 */
abstract class ConfigBase extends DependencySerialization {

  /**
   * The name of the configuration object.
   *
   * @var string
   */
  protected $name;

  /**
   * The data of the configuration object.
   *
   * @var array
   */
  protected $data = array();

  /**
   * The maximum length of a configuration object name.
   *
   * Many filesystems (including HFS, NTFS, and ext4) have a maximum file name
   * length of 255 characters. To ensure that no configuration objects
   * incompatible with this limitation are created, we enforce a maximum name
   * length of 250 characters (leaving 5 characters for the file extension).
   *
   * @see http://en.wikipedia.org/wiki/Comparison_of_file_systems
   *
   * Configuration objects not stored on the filesystem should still be
   * restricted in name length so name can be used as a cache key.
   */
  const MAX_NAME_LENGTH = 250;

  /**
   * Returns the name of this configuration object.
   *
   * @return string
   *   The name of the configuration object.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of this configuration object.
   *
   * @param string $name
   *  The name of the configuration object.
   *
   * @return $this
   *   The configuration object.
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * Validates the configuration object name.
   *
   * @param string $name
   *  The name of the configuration object.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   *
   * @see Config::MAX_NAME_LENGTH
   */
  public static function validateName($name) {
    // The name must be namespaced by owner.
    if (strpos($name, '.') === FALSE) {
      throw new ConfigNameException(String::format('Missing namespace in Config object name @name.', array(
        '@name' => $name,
      )));
    }
    // The name must be shorter than Config::MAX_NAME_LENGTH characters.
    if (strlen($name) > self::MAX_NAME_LENGTH) {
      throw new ConfigNameException(String::format('Config object name @name exceeds maximum allowed length of @length characters.', array(
        '@name' => $name,
        '@length' => self::MAX_NAME_LENGTH,
      )));
    }

    // The name must not contain any of the following characters:
    // : ? * < > " ' / \
    if (preg_match('/[:?*<>"\'\/\\\\]/', $name)) {
      throw new ConfigNameException(String::format('Invalid character in Config object name @name.', array(
        '@name' => $name,
      )));
    }
  }

  /**
   * Gets data from this configuration object.
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *   For instance in the following configuration array:
   *   @code
   *   array(
   *     'foo' => array(
   *       'bar' => 'baz',
   *     ),
   *   );
   *   @endcode
   *   A key of 'foo.bar' would return the string 'baz'. However, a key of 'foo'
   *   would return array('bar' => 'baz').
   *   If no key is specified, then the entire data array is returned.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function get($key = '') {
    if (empty($key)) {
      return $this->data;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($this->data[$key]) ? $this->data[$key] : NULL;
      }
      else {
        $value = NestedArray::getValue($this->data, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

  /**
   * Replaces the data of this configuration object.
   *
   * @param array $data
   *   The new configuration data.
   *
   * @return $this
   *   The configuration object.
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * Sets a value in this configuration object.
   *
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @return $this
   *   The configuration object.
   */
  public function set($key, $value) {
    // The dot/period is a reserved character; it may appear between keys, but
    // not within keys.
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      $this->data[$key] = $value;
    }
    else {
      NestedArray::setValue($this->data, $parts, $value);
    }
    return $this;
  }

  /**
   * Unsets a value in this configuration object.
   *
   * @param string $key
   *   Name of the key whose value should be unset.
   *
   * @return $this
   *   The configuration object.
   */
  public function clear($key) {
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      unset($this->data[$key]);
    }
    else {
      NestedArray::unsetValue($this->data, $parts);
    }
    return $this;
  }

  /**
   * Merges data into a configuration object.
   *
   * @param array $data_to_merge
   *   An array containing data to merge.
   *
   * @return $this
   *   The configuration object.
   */
  public function merge(array $data_to_merge) {
    // Preserve integer keys so that configuration keys are not changed.
    $this->setData(NestedArray::mergeDeepArray(array($this->data, $data_to_merge), TRUE));
    return $this;
  }
}
