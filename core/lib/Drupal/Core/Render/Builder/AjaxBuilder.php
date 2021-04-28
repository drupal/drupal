<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'ajax' element.
 */
class AjaxBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'ajax'];

  /**
   * Set the headers property on the ajax.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHeaders($value) {
    $this->set('headers', $value);
    return $this;
  }

  /**
   * Set the commands property on the ajax.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setCommands($value) {
    $this->set('commands', $value);
    return $this;
  }

  /**
   * Set the error property on the ajax.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setError($value) {
    $this->set('error', $value);
    return $this;
  }

}
