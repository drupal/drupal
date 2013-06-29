<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Email.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;

/**
 * The Email data type.
 *
 * The plain value of Email is the email address represented as PHP string.
 *
 * @DataType(
 *   id = "email",
 *   label = @Translation("Email"),
 *   primitive_type = 2,
 *   constraints = {"Email" = TRUE}
 * )
 */
class Email extends String {

}
