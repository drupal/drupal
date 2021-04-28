<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'username' element.
 */
class UsernameBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'username'];

  /**
   * Set the account property on the username.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAccount($value) {
    $this->set('account', $value);
    return $this;
  }

  /**
   * Set the attributes property on the username.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAttributes($value) {
    $this->set('attributes', $value);
    return $this;
  }

  /**
   * Set the link_options property on the username.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLinkOptions($value) {
    $this->set('link_options', $value);
    return $this;
  }

}
