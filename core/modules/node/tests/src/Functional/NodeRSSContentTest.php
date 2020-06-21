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
    $rss_only_content = t('Extra data that should appear only in the RSS feed for node @nid.', ['@nid' => $node->id()]);
    $this->assertText($rss_only_content, 'Node content designated for RSS appear in RSS feed.');

    // Check that content added in view modes other than 'rss' doesn't
    // appear in RSS feed.
    $non_rss_content = t('Extra data that should appear everywhere except the RSS feed for node @nid.', ['@nid' => $node->id()]);
    $this->assertNoText($non_rss_content, 'Node content not designed for RSS does not appear in RSS feed.');

    // Check that extra RSS elements and namespaces are added to RSS feed.
    $test_element = '<testElement>' . t('Value of testElement RSS element for node @nid.', ['@nid' => $node->id()]) . '</testElement>';
    $test_ns = 'xmlns:drupaltest="http://example.com/test-namespace"';
    $this->assertRaw($test_element, 'Extra RSS elements appear in RSS feed.');
    $this->assertRaw($test_ns, 'Extra namespaces appear in RSS feed.');

    // Check that content added in 'rss' view mode doesn't appear when
    // viewing node.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText($rss_only_content, 'Node content designed for RSS does not appear when viewing node.');
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
    $this->drupalCreateNode($defaults + [
      'body' => [
        'value' => '<p><a href="' . file_url_transform_relative(file_create_url('public://root-relative')) . '">Root-relative URL</a></p>',
        'format' => 'full_html',
      ],
    ]);
    $protocol_relative_url = substr(file_create_url('public://protocol-relative'), strlen(\Drupal::request()->getScheme() . ':'));
    $this->drupalCreateNode($defaults + [
      'body' => [
        'value' => '<p><a href="' . $protocol_relative_url . '">Protocol-relative URL</a></p>',
        'format' => 'full_html',
      ],
    ]);
    $absolute_url = file_create_url('public://absolute');
    $this->drupalCreateNode($defaults + [
      'body' => [
        'value' => '<p><a href="' . $absolute_url . '">Absolute URL</a></p>',
        'format' => 'full_html',
      ],
    ]);

    $this->drupalGet('rss.xml');
    $this->assertRaw(file_create_url('public://root-relative'), 'Root-relative URL is transformed to absolute.');
    $this->assertRaw($protocol_relative_url, 'Protocol-relative URL is left untouched.');
    $this->assertRaw($absolute_url, 'Absolute URL is left untouched.');
  }

}
