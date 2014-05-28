<?php

/**
 * @file
 * Contains \Drupal\action_test\Plugin\Action\NoType.
 */

namespace Drupal\action_test\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Provides an operation with no type specified.
 *
 * @Action(
 *   id = "action_test_no_type",
 *   label = @Translation("An operation with no type specified")
 * )
 */
class NoType extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
  }

}
