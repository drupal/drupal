<?php

namespace Drupal\Core\Test;

/**
 * Object to test that security issues around serialization.
 */
class ObjectSerialization {

  /**
   * ObjectSerialization constructor.
   */
  public function __construct() {
    throw new \Exception('This object should never be constructed');
  }

  /**
   * ObjectSerialization deconstructor.
   */
  public function __destruct() {
    throw new \Exception('This object should never be destructed');
  }

}
