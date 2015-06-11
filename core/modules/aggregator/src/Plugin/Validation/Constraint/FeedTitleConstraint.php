<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Validation\Constraint\FeedTitleConstraint.
 */

namespace Drupal\aggregator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating feed titles.
 *
 * @Constraint(
 *   id = "FeedTitle",
 *   label = @Translation("Feed title", context = "Validation")
 * )
 */
class FeedTitleConstraint extends Constraint {

  public $message = 'A feed named %value already exists. Enter a unique title.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}
