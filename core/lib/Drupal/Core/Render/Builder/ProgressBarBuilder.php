<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'progress_bar' element.
 */
class ProgressBarBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'progress_bar'];

  /**
   * Set the label property on the progress_bar.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLabel($value) {
    $this->set('label', $value);
    return $this;
  }

  /**
   * Set the percent property on the progress_bar.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setPercent($value) {
    $this->set('percent', $value);
    return $this;
  }

  /**
   * Set the message property on the progress_bar.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMessage($value) {
    $this->set('message', $value);
    return $this;
  }

}
