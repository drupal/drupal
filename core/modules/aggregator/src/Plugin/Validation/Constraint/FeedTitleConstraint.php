<?php

namespace Drupal\aggregator\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Supports validating feed titles.
 *
 * @Constraint(
 *   id = "FeedTitle",
 *   label = @Translation("Feed title", context = "Validation")
 * )
 */
class FeedTitleConstraint extends UniqueFieldConstraint {

  public $message = 'A feed named %value already exists. Enter a unique title.';

}
