<?php

namespace Drupal\responsive_image;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 *   This class is only meant to fix outdated responsive image configuration and
 *   its methods should not be invoked directly. It will be removed once all the
 *   deprecated methods have been removed.
 */
final class ResponsiveImageConfigUpdater {

  /**
   * Re-order mappings by breakpoint ID and descending numeric multiplier order.
   *
   * @param \Drupal\responsive_image\ResponsiveImageStyleInterface $responsive_image_style
   *   The responsive image style
   *
   * @return bool
   *   Whether the responsive image style was updated.
   */
  public function orderMultipliersNumerically(ResponsiveImageStyleInterface $responsive_image_style): bool {
    $changed = FALSE;

    $mappings = $sorted = $responsive_image_style->getImageStyleMappings();
    usort($sorted, static function (array $a, array $b) {
      $first = ((float) mb_substr($a['multiplier'], 0, -1)) * 100;
      $second = ((float) mb_substr($b['multiplier'], 0, -1)) * 100;
      if ($first === $second) {
        return strcmp($a['breakpoint_id'], $b['breakpoint_id']);
      }
      return $first - $second;
    });
    if ($sorted !== $mappings) {
      $responsive_image_style->removeImageStyleMappings();
      foreach ($sorted as $mapping) {
        $responsive_image_style->addImageStyleMapping($mapping['breakpoint_id'], $mapping['multiplier'], $mapping);
      }
      $changed = TRUE;
    }

    return $changed;
  }

}
