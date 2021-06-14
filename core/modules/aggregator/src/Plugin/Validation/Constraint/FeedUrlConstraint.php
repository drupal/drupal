<?php

namespace Drupal\aggregator\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Supports validating feed URLs.
 *
 * @Constraint(
 *   id = "FeedUrl",
 *   label = @Translation("Feed URL", context = "Validation")
 * )
 */
class FeedUrlConstraint extends UniqueFieldConstraint {

  public $message = 'A feed with this URL %value already exists. Enter a unique URL.';

}
