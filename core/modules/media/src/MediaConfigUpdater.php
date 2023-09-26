<?php

namespace Drupal\media;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 *   This class is only meant to fix outdated media configuration and its
 *   methods should not be invoked directly. It will be removed once all the
 *   associated updates have been removed.
 */
class MediaConfigUpdater {

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  private $deprecationsEnabled = FALSE;

  /**
   * Stores which deprecations were triggered.
   *
   * @var bool
   */
  private $triggeredDeprecations = [];

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
   * Processes oembed type fields.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display
   *   The view display.
   *
   * @return bool
   *   Whether the display was updated.
   */
  public function processOembedEagerLoadField(EntityViewDisplayInterface $view_display): bool {
    $changed = FALSE;

    foreach ($view_display->getComponents() as $field => $component) {
      if (array_key_exists('type', $component)
        && ($component['type'] === 'oembed')
        && !array_key_exists('loading', $component['settings'])) {
        $component['settings']['loading']['attribute'] = 'eager';
        $view_display->setComponent($field, $component);
        $changed = TRUE;
      }
    }

    $deprecations_triggered = &$this->triggeredDeprecations['3212351'][$view_display->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The oEmbed loading attribute update for view display "%s" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3275103', $view_display->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

}
