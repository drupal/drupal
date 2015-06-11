<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Validation\Constraint\FeedUrlConstraint.
 */

namespace Drupal\aggregator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating feed URLs.
 *
 * @Constraint(
 *   id = "FeedUrl",
 *   label = @Translation("Feed URL", context = "Validation")
 * )
 */
class FeedUrlConstraint extends Constraint {

  public $message = 'A feed with this URL %value already exists. Enter a unique URL.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}
