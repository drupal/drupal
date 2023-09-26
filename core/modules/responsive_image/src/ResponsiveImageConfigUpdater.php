<?php

namespace Drupal\responsive_image;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 *   This class is only meant to fix outdated responsive image configuration and
 *   its methods should not be invoked directly.
 */
final class ResponsiveImageConfigUpdater {

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  private $deprecationsEnabled = TRUE;

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
   * Re-order mappings by breakpoint ID and descending numeric multiplier order.
   *
   * @param \Drupal\responsive_image\ResponsiveImageStyleInterface $responsive_image_style
   *   The responsive image style
   *
   * @return bool
   *   Whether the responsive image style was updated.
   *
   *   TODO: when removing this, evaluate if we need to keep it permanently
   *   to support an upgrade path (migration) from Drupal 7 picture module.
   */
  public function orderMultipliersNumerically(ResponsiveImageStyleInterface $responsive_image_style): bool {
    $changed = FALSE;

    $original_mapping_order = $responsive_image_style->getImageStyleMappings();
    $responsive_image_style->removeImageStyleMappings();
    foreach ($original_mapping_order as $mapping) {
      $responsive_image_style->addImageStyleMapping($mapping['breakpoint_id'], $mapping['multiplier'], $mapping);
    }
    if ($responsive_image_style->getImageStyleMappings() !== $original_mapping_order) {
      $changed = TRUE;
    }

    $deprecations_triggered = &$this->triggeredDeprecations['3267870'][$responsive_image_style->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The responsive image style multiplier re-ordering update for "%s" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Profile, module and theme provided Responsive Image configuration should be updated. See https://www.drupal.org/node/3274803', $responsive_image_style->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Processes responsive image type fields.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display
   *   The view display.
   *
   * @return bool
   *   Whether the display was updated.
   */
  public function processResponsiveImageField(EntityViewDisplayInterface $view_display): bool {
    $changed = FALSE;

    foreach ($view_display->getComponents() as $field => $component) {
      if (isset($component['type'])
        && $component['type'] === 'responsive_image'
        && !array_key_exists('image_loading', $component['settings'])
      ) {
        $component['settings']['image_loading']['attribute'] = 'eager';
        $view_display->setComponent($field, $component);
        $changed = TRUE;
      }
    }

    $deprecations_triggered = &$this->triggeredDeprecations['3192234'][$view_display->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The responsive image loading attribute update for "%s" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Configuration should be updated. See https://www.drupal.org/node/3279032', $view_display->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

}
