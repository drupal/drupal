<?php

/**
 * @file
 * Contains \Drupal\forum\ForumBreadcrumbBuilder.
 */

namespace Drupal\forum;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManager;

/**
 * Class to define the forum breadcrumb builder.
 */
class ForumBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * Configuration object for this builder.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new ForumBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration factory.
   */
  public function __construct(EntityManager $entity_manager, ConfigFactory $configFactory) {
    $this->entityManager = $entity_manager;
    $this->config = $configFactory->get('forum.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {

    // @todo This only works for legacy routes. Once node/% and forum/% are
    //   converted to the new router this code will need to be updated.
    if (isset($attributes['drupal_menu_item'])) {
      $item = $attributes['drupal_menu_item'];
      switch ($item['path']) {

        case 'node/%':
          $node = $item['map'][1];
          // Load the object in case of missing wildcard loaders.
          $node = is_object($node) ? $node : node_load($node);
          if (_forum_node_check_node_type($node)) {
            $breadcrumb = $this->forumPostBreadcrumb($node);
          }
          break;

        case 'forum/%':
          $term = $item['map'][1];
          // Load the object in case of missing wildcard loaders.
          $term = is_object($term) ? $term : forum_forum_load($term);
          $breadcrumb = $this->forumTermBreadcrumb($term);
          break;
      }
    }

    if (!empty($breadcrumb)) {
      return $breadcrumb;
    }
  }

  /**
   * Builds the breadcrumb for a forum post page.
   */
  protected function forumPostBreadcrumb($node) {
    $vocabulary = $this->entityManager->getStorageController('taxonomy_vocabulary')->load($this->config->get('vocabulary'));

    $breadcrumb[] = l(t('Home'), NULL);
    $breadcrumb[] = l($vocabulary->label(), 'forum');
    if ($parents = taxonomy_term_load_parents_all($node->forum_tid)) {
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $breadcrumb[] = l($parent->label(), 'forum/' . $parent->id());
      }
    }
    return $breadcrumb;
  }

  /**
   * Builds the breadcrumb for a forum term page.
   */
  protected function forumTermBreadcrumb($term) {
    $vocabulary = $this->entityManager->getStorageController('taxonomy_vocabulary')->load($this->config->get('vocabulary'));

    $breadcrumb[] = l(t('Home'), NULL);
    if ($term->tid) {
      // Parent of all forums is the vocabulary name.
      $breadcrumb[] = l($vocabulary->label(), 'forum');
    }
    // Add all parent forums to breadcrumbs.
    if ($term->parents) {
      foreach (array_reverse($term->parents) as $parent) {
        if ($parent->id() != $term->id()) {
          $breadcrumb[] = l($parent->label(), 'forum/' . $parent->id());
        }
      }
    }
    return $breadcrumb;
  }

}
