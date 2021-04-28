<?php

namespace Drupal\Core\Render\Builder;

/**
 * Defines methods found on the base builder class.
 */
interface BuilderBaseInterface {

  /**
   * Create an instance of a builder object.
   *
   * @return $this
   */
  public static function create();

  /**
   * Set any key on the renderable array being constructed.
   *
   * @param string $key
   *   The key to set on the renderable array.
   * @param mixed $value
   *   The value to assign the key.
   */
  public function set($key, $value);

  /**
   * Set the 'prefix' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setPrefix($value);

  /**
   * Set the 'suffix' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setSuffix($value);

  /**
   * Set the 'post_render' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setPostRender($value);

  /**
   * Set the 'pre_render' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setPreRender($value);

  /**
   * Set the 'access' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setAccess($value);

  /**
   * Set the 'access_callback' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setAccessCallback($value);

  /**
   * Set the 'weight' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setWeight($value);

  /**
   * Set the 'cache' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setCache($value);

  /**
   * Set the 'theme_wrappers' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setThemeWrappers($value);

  /**
   * Set the 'after_build' property.
   *
   * @param array $value
   *   The value to assign the property.
   *
   * @return $this
   */
  public function setAfterBuild($value);

}
