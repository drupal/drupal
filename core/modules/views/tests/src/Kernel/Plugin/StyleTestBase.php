<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Masterminds\HTML5;

/**
 * Tests some general style plugin related functionality.
 */
abstract class StyleTestBase extends ViewsKernelTestBase {

  /**
   * Stores the SimpleXML representation of the output.
   *
   * @var \SimpleXMLElement
   */
  protected $elements;

  /**
   * Stores a view output in the elements.
   */
  function storeViewPreview($output) {
    $html5 = new HTML5();
    $htmlDom = $html5->loadHTML('<html><body>' . $output . '</body></html>');
    if ($htmlDom) {
      // It's much easier to work with simplexml than DOM, luckily enough
      // we can just simply import our DOM tree.
      $this->elements = simplexml_import_dom($htmlDom);
    }
  }

}
