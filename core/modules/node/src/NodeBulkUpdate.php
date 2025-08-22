<?php

declare(strict_types=1);

namespace Drupal\node;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a service to update nodes in bulk.
 */
class NodeBulkUpdate {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Updates all nodes in the passed-in array with the passed-in field values.
   *
   * IMPORTANT NOTE: This function is intended to work when called from a form
   * submission handler. Calling it outside of the form submission process may
   * not work correctly.
   *
   * @param array $nodes
   *   Array of node nids or nodes to update.
   * @param array $updates
   *   Array of key/value pairs with node field names and the value to update
   *   that field to.
   * @param string|null $langcode
   *   (optional) The language updates should be applied to. If NULL all
   *   available languages are processed.
   * @param bool $load
   *   (optional) TRUE if $nodes contains an array of node IDs to be loaded,
   *   FALSE if it contains fully loaded nodes. Defaults to FALSE.
   * @param bool $revisions
   *   (optional) TRUE if $nodes contains an array of revision IDs instead of
   *   node IDs. Defaults to FALSE; will be ignored if $load is FALSE.
   */
  public function process(array $nodes, array $updates, ?string $langcode = NULL, bool $load = FALSE, bool $revisions = FALSE): void {
    // We use batch processing to prevent timeout when updating a large number
    // of nodes.
    if (count($nodes) > 10) {
      $batch_builder = (new BatchBuilder())
        ->addOperation([static::class, 'batchProcess'], [$nodes, $updates, $langcode, $load, $revisions])
        ->setFinishCallback([static::class, 'batchFinished'])
        ->setTitle($this->t('Processing'))
        ->setErrorMessage($this->t('The update has encountered an error.'))
        // We use a single multi-pass operation, so the default
        // 'Remaining x of y operations' message will be confusing here.
        ->setProgressMessage('');
      batch_set($batch_builder->toArray());
    }
    else {
      /** @var \Drupal\node\NodeStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('node');
      if ($load) {
        $nodes = $revisions ? $storage->loadMultipleRevisions($nodes) : $storage->loadMultiple($nodes);
      }
      foreach ($nodes as $node) {
        static::processNode($node, $updates, $langcode);
      }
      $this->messenger->addStatus($this->t('The update has been performed.'));
    }
  }

  /**
   * Updates individual nodes when fewer than 10 are queued.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node to update.
   * @param array $updates
   *   Associative array of updates.
   * @param string|null $langcode
   *   (optional) The language updates should be applied to. If NULL all
   *   available languages are processed.
   *
   * @return \Drupal\node\NodeInterface
   *   An updated node object.
   *
   * @see self::process()
   */
  protected static function processNode(NodeInterface $node, array $updates, string|null $langcode = NULL): NodeInterface {
    $langcodes = isset($langcode) ? [$langcode] : array_keys($node->getTranslationLanguages());
    // For efficiency manually save the original node before applying any
    // changes.
    $node->setOriginal(clone $node);
    foreach ($langcodes as $langcode) {
      foreach ($updates as $name => $value) {
        $node->getTranslation($langcode)->$name = $value;
      }
    }
    $node->save();
    return $node;
  }

  /**
   * Executes a batch operation for processing a node bulk update.
   *
   * @param array $nodes
   *   An array of node IDs.
   * @param array $updates
   *   Associative array of updates.
   * @param string|null $langcode
   *   The language updates should be applied to. If none is specified all
   *   available languages are processed.
   * @param bool $load
   *   TRUE if $nodes contains an array of node IDs to be loaded, FALSE if it
   *   contains fully loaded nodes.
   * @param bool $revisions
   *   (optional) TRUE if $nodes contains an array of revision IDs instead of
   *   node IDs. Defaults to FALSE; will be ignored if $load is FALSE.
   * @param array|\ArrayAccess $context
   *   An array of contextual key/values.
   */
  public static function batchProcess(array $nodes, array $updates, ?string $langcode, bool $load, bool $revisions, array|\ArrayAccess &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($nodes);
      $context['sandbox']['nodes'] = $nodes;
    }

    // Process nodes by groups of 5.
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $count = min(5, count($context['sandbox']['nodes']));
    for ($i = 1; $i <= $count; $i++) {
      // For each nid, load the node, reset the values, and save it.
      $node = array_shift($context['sandbox']['nodes']);
      if ($load) {
        $node = $revisions ? $storage->loadRevision($node) : $storage->load($node);
      }
      $node = static::processNode($node, $updates, $langcode);

      // Store result for post-processing in the finished callback.
      $context['results'][] = $node->toLink()->toString();

      // Update our progress information.
      $context['sandbox']['progress']++;
    }

    // Inform the batch engine that we are not finished, and provide an
    // estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Reports the 'finished' status of batch operation the node bulk update.
   *
   * @param bool $success
   *   A boolean indicating whether the batch mass update operation successfully
   *   concluded.
   * @param string[] $results
   *   An array of rendered links to nodes updated via the batch mode process.
   *
   * @see self::batchProcess()
   */
  public static function batchFinished(bool $success, array $results): void {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addStatus(t('The update has been performed.'));
    }
    else {
      $messenger->addError(t('An error occurred and processing did not complete.'));
      $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
      $item_list = [
        '#theme' => 'item_list',
        '#items' => $results,
      ];
      $message .= \Drupal::service('renderer')->render($item_list);
      $messenger->addStatus($message);
    }
  }

}
