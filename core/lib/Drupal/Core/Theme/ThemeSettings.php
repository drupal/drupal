<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeSettings.
 */

namespace Drupal\Core\Theme;

use Drupal\Component\Utility\NestedArray;

/**
 * Defines the default theme settings object.
 */
class ThemeSettings {

  /**
   * The theme of the theme settings object.
   *
   * @var string
   */
  protected $theme;

  /**
   * The data of the theme settings object.
   *
   * @var array
   */
  protected $data;

  /**
   * Constructs a theme settings object.
   *
   * @param string $name
   *   The name of the theme settings object being constructed.
   */
  public function __construct($theme) {
    $this->theme = $theme;
    $this->data = array();
  }

  /**
   * Returns the theme of this theme settings object.
   *
   * @return string
   *   The theme of this theme settings object.
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * Gets data from this theme settings object.
   *
   * @param string $key
   *   A string that maps to a key within the theme settings data.
   *   For instance in the following theme settings array:
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
   * Replaces the data of this theme settings object.
   *
   * @param array $data
   *   The new theme settings data.
   *
   * @return \Drupal\Core\Theme\ThemeSettings
   *   The theme settings object.
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * Sets value in this theme settings object.
   *
   * @param string $key
   *   Identifier to store value in theme settings.
   * @param string $value
   *   Value to associate with identifier.
   *
   * @return \Drupal\Core\Theme\ThemeSettings
   *   The theme settings object.
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
   * Unsets value in this theme settings object.
   *
   * @param string $key
   *   Name of the key whose value should be unset.
   *
   * @return \Drupal\Core\Theme\ThemeSettings
   *   The theme settings object.
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
   * Merges the data into this theme settings object.
   *
   * @param array $data
   *   Theme settings data to merge.
   *
   * @return \Drupal\Core\Theme\ThemeSettings
   *   The theme settings object.
   */
  public function mergeData ($data) {
    $this->data = NestedArray::mergeDeep($this->data, $data);
    return $this;
  }
}
