<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Type\UriInterface;

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
class Uri extends StringData implements UriInterface {

}
