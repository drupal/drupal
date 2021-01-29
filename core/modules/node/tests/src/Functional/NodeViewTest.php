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
  protected $defaultTheme = 'classy';

  /**
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $node = $this->drupalCreateNode();

    $this->drupalGet($node->toUrl());

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($node->toUrl()->setAbsolute()->toString(), $result[0]->getAttribute('href'));

    // Link relations are checked for access for anonymous users.
    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEmpty($result, 'Version history not present for anonymous users without access.');

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEmpty($result, 'Edit form not present for anonymous users without access.');

    $this->drupalLogin($this->createUser(['access content']));
    $this->drupalGet($node->toUrl());

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($node->toUrl()->setAbsolute()->toString(), $result[0]->getAttribute('href'));

    // Link relations are present regardless of access for authenticated users.
    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEqual($node->toUrl('version-history')->setAbsolute()->toString(), $result[0]->getAttribute('href'));

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($node->toUrl('edit-form')->setAbsolute()->toString(), $result[0]->getAttribute('href'));

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
    $this->assertEqual($node->toUrl()->setAbsolute()->toString(), $result[0]->getAttribute('href'));

    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEmpty($result, 'Version history not present for anonymous users without access.');

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($node->toUrl('edit-form')->setAbsolute()->toString(), $result[0]->getAttribute('href'));
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
    $this->assertEqual($expected, $links);
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
    $result = $this->xpath('//span[contains(@class, "field--name-title")]');
    $this->assertEqual($title, $result[0]->getText(), 'The passed title was returned.');
  }

}
