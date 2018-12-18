<?php

namespace Drupal\user_batch_action_test\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides action that sets batch precessing.
 *
 * @Action(
 *   id = "user_batch_action_test_action",
 *   label = @Translation("Process user in batch"),
 *   type = "user",
 * )
 */
class BatchUserAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $operations = [];

    foreach ($entities as $entity) {
      $operations[] = [
        [get_class($this), 'processBatch'],
        [
          [
            'entity_type' => $entity->getEntityTypeId(),
            'entity_id' => $entity->id(),
          ],
        ],
      ];
    }

    if ($operations) {
      $batch = [
        'operations' => $operations,
        'finished' => [get_class($this), 'finishBatch'],
      ];
      batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

  /**
   * Processes the batch item.
   *
   * @param array $data
   *   Keyed array of data to process.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch($data, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['theme'] = \Drupal::service('theme.manager')->getActiveTheme(\Drupal::routeMatch())->getName();
    }
    $context['results']['processed']++;
  }

  /**
   * Finish batch.
   *
   * @param bool $success
   *   Indicates whether the batch process was successful.
   * @param array $results
   *   Results information passed from the processing callback.
   */
  public static function finishBatch($success, $results) {
    \Drupal::messenger()->addMessage(
      \Drupal::translation()->formatPlural($results['processed'], 'One item has been processed.', '@count items have been processed.')
    );
    \Drupal::messenger()->addMessage($results['theme'] . ' theme used');
  }

}
