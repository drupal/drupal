<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Email.
 */

namespace Drupal\Core\TypedData\Type;

/**
 * The Email data type.
 *
 * The plain value of Email is the email address represented as PHP string.
 */
class Email extends String {

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::validate().
   */
  public function validate() {
    // @todo Implement validate() method.
  }

}
