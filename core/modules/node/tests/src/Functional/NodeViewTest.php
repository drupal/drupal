<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Component\Utility\Html;

/**
 * Tests the node/{node} page.
 *
 * @group node
 * @see \Drupal\node\Controller\NodeController
 */
class NodeViewTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $node = $this->drupalCreateNode();

    $this->drupalGet($node->toUrl());

    $element = $this->assertSession()->elementExists('css', 'link[rel="canonical"]');
    $this->assertEquals($node->toUrl()->setAbsolute()->toString(), $element->getAttribute('href'));

    $element = $this->assertSession()->elementExists('css', 'link[rel="shortlink"]');
    $this->assertEquals($node->toUrl('canonical', ['alias' => TRUE])->setAbsolute()->toString(), $element->getAttribute('href'));
  }

  /**
   * Tests the Link header.
   */
  public function testLinkHeader() {
    $node = $this->drupalCreateNode();

    $expected = [
      '<' . Html::escape($node->toUrl('canonical')->setAbsolute()->toString()) . '>; rel="canonical"',
      '<' . Html::escape($node->toUrl('canonical', ['alias' => TRUE])->setAbsolute()->toString()) . '>; rel="shortlink"',
    ];

    $this->drupalGet($node->toUrl());

    $links = $this->getSession()->getResponseHeaders()['Link'];
    $this->assertEquals($expected, $links);
  }

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  public function testMultiByteUtf8() {
    $title = '🐝';
    // To ensure that the title has multi-byte characters, we compare the byte
    // length to the character length.
    $this->assertLessThan(strlen($title), mb_strlen($title, 'utf-8'));
    $node = $this->drupalCreateNode(['title' => $title]);
    $this->drupalGet($node->toUrl());
    // Verify that the passed title was returned.
    $this->assertSession()->elementTextEquals('xpath', '//h1/span', $title);
  }

}
