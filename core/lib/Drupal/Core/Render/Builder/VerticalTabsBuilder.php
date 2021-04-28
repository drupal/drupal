<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'vertical_tabs' element.
 */
class VerticalTabsBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'vertical_tabs'];

  /**
   * Set the default_tab property on the vertical_tabs.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDefaultTab($value) {
    $this->set('default_tab', $value);
    return $this;
  }

}
