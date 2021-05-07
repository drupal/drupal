<?php

namespace Drupal\user\Entity\Handler;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity handler to do a batch update on user account cancellation.
 */
class BatchCancellationHandler extends DefaultCancellationHandler {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * BatchCancellationHandler constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $storage
   *   The entity storage handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(ContentEntityTypeInterface $entity_type, ContentEntityStorageInterface $storage, RendererInterface $renderer) {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function updateMultiple(array $ids, string $method): void {
    // Use batch processing to prevent timeout when updating a large number
    // of entities.
    if (count($ids) > 10) {
      $batch = $this->buildBatch($ids, $method);
      batch_set($batch->toArray());
    }
    else {
      parent::updateMultiple($ids, $method);
    }
  }

  /**
   * Builds a batch definition for updating multiple entities.
   *
   * @param array $ids
   *   The IDs, or revision IDs, of the entities to update.
   * @param string $method
   *   The user account cancellation method.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The batch definition builder.
   */
  protected function buildBatch(array $ids, string $method): BatchBuilder {
    return (new BatchBuilder())
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      ->setProgressMessage('')
      ->setErrorMessage($this->t('The update has encountered an error.'))
      ->setFinishCallback(static::class . '::batchFinished')
      ->addOperation(static::class . '::batchProcess', [
        $this->entityType->id(),
        $ids,
        $method,
      ]);
  }

  /**
   * Statically instantiates this handler during batch processing.
   *
   * @param string $entity_type_id
   *   The entity type ID with which the handler should be associated.
   *
   * @return static
   *   The entity handler.
   */
  protected static function getInstance(string $entity_type_id) {
    return \Drupal::entityTypeManager()
      ->getHandler($entity_type_id, 'user_cancel');
  }

  /**
   * Static wrapper called during batch processing.
   *
   * @param string $entity_type_id
   *   The entity type ID being processed.
   * @param array $ids
   *   The entity IDs, or revision IDs, being processed.
   * @param string $method
   *   The account cancellation method.
   * @param array $context
   *   The current context of the batch operation.
   */
  public static function batchProcess(string $entity_type_id, array $ids, string $method, array &$context): void {
    // The entity type ID is needed by other batch callbacks, so we need to
    // store it in a persistent place.
    $context['results']['_entity_type_id'] = $entity_type_id;

    static::getInstance($entity_type_id)
      ->doBatchProcess($ids, $method, $context);
  }

  /**
   * Processes a set of entities during a batch operation.
   *
   * @param array $ids
   *   The entity IDs, or revision IDs, being processed.
   * @param string $method
   *   The account cancellation method.
   * @param array $context
   *   The current context of the batch operation.
   */
  protected function doBatchProcess(array $ids, string $method, array &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($ids);
      $context['sandbox']['entities'] = $ids;
    }

    // Process entities by groups of 5.
    $count = min(5, count($context['sandbox']['entities']));
    for ($i = 1; $i <= $count; $i++) {
      // For each ID, load the entity, reset the values, and save it.
      $entity = array_shift($context['sandbox']['entities']);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityType->isRevisionable()
        ? $this->storage->loadRevision($entity)
        : $this->storage->load($entity);
      $this->updateEntity($entity, $method);

      // Store result for post-processing in the finished callback.
      $context['results'][] = $entity->toLink();

      // Update our progress information.
      $context['sandbox']['progress']++;
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Static wrapper called when the batch operation is finished.
   *
   * @param bool $success
   *   A boolean indicating whether the batch mass update operation successfully
   *   concluded.
   * @param string[] $results
   *   An array of rendered links to entities updated via the batch mode
   *   process.
   * @param array $operations
   *   An array of function calls.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    static::getInstance($results['_entity_type_id'])
      ->doFinish($success, $results, $operations);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Reports the 'finished' status of the batch mass update operation.
   *
   * @param bool $success
   *   A boolean indicating whether the batch mass update operation successfully
   *   concluded.
   * @param string[] $results
   *   An array of rendered links to entities updated via the batch mode
   *   process.
   * @param array $operations
   *   An array of function calls (not used in this function).
   */
  protected function doFinish(bool $success, array $results, array $operations): void {
    if ($success) {
      $this->messenger()->addStatus($this->t('The update has been performed.'));
    }
    else {
      $this->messenger()->addError($this->t('An error occurred and processing did not complete.'));
      $message = $this->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
      unset($results['_entity_type_id']);
      $item_list = [
        '#theme' => 'item_list',
        '#items' => $results,
      ];
      $message .= $this->renderer->render($item_list);
      $this->messenger()->addStatus($message);
    }
  }

}
