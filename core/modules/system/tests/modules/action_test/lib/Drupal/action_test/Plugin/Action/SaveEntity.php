<?php

/**
 * @file
 * Contains \Drupal\action_test\Plugin\Action\SaveEntity.
 */

namespace Drupal\action_test\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ActionBase;

/**
 * Provides an operation to save user entities.
 *
 * @Action(
 *   id = "action_test_save_entity",
 *   label = @Translation("Saves entities"),
 *   type = "user"
 * )
 */
class SaveEntity extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->save();
  }

}
