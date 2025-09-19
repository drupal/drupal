<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search\SearchIndexInterface;

/**
 * Search related hook implementations for node module.
 */
class NodeSearchHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly StateInterface $state,
    protected readonly ?SearchIndexInterface $searchIndex = NULL,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    // Calculate the oldest and newest node created times, for use in search
    // rankings. (Note that field aliases have to be variables passed by
    // reference.)
    if ($this->moduleHandler->moduleExists('search')) {
      $min_alias = 'min_created';
      $max_alias = 'max_created';
      $result = $this->entityTypeManager->getStorage('node')->getAggregateQuery()->accessCheck(FALSE)->aggregate('created', 'MIN', NULL, $min_alias)->aggregate('created', 'MAX', NULL, $max_alias)->execute();
      if (isset($result[0])) {
        // Make an array with definite keys and store it in the state system.
        $array = ['min_created' => $result[0][$min_alias], 'max_created' => $result[0][$max_alias]];
        $this->state->set('node.min_max_update_time', $array);
      }
    }
  }

  /**
   * Implements hook_node_search_ranking().
   */
  #[Hook('node_search_ranking')]
  public function ranking(): array {
    // Create the ranking array and add the basic ranking options.
    $ranking = [
      'relevance' => [
        'title' => $this->t('Keyword relevance'),
        // Average relevance values hover around 0.15
        'score' => 'i.relevance',
      ],
      'sticky' => [
        'title' => $this->t('Content is sticky at top of lists'),
        // The sticky flag is either 0 or 1, which is automatically normalized.
        'score' => 'n.sticky',
      ],
      'promote' => [
        'title' => $this->t('Content is promoted to the front page'),
        // The promote flag is either 0 or 1, which is automatically normalized.
        'score' => 'n.promote',
      ],
    ];
    // Add relevance based on updated date, but only if it the scale values have
    // been calculated in node_cron().
    if ($node_min_max = $this->state->get('node.min_max_update_time')) {
      $ranking['recent'] = [
        'title' => $this->t('Recently created'),
        // Exponential decay with half life of 14% of the age range of nodes.
        'score' => 'EXP(-5 * (1 - (n.created - :node_oldest) / :node_range))',
        'arguments' => [
          ':node_oldest' => $node_min_max['min_created'],
          ':node_range' => max($node_min_max['max_created'] - $node_min_max['min_created'], 1),
        ],
      ];
    }
    return $ranking;
  }

  /**
   * Implements hook_node_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate($node): void {
    $this->reindexNodeForSearch($node->id());
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for comment entities.
   */
  #[Hook('comment_insert')]
  public function commentInsert($comment): void {
    // Reindex the node when comments are added.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      $this->reindexNodeForSearch($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for comment entities.
   */
  #[Hook('comment_update')]
  public function commentUpdate($comment): void {
    // Reindex the node when comments are changed.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      $this->reindexNodeForSearch($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for comment entities.
   */
  #[Hook('comment_delete')]
  public function commentDelete($comment): void {
    // Reindex the node when comments are deleted.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      $this->reindexNodeForSearch($comment->getCommentedEntityId());
    }
  }

  /**
   * Reindex a node for search.
   *
   * @param string|int $nid
   *   The node ID to reindex.
   */
  protected function reindexNodeForSearch(string|int $nid): void {
    // Reindex node context indexed by the node module search plugin.
    $this->searchIndex?->markForReindex('node_search', (int) $nid);
  }

}
