<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'page' element.
 */
class PageBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'page'];

  /**
   * Set the page property on the page.
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
   * Set the title property on the page.
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
   * Set the title_prefix property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitlePrefix($value) {
    $this->set('title_prefix', $value);
    return $this;
  }

  /**
   * Set the title_suffix property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitleSuffix($value) {
    $this->set('title_suffix', $value);
    return $this;
  }

  /**
   * Set the front_page property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setFrontPage($value) {
    $this->set('front_page', $value);
    return $this;
  }

  /**
   * Set the logo property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLogo($value) {
    $this->set('logo', $value);
    return $this;
  }

  /**
   * Set the site_name property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSiteName($value) {
    $this->set('site_name', $value);
    return $this;
  }

  /**
   * Set the site_slogan property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSiteSlogan($value) {
    $this->set('site_slogan', $value);
    return $this;
  }

  /**
   * Set the action_links property on the page.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setActionLinks($value) {
    $this->set('action_links', $value);
    return $this;
  }

}
