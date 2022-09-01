<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group rdf
 * @group legacy
 */
class RssViewIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'rdf_test_namespaces',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that RSS views have RDF's XML namespaces defined.
   */
  public function testRdfNamespacesAreAddedToRssViews(): void {
    // RDF's namespaces are only attached when there is content in the feed.
    $node_type = $this->drupalCreateContentType()->id();
    $this->drupalCreateNode(['type' => $node_type]);

    $this->drupalGet('rdf-rss-test');
    $this->assertSession()->statusCodeEquals(200);

    // Mink insists on treating the page as an HTML document, so we have to use
    // PHP's built-in DOM extension to examine the RSS feed.
    $xml = $this->getSession()->getPage()->getContent();
    $document = new \DOMDocument();
    $this->assertTrue($document->loadXML($xml));

    foreach (rdf_get_namespaces() as $prefix => $uri) {
      if ($prefix === 'dc') {
        continue;
      }
      $this->assertSame($uri, $document->lookupNamespaceURI($prefix));
    }
  }

}
