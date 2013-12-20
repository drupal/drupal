<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Uri.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\PrimitiveBase;
use Drupal\Core\TypedData\Type\UriInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * The URI data type.
 *
 * The plain value of a URI is an absolute URI represented as PHP string.
 *
 * @DataType(
 *   id = "uri",
 *   label = @Translation("URI")
 * )
 */
class Uri extends String implements UriInterface {

}
