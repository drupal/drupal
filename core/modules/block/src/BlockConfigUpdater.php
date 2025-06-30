<?php

declare(strict_types=1);

namespace Drupal\block;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 */
class BlockConfigUpdater {

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  protected bool $deprecationsEnabled = TRUE;

  /**
   * Stores which deprecations were triggered.
   *
   * @var array
   */
  protected array $triggeredDeprecations = [];

  /**
   * Sets the deprecations enabling status.
   *
   * @param bool $enabled
   *   Whether deprecations should be enabled.
   */
  public function setDeprecationsEnabled(bool $enabled): void {
    $this->deprecationsEnabled = $enabled;
  }

  /**
   * Performs the required update.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block to update.
   *
   * @return bool
   *   Whether the block was updated.
   */
  public function updateBlock(BlockInterface $block): bool {
    $changed = FALSE;
    if ($this->needsInfoStatusSettingsRemoved($block)) {
      $settings = $block->get('settings');
      unset($settings['info'], $settings['status']);
      $block->set('settings', $settings);
      $changed = TRUE;
    }
    return $changed;
  }

  /**
   * Checks if the block contains deprecated info and status settings.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block to update.
   *
   * @return bool
   *   TRUE if the block has deprecated settings.
   */
  public function needsInfoStatusSettingsRemoved(BlockInterface $block): bool {
    if (!str_starts_with($block->getPluginId(), 'block_content')) {
      return FALSE;
    }
    $settings = $block->get('settings');
    if (!isset($settings['info']) && !isset($settings['status'])) {
      return FALSE;
    }

    $deprecations_triggered = &$this->triggeredDeprecations['3426302'][$block->id()];
    if ($this->deprecationsEnabled && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error('Block content blocks with the "status" and "info" settings is deprecated in drupal:11.3.0 and will be removed in drupal:12.0.0. They were unused, so there is no replacement. Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3499836', E_USER_DEPRECATED);
    }

    return TRUE;
  }

}
