<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'html' element.
 */
class HtmlBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'html'];

  /**
   * Set the logged_in property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLoggedIn($value) {
    $this->set('logged_in', $value);
    return $this;
  }

  /**
   * Set the root_path property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setRootPath($value) {
    $this->set('root_path', $value);
    return $this;
  }

  /**
   * Set the node_type property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setNodeType($value) {
    $this->set('node_type', $value);
    return $this;
  }

  /**
   * Set the css property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setCss($value) {
    $this->set('css', $value);
    return $this;
  }

  /**
   * Set the head property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHead($value) {
    $this->set('head', $value);
    return $this;
  }

  /**
   * Set the head_title property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHeadTitle($value) {
    $this->set('head_title', $value);
    return $this;
  }

  /**
   * Set the title property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitle($value) {
    $this->set('title', $value);
    return $this;
  }

  /**
   * Set the page_top property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setPageTop($value) {
    $this->set('page_top', $value);
    return $this;
  }

  /**
   * Set the page property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setPage($value) {
    $this->set('page', $value);
    return $this;
  }

  /**
   * Set the page_bottom property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setPageBottom($value) {
    $this->set('page_bottom', $value);
    return $this;
  }

  /**
   * Set the styles property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setStyles($value) {
    $this->set('styles', $value);
    return $this;
  }

  /**
   * Set the scripts property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setScripts($value) {
    $this->set('scripts', $value);
    return $this;
  }

  /**
   * Set the db_offline property on the html.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDbOffline($value) {
    $this->set('db_offline', $value);
    return $this;
  }

}
