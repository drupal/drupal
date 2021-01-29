<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests search integration filters.
 *
 * @group views
 */
class SearchIntegrationTest extends ViewTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_search'];

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
    $node['body'] = [['value' => 'pizza']];
    $node['type'] = $type->id();
    $this->drupalCreateNode($node);

    $this->drupalGet('node/1');
    $node_url = $this->getUrl();

    $node['title'] = 'sandwich';
    $node['body'] = [['value' => 'sandwich with a <a href="' . $node_url . '">link to first node</a>']];
    $this->drupalCreateNode($node);

    $node['title'] = 'cola';
    $node['body'] = [['value' => 'cola is good with pizza']];
    $node['type'] = $type->id();
    $this->drupalCreateNode($node);

    // Run cron so that the search index tables are updated.
    $this->cronRun();

    // Test the various views filters by visiting their pages.
    // These are in the test view 'test_search', and they just display the
    // titles of the nodes in the result, as links.

    // Page with a keyword filter of 'pizza'.
    $this->drupalGet('test-filter');
    $this->assertSession()->linkExists('pizza');
    $this->assertSession()->linkNotExists('sandwich');
    $this->assertSession()->linkExists('cola');

    // Page with a keyword argument, various argument values.
    // Verify that the correct nodes are shown, and only once.
    $this->drupalGet('test-arg/pizza');
    $this->assertOneLink('pizza');
    $this->assertSession()->linkNotExists('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/sandwich');
    $this->assertSession()->linkNotExists('pizza');
    $this->assertOneLink('sandwich');
    $this->assertSession()->linkNotExists('cola');

    $this->drupalGet('test-arg/pizza OR sandwich');
    $this->assertOneLink('pizza');
    $this->assertOneLink('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/pizza sandwich OR cola');
    $this->assertSession()->linkNotExists('pizza');
    $this->assertSession()->linkNotExists('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/cola pizza');
    $this->assertSession()->linkNotExists('pizza');
    $this->assertSession()->linkNotExists('sandwich');
    $this->assertOneLink('cola');

    $this->drupalGet('test-arg/"cola is good"');
    $this->assertSession()->linkNotExists('pizza');
    $this->assertSession()->linkNotExists('sandwich');
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
    /** @var \Behat\Mink\Element\NodeElement[] $results */
    $results = $this->xpath($xpath);
    $this->assertEqual("Drupal's search rocks <em>really</em> rocks!", $results[0]->getText());
    $this->assertEqual("Drupal's search rocks.", $results[1]->getText());
    $this->assertSession()->assertEscaped("Drupal's search rocks <em>really</em> rocks!");

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
    $this->assertEqual("Testing one one one", $results[0]->getText());
    $this->assertEqual("Testing one two two two", $results[1]->getText());
  }

  /**
   * Asserts that exactly one link exists with the given text.
   *
   * @param string $label
   *   Link label to assert.
   *
   * @return bool
   *   TRUE if the assertion succeeded.
   */
  protected function assertOneLink($label) {
    $xpath = $this->assertSession()->buildXPathQuery('//a[normalize-space(text())=:label]', [
      ':label' => $label,
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
    return TRUE;
  }

}
