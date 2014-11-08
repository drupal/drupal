<?php

/**
 * @file
 * Contains \Drupal\block\BlockRepository.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityManagerInterface;
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
   * Constructs a new BlockRepository.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ThemeManagerInterface $theme_manager) {
    $this->blockStorage = $entity_manager->getStorage('block');
    $this->themeManager = $theme_manager;
  }

  /**
   * Returns the human-readable list of regions keyed by machine name.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  protected function getRegionNames() {
    return system_region_list($this->getTheme());
  }

  /**
   * Gets the current theme for this page.
   *
   * @return string
   *   The current theme.
   */
  protected function getTheme() {
    return $this->themeManager->getActiveTheme()->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleBlocksPerRegion() {
    // Build an array of the region names in the right order.
    $empty = array_fill_keys(array_keys($this->getRegionNames()), array());

    $full = array();
    foreach ($this->blockStorage->loadByProperties(array('theme' => $this->getTheme())) as $block_id => $block) {
      if ($block->access('view')) {
        $full[$block->get('region')][$block_id] = $block;
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
