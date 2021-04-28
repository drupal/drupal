<?php

namespace Drupal\Core\Render\Builder;

use Drupal\Core\Render\RenderableInterface;

/**
 * Define a base that all theme builders extend.
 */
abstract class BuilderBase implements BuilderBaseInterface, RenderableInterface {

  /**
   * An array of the internal representation of the theme instance.
   *
   * @var array
   */
  protected $renderable = [];

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    return $this->renderable;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->renderable['#' . $key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrefix($value) {
    $this->set('prefix', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuffix($value) {
    $this->set('suffix', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostRender($value) {
    $this->set('post_render', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreRender($value) {
    $this->set('pre_render', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccess($value) {
    $this->set('access', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessCallback($value) {
    $this->set('access_callback', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($value) {
    $this->set('weight', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCache($value) {
    $this->set('cache', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setThemeWrappers($value) {
    $this->set('theme_wrappers', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAfterBuild($value) {
    $this->set('after_build', $value);
    return $this;
  }

}
