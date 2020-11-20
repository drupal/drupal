<?php

namespace Zumba\GastonJS;

/**
 * Class Cookie
 * @package Zumba\GastonJS
 */
class Cookie {
  /** @var  array */
  protected $attributes;

  /**
   * @param $attributes
   */
  public function __construct($attributes) {
    $this->attributes = $attributes;
  }

  /**
   * Returns the cookie name
   * @return string
   */
  public function getName() {
    return $this->attributes['name'];
  }

  /**
   * Returns the cookie value
   * @return string
   */
  public function getValue() {
    return urldecode($this->attributes['value']);
  }

  /**
   * Returns the cookie domain
   * @return string
   */
  public function getDomain() {
    return $this->attributes['domain'];
  }

  /**
   * Returns the path were the cookie is valid
   * @return string
   */
  public function getPath() {
    return $this->attributes['path'];
  }

  /**
   * Is a secure cookie?
   * @return bool
   */
  public function isSecure() {
    return isset($this->attributes['secure']);
  }

  /**
   * Is http only cookie?
   * @return bool
   */
  public function isHttpOnly() {
    return isset($this->attributes['httponly']);
  }

  /**
   * Returns cookie expiration time
   * @return mixed
   */
  public function getExpirationTime() {
    //TODO: return a \DateTime object
    if (isset($this->attributes['expiry'])) {
      return $this->attributes['expiry'];
    }
    return null;
  }
}
