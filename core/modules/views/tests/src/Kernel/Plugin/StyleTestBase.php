<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests some general style plugin related functionality.
 */
abstract class StyleTestBase extends ViewsKernelTestBase {

  /**
   * Stores a view output in the elements.
   */
  public function storeViewPreview($output): void {
    $htmlDom = Html::load($output);
    if ($htmlDom) {
      // It's much easier to work with simplexml than DOM, luckily enough
      // we can just simply import our DOM tree.
      $this->elements = simplexml_import_dom($htmlDom);
    }
  }

}
