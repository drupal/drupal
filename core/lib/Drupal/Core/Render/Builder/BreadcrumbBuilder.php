<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'breadcrumb' element.
 */
class BreadcrumbBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'breadcrumb'];

  /**
   * Set the links property on the breadcrumb.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLinks($value) {
    $this->set('links', $value);
    return $this;
  }

}
