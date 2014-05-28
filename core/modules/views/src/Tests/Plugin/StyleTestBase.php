<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\StyleTestBase.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests some general style plugin related functionality.
 */
abstract class StyleTestBase extends ViewUnitTestBase {

  /**
   * Stores the SimpleXML representation of the output.
   *
   * @var SimpleXMLElement
   */
  protected $elements;

  /**
   * Stores a view output in the elements.
   */
  function storeViewPreview($output) {
    $htmlDom = new \DOMDocument();
    @$htmlDom->loadHTML($output);
    if ($htmlDom) {
      // It's much easier to work with simplexml than DOM, luckily enough
      // we can just simply import our DOM tree.
      $this->elements = simplexml_import_dom($htmlDom);
    }
  }

}
