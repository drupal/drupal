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
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $node = $this->drupalCreateNode();

    $this->drupalGet($node->toUrl());

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]->getAttribute('href'), $node->toUrl()->setAbsolute()->toString());

    // Link relations are checked for access for anonymous users.
    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertFalse($result, 'Version history not present for anonymous users without access.');

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertFalse($result, 'Edit form not present for anonymous users without access.');

    $this->drupalLogin($this->createUser(['access content']));
    $this->drupalGet($node->toUrl());

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]->getAttribute('href'), $node->toUrl()->setAbsolute()->toString());

    // Link relations are present regardless of access for authenticated users.
    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEqual($result[0]->getAttribute('href'), $node->toUrl('version-history')->setAbsolute()->toString());

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($result[0]->getAttribute('href'), $node->toUrl('edit-form')->setAbsolute()->toString());

    // Give anonymous users access to edit the node. Do this through the UI to
    // ensure caches are handled properly.
    $this->drupalLogin($this->rootUser);
    $edit = [
      'anonymous[edit own ' . $node->bundle() . ' content]' => TRUE,
    ];
    $this->drupalPostForm('admin/people/permissions', $edit, 'Save permissions');
    $this->drupalLogout();

    // Anonymous user's should now see the edit-form link but not the
    // version-history link.
    $this->drupalGet($node->toUrl());
    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]->getAttribute('href'), $node->toUrl()->setAbsolute()->toString());

    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertFalse($result, 'Version history not present for anonymous users without access.');

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($result[0]->getAttribute('href'), $node->toUrl('edit-form')->setAbsolute()->toString());
  }

  /**
   * Tests the Link header.
   */
  public function testLinkHeader() {
    $node = $this->drupalCreateNode();

    $expected = [
      '<' . Html::escape($node->toUrl('canonical')->setAbsolute()->toString()) . '>; rel="canonical"',
      '<' . Html::escape($node->toUrl('canonical')->setAbsolute()->toString(), ['alias' => TRUE]) . '>; rel="shortlink"',
      '<' . Html::escape($node->toUrl('revision')->setAbsolute()->toString()) . '>; rel="revision"',
    ];

    $this->drupalGet($node->toUrl());

    $links = $this->getSession()->getResponseHeaders()['Link'];
    $this->assertEqual($links, $expected);
  }

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  public function testMultiByteUtf8() {
    $title = '🐝';
    $this->assertTrue(mb_strlen($title, 'utf-8') < strlen($title), 'Title has multi-byte characters.');
    $node = $this->drupalCreateNode(['title' => $title]);
    $this->drupalGet($node->toUrl());
    $result = $this->xpath('//span[contains(@class, "field--name-title")]');
    $this->assertEqual($result[0]->getText(), $title, 'The passed title was returned.');
  }

}
