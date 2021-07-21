<?php

namespace Drupal\image_style_filter_test;

use Drupal\image\Plugin\Filter\FilterImageStyle;

/**
 * Replacement class for 'filter_image_style' plugin.
 *
 * Overrides parent::onDependencyRemoval() and omits to resolve the 'style2'
 * dependency.
 */
class FilterTestImageStyle extends FilterImageStyle {

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = FALSE;
    if ($this->settings['allowed_styles']) {
      foreach ($this->getAllowedImageStyles() as $image_style_id => $image_style) {
        // Unlike the parent method, we intentionally don't resolve the 'style2'
        // dependency to test the case when there are still unresolved
        // dependencies left after plugins got the chance to act on removal.
        // @see \Drupal\Tests\image\Kernel\FilterDependencyTest::testDependencyRemoval()
        if ($image_style_id !== 'style2') {
          if (isset($dependencies[$image_style->getConfigDependencyKey()][$image_style->getConfigDependencyName()])) {
            unset($this->settings['allowed_styles'][array_search($image_style_id, $this->settings['allowed_styles'])]);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

}
