<?php

namespace Drupal\views\Tests;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests search integration filters.
 *
 * @group views
 */
class SearchIntegrationTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'search');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_search');

  /**
   * Tests search integration.
   */
  public function testSearchIntegration() {
    // Create a content type.
    $type = $this->drupalCreateContentType();

    // Add three nodes, one containing the word "pizza", one containing
    // "sandwich", and one containing "cola is good with pizza". Make the
    // second node link to the first.
    $node['title'] = 'pizza';
    $node['body'] = array(array('value' => 'pizza'));
    $node['type'] = $type->id();
    $this->drupalCreateNode($node);

    $this->drupalGet('node/1');
    $node_url = $this->getUrl();

    $node['title'] = 'sandwich';
    $node['body'] = array(array('value' => 'sandwich with a <a href="' . $node_url . '">link to first node</a>'));
    $this->drupalCreateNode($node);

    $node['title'] = 'cola';
    $node['body'] = array(array('value' => 'cola is good with pizza'));
    $node['type'] = $type->id();
    $this->drupalCreateNode($node);

    // Run cron so that the search index tables are updated.
    $this->cronRun();

    // Test the various views filters by visiting their pages.
    // These are in the test view 'test_search', and they just display the
    // titles of the nodes in the result, as links.

    // Page with a keyword filter of 'pizza'.
    $this->drupalGet('test-filter');
    $this->assertLink('pizza');
    $this->assertNoLink('sandwich');
    $this->assertLink('cola');

    // Page with a keyword argument, various argument values.
    // Verify that the correct nodes are shown, and only once.
    $this->drupalGet('test-arg/pizza');
    $this->assertOneLink('pizza');
    $this->assertNoLink('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/sandwich');
    $this->assertNoLink('pizza');
    $this->assertOneLink('sandwich');
    $this->assertNoLink('cola');

    $this->drupalGet('test-arg/pizza OR sandwich');
    $this->assertOneLink('pizza');
    $this->assertOneLink('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/pizza sandwich OR cola');
    $this->assertNoLink('pizza');
    $this->assertNoLink('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/cola pizza');
    $this->assertNoLink('pizza');
    $this->assertNoLink('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/"cola is good"');
    $this->assertNoLink('pizza');
    $this->assertNoLink('sandwich');
    $this->assertOneLink('cola');

    // Test sorting.
    $node = [
      'title' => "Drupal's search rocks.",
      'type' => $type->id(),
    ];
    $this->drupalCreateNode($node);
    $node['title'] = "Drupal's search rocks <em>really</em> rocks!";
    $this->drupalCreateNode($node);
    $this->cronRun();
    $this->drupalGet('test-arg/rocks');
    $xpath = '//div[@class="views-row"]//a';
    /** @var \SimpleXMLElement[] $results */
    $results = $this->xpath($xpath);
    $this->assertEqual((string) $results[0], "Drupal's search rocks <em>really</em> rocks!");
    $this->assertEqual((string) $results[1], "Drupal's search rocks.");
    $this->assertEscaped("Drupal's search rocks <em>really</em> rocks!");

    // Test sorting with another set of titles.
    $node = [
      'title' => "Testing one two two two",
      'type' => $type->id(),
    ];
    $this->drupalCreateNode($node);
    $node['title'] = "Testing one one one";
    $this->drupalCreateNode($node);
    $this->cronRun();
    $this->drupalGet('test-arg/one');
    $xpath = '//div[@class="views-row"]//a';
    /** @var \SimpleXMLElement[] $results */
    $results = $this->xpath($xpath);
    $this->assertEqual((string) $results[0], "Testing one one one");
    $this->assertEqual((string) $results[1], "Testing one two two two");
  }

  /**
   * Asserts that exactly one link exists with the given text.
   *
   * @param string $label
   *   Link label to assert.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertOneLink($label) {
    $links = $this->xpath('//a[normalize-space(text())=:label]', array(':label' => $label));
    $message = SafeMarkup::format('Link with label %label found once.', array('%label' => $label));
    return $this->assert(isset($links[0]) && !isset($links[1]), $message);
  }

}
