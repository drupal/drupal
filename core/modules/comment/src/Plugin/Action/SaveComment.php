<?php

namespace Drupal\comment\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Action\Plugin\Action\SaveAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Saves a comment.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\SaveAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\SaveAction
 * @see https://www.drupal.org/node/2919303
 *
 * @Action(
 *   id = "comment_save_action",
 *   label = @Translation("Save comment"),
 *   type = "comment"
 * )
 */
class SaveComment extends SaveAction {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $time);
    @trigger_error(__NAMESPACE__ . '\SaveComment is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\SaveAction instead. See https://www.drupal.org/node/2919303.', E_USER_DEPRECATED);
  }

}
