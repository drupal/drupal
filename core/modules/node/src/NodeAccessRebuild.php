<?php

declare(strict_types=1);

namespace Drupal\node;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Utility\Environment;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides methods for checking and rebuilding node access permissions.
 */
class NodeAccessRebuild {

  use StringTranslationTrait;

  public const string STATE_KEY = 'node.node_access_needs_rebuild';

  public function __construct(
    protected readonly StateInterface $state,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly NodeGrantDatabaseStorageInterface $grantStorage,
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Reads the value of a flag for rebuilding the node access grants.
   *
   * When the flag is set, a message is displayed to users with 'access
   * administration pages' permission, pointing to the 'rebuild' confirm form.
   * This can be used as an alternative to direct rebuild calls,
   * allowing administrators to decide when they want to perform the actual
   * (possibly time consuming) rebuild.
   *
   * When unsure if the current user is an administrator, self::rebuild()
   * should be used instead.
   *
   * @return bool
   *   The current value of the flag.
   *
   * @see self::rebuild()
   */
  public function needsRebuild(): bool {
    return $this->state->get(self::STATE_KEY, FALSE);
  }

  /**
   * Sets the value of a flag for rebuilding the node access grants.
   *
   * @param bool $rebuild
   *   (optional) Sets the value of the state key to TRUE if TRUE, otherwise
   *   deletes the key. Defaults to TRUE.
   *
   * @see self::rebuild()
   */
  public function setNeedsRebuild(bool $rebuild = TRUE): void {
    if ($rebuild) {
      $this->state->set(self::STATE_KEY, TRUE);
    }
    else {
      $this->state->delete(self::STATE_KEY);
    }
  }

  /**
   * Rebuilds the node access database.
   *
   * This rebuild is occasionally needed by modules that make system-wide
   * changes to access levels. When the rebuild is required by an
   * admin-triggered action (e.g module settings form), calling
   * self::setNeedsRebuild(TRUE) instead of self::rebuild() lets the user
   * perform changes and actually rebuild only once done.
   *
   * @param bool $batch_mode
   *   (optional) Set to TRUE to process in 'batch' mode, spawning processing
   *   over several HTTP requests (thus avoiding the risk of PHP timeout if the
   *   site has a large number of nodes). hook_update_N() and any form submit
   *   handler are safe contexts to use the 'batch mode'. Less decidable cases
   *   (such as calls from hook_user(), hook_taxonomy(), etc.) might consider
   *   using the non-batch mode. Defaults to FALSE. Calling this method
   *   multiple times in the same request with $batch_mode set to TRUE will
   *   only result in one batch set being added.
   *
   * @see self::needsRebuild()
   */
  public function rebuild(bool $batch_mode = FALSE): void {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('node');

    // If the rebuild fails to complete, and node_access_needs_rebuild is not
    // set to TRUE, the node_access table is left in an incomplete state.
    // Force node_access_needs_rebuild to TRUE once existing grants are deleted,
    // to signal that the node access table still needs to be rebuilt if this
    // function does not finish.
    $this->setNeedsRebuild(TRUE);
    $access_control_handler->deleteGrants();

    // Only recalculate if the site is using a node_access module.
    if ($this->moduleHandler->hasImplementations('node_grants')) {
      if ($batch_mode) {
        if (!BatchBuilder::isSetIdRegistered(__FUNCTION__)) {
          $batch_builder = (new BatchBuilder())
            ->setTitle($this->t('Rebuilding content access permissions'))
            ->addOperation(static::class . ':batchOperation')
            ->setFinishCallback(static::class . ':batchFinished')
            ->registerSetId(__FUNCTION__);
          batch_set($batch_builder->toArray());
        }
      }
      else {
        // Try to allocate enough time to rebuild node grants
        Environment::setTimeLimit(240);

        // Rebuild newest nodes first so that recent content becomes available
        // quickly.
        $nids = $node_storage->getQuery()
          ->sort('nid', 'DESC')
          // Disable access checking since all nodes must be processed even
          // if the user does not have access. And unless the current user
          // has the bypass node access permission, no nodes are accessible
          // since the grants have just been deleted.
          ->accessCheck(FALSE)
          ->execute();

        foreach ($nids as $nid) {
          $node_storage->resetCache([$nid]);
          $node = $node_storage->load($nid);
          // To preserve database integrity, only write grants if the node
          // loads successfully.
          if ($node instanceof NodeInterface) {
            $grants = $access_control_handler->acquireGrants($node);
            $this->grantStorage->write($node, $grants);
          }
        }
      }
    }
    else {
      // Not using any node_access modules. Add the default grant.
      $access_control_handler->writeDefaultGrant();
    }

    if (!isset($batch_builder)) {
      $this->messenger->addStatus($this->t('Content permissions have been rebuilt.'));
      $this->setNeedsRebuild(FALSE);
    }
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs batch operation for \Drupal\node\NodeAccessRebuild::rebuild().
   *
   * This is a multistep operation: we go through all nodes by packs of 20. The
   * batch processing engine interrupts processing and sends progress feedback
   * after 1 second execution time.
   *
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchOperation(array &$context): void {
    $node_storage = $this->entityTypeManager->getStorage('node');

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['max'] = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }

    $limit = 20;
    $nids = $node_storage->getQuery()
      ->condition('nid', $context['sandbox']['current_node'], '>')
      ->sort('nid', 'ASC')
      // Disable access checking since all nodes must be processed even if the
      // user does not have access. And unless the current user has the bypass
      // node access permission, no nodes are accessible since the grants have
      // just been deleted.
      ->accessCheck(FALSE)
      ->range(0, $limit)
      ->execute();

    $node_storage->resetCache($nids);
    $nodes = $node_storage->loadMultiple($nids);
    /** @var \Drupal\node\NodeAccessControlHandlerInterface $access_control_handler */
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('node');
    foreach ($nids as $nid) {
      $node = $nodes[$nid] ?? NULL;

      // To preserve database integrity, only write grants if the node
      // loads successfully.
      if ($node instanceof NodeInterface) {
        $grants = $access_control_handler->acquireGrants($node);
        $this->grantStorage->write($node, $grants);
      }
      $context['sandbox']['progress']++;
      $context['sandbox']['current_node'] = $nid;
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Implements callback_batch_finished().
   *
   * Performs post-processing for \Drupal\node\NodeAccessRebuild::rebuild().
   *
   * @param bool $success
   *   A boolean indicating whether the re-build process has completed.
   */
  public function batchFinished(bool $success): void {
    if ($success) {
      $this->messenger->addStatus($this->t('The content access permissions have been rebuilt.'));
      $this->setNeedsRebuild(FALSE);
    }
    else {
      $this->messenger->addError($this->t('The content access permissions have not been properly rebuilt.'));
    }
  }

}
