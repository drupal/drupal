<?php

namespace Drupal\block;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Provides a repository for Block config entities.
 */
class BlockRepository implements BlockRepositoryInterface {

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockStorage;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Constructs a new BlockRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The plugin context handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, ContextHandlerInterface $context_handler) {
    $this->blockStorage = $entity_type_manager->getStorage('block');
    $this->themeManager = $theme_manager;
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleBlocksPerRegion(array &$cacheable_metadata = []) {
    $active_theme = $this->themeManager->getActiveTheme();
    // Build an array of the region names in the right order.
    $empty = array_fill_keys($active_theme->getRegions(), []);

    $full = [];
    foreach ($this->blockStorage->loadByProperties(['theme' => $active_theme->getName()]) as $block_id => $block) {
      /** @var \Drupal\block\BlockInterface $block */
      $access = $block->access('view', NULL, TRUE);
      $region = $block->getRegion();
      if (!isset($cacheable_metadata[$region])) {
        $cacheable_metadata[$region] = CacheableMetadata::createFromObject($access);
      }
      else {
        $cacheable_metadata[$region] = $cacheable_metadata[$region]->merge(CacheableMetadata::createFromObject($access));
      }

      // Set the contexts on the block before checking access.
      if ($access->isAllowed()) {
        $full[$region][$block_id] = $block;
      }
    }

    // Merge it with the actual values to maintain the region ordering.
    $assignments = array_intersect_key(array_merge($empty, $full), $empty);
    foreach ($assignments as &$assignment) {
      // Suppress errors because PHPUnit will indirectly modify the contents,
      // triggering https://bugs.php.net/bug.php?id=50688.
      @uasort($assignment, 'Drupal\block\Entity\Block::sort');
    }
    return $assignments;
  }

}
