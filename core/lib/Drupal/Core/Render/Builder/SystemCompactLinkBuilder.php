<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'system_compact_link' element.
 */
class SystemCompactLinkBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'system_compact_link'];

  /**
   * Set the title property on the system_compact_link.
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
   * Set the route_name property on the system_compact_link.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setRouteName($value) {
    $this->set('route_name', $value);
    return $this;
  }

  /**
   * Set the route_parameters property on the system_compact_link.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setRouteParameters($value) {
    $this->set('route_parameters', $value);
    return $this;
  }

  /**
   * Set the href property on the system_compact_link.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHref($value) {
    $this->set('href', $value);
    return $this;
  }

  /**
   * Set the options property on the system_compact_link.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setOptions($value) {
    $this->set('options', $value);
    return $this;
  }

}
