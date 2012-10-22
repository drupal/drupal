<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\StyleTestBase.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests some general style plugin related functionality.
 */
abstract class StyleTestBase extends PluginTestBase {

  /**
   * Stores the SimpleXML representation of the output.
   *
   * @var SimpleXMLElement
   */
  protected $elements;

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

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
