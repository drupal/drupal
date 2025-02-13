<?php

declare(strict_types=1);

namespace Drupal\block_content_test\Hook;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for block_content_test.
 */
class BlockContentTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_view().
   */
  #[Hook('block_content_view')]
  public function blockContentView(array &$build, BlockContent $block_content, $view_mode): void {
    // Add extra content.
    $build['extra_content'] = ['#markup' => '<blink>Wow</blink>'];
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('block_content_presave')]
  public function blockContentPresave(BlockContent $block_content): void {
    if ($block_content->label() == 'testing_block_content_presave') {
      $block_content->setInfo($block_content->label() . '_presave');
    }
    // Determine changes.
    if ($block_content->getOriginal() && $block_content->getOriginal()->label() == 'test_changes') {
      if ($block_content->getOriginal()->label() != $block_content->label()) {
        $block_content->setInfo($block_content->label() . '_presave');
        // Drupal 1.0 release.
        $block_content->changed = 979534800;
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('block_content_update')]
  public function blockContentUpdate(BlockContent $block_content): void {
    // Determine changes on update.
    if ($block_content->getOriginal() && $block_content->getOriginal()->label() == 'test_changes') {
      if ($block_content->getOriginal()->label() != $block_content->label()) {
        $block_content->setInfo($block_content->label() . '_update');
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * This tests saving a block_content on block_content insert.
   *
   * @see \Drupal\block_content\Tests\BlockContentSaveTest::testBlockContentSaveOnInsert()
   */
  #[Hook('block_content_insert')]
  public function blockContentInsert(BlockContent $block_content): void {
    // Set the block_content title to the block_content ID and save.
    if ($block_content->label() == 'new') {
      $block_content->setInfo('BlockContent ' . $block_content->id());
      $block_content->setNewRevision(FALSE);
      $block_content->save();
    }
    if ($block_content->label() == 'fail_creation') {
      throw new \Exception('Test exception for rollback.');
    }
  }

}
