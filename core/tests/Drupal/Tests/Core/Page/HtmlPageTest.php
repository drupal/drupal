<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Page\HtmlPageTest.
 */

namespace Drupal\Tests\Core\Page;

use Drupal\Core\Page\HtmlPage;
use Drupal\Core\Page\MetaElement;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Page\HtmlPage
 * @group Page
 */
class HtmlPageTest extends UnitTestCase {

  /**
   * Ensures that a single metatags can be changed.
   */
  public function testMetatagAlterability() {
    $page = new HtmlPage();
    $page->addMetaElement(new MetaElement('', array('name' => 'example')));
    $page->addMetaElement(new MetaElement('', array('name' => 'example2')));
    $metatags = $page->getMetaElements();

    foreach ($metatags as $tag) {
      if ($tag->getName() == 'example') {
        $tag->setContent('hello');
      }
    }

    $metatags = $page->getMetaElements();
    $this->assertEquals('hello', $metatags[0]->getContent());
    $this->assertEquals('', $metatags[1]->getContent());
  }

  /**
   * Ensures that a metatag can be removed.
   */
  public function testMetatagRemovability() {
    $page = new HtmlPage();
    $page->addMetaElement(new MetaElement('', array('name' => 'example')));
    $page->addMetaElement(new MetaElement('', array('name' => 'example2')));
    $metatags =& $page->getMetaElements();

    foreach ($metatags as $key => $tag) {
      if ($tag->getName() == 'example') {
        unset($metatags[$key]);
      }
    }

    $metatags = $page->getMetaElements();
    reset($metatags);
    $this->assertCount(1, $metatags);
    $this->assertEquals('example2', current($metatags)->getName());
  }

}

