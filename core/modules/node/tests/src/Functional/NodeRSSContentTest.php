<?php

namespace Drupal\Tests\node\Functional;

use Drupal\filter\Entity\FilterFormat;

/**
 * Ensures that data added to nodes by other modules appears in RSS feeds.
 *
 * Create a node, enable the node_test module to ensure that extra data is
 * added to the node's renderable array, then verify that the data appears on
 * the site-wide RSS feed at rss.xml.
 *
 * @group node
 */
class NodeRSSContentTest extends NodeTestBase {

  /**
   * Enable a module that implements hook_node_view().
   *
   * @var array
   */
  protected static $modules = ['node_test', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use bypass node access permission here, because the test class uses
    // hook_grants_alter() to deny access to everyone on node_access
    // queries.
    $user = $this->drupalCreateUser([
      'bypass node access',
      'access content',
      'create article content',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Ensures that a new node includes the custom data when added to an RSS feed.
   */
  public function testNodeRSSContent() {
    // Create a node.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);

    $this->drupalGet('rss.xml');

    // Check that content added in 'rss' view mode appear in RSS feed.
    $rss_only_content = 'Extra data that should appear only in the RSS feed for node ' . $node->id() . '.';
    $this->assertSession()->responseContains($rss_only_content);

    // Check that content added in view modes other than 'rss' doesn't
    // appear in RSS feed.
    $non_rss_content = 'Extra data that should appear everywhere except the RSS feed for node ' . $node->id() . '.';
    $this->assertSession()->responseNotContains($non_rss_content);

    // Check that extra RSS elements and namespaces are added to RSS feed.
    $test_element = "<testElement>Value of testElement RSS element for node {$node->id()}.</testElement>";
    $test_ns = 'xmlns:drupaltest="http://example.com/test-namespace"';
    $this->assertSession()->responseContains($test_element);
    $this->assertSession()->responseContains($test_ns);

    // Check that content added in 'rss' view mode doesn't appear when
    // viewing node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains($rss_only_content);
  }

  /**
   * Tests relative, root-relative, protocol-relative and absolute URLs.
   */
  public function testUrlHandling() {
    // Only the plain_text text format is available by default, which escapes
    // all HTML.
    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'filters' => [],
    ])->save();

    $defaults = [
      'type' => 'article',
      'promote' => 1,
    ];
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $this->drupalCreateNode($defaults + [
      'body' => [
        'value' => '<p><a href="' . $file_url_generator->generateString('public://root-relative') . '">Root-relative URL</a></p>',
        'format' => 'full_html',
      ],
    ]);
    $protocol_relative_url = substr($file_url_generator->generateAbsoluteString('public://protocol-relative'), strlen(\Drupal::request()->getScheme() . ':'));
    $this->drupalCreateNode($defaults + [
      'body' => [
        'value' => '<p><a href="' . $protocol_relative_url . '">Protocol-relative URL</a></p>',
        'format' => 'full_html',
      ],
    ]);
    $absolute_url = $file_url_generator->generateAbsoluteString('public://absolute');
    $this->drupalCreateNode($defaults + [
      'body' => [
        'value' => '<p><a href="' . $absolute_url . '">Absolute URL</a></p>',
        'format' => 'full_html',
      ],
    ]);

    $this->drupalGet('rss.xml');
    // Verify that root-relative URL is transformed to absolute.
    $this->assertSession()->responseContains($file_url_generator->generateAbsoluteString('public://root-relative'));
    // Verify that protocol-relative URL is left untouched.
    $this->assertSession()->responseContains($protocol_relative_url);
    // Verify that absolute URL is left untouched.
    $this->assertSession()->responseContains($absolute_url);
  }

}
