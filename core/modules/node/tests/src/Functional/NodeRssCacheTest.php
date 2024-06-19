<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\views\Tests\ViewTestData;

/**
 * Ensures that RSS render cache doesn't interfere with other caches.
 *
 * Create a node, render that node as a teaser in the RSS feed, ensure that
 * the RSS teaser render doesn't contain tags from the default theme.
 *
 * @group node
 */
class NodeRssCacheTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_test', 'views', 'node_test_views'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_node_article_feed'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(static::class, ['node_test_views']);

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
   * Ensure the RSS teaser render does not interfere with default theme cache.
   */
  public function testNodeRssCacheContent(): void {
    // Only the plain_text text format is available by default, which escapes
    // all HTML.
    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'filters' => [],
    ])->save();

    // Create the test node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'promote' => 1,
      'title' => 'Article Test Title',
      'body' => [
        'value' => '<p>Article test text.</p>',
        'format' => 'full_html',
      ],
    ]);

    // Render the node in the RSS feed view as a teaser.
    $this->drupalGet('test-node-article-feed');

    // Render the teaser normally.
    $viewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('node');
    $build = $viewBuilder->view($node, 'teaser');
    $output = $this->container->get('renderer')->renderInIsolation($build);

    // Teaser must contain an "<article" tag from the stable9 theme.
    $this->assertStringContainsString('<article', (string) $output);
  }

}
