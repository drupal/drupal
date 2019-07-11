<?php

namespace Drupal\Tests\node\Functional\Views;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\AssertViewsCacheTagsTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the default frontpage provided by views.
 *
 * @group node
 */
class FrontPageTest extends ViewTestBase {

  use AssertViewsCacheTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * The entity storage for nodes.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
  }

  /**
   * Tests the frontpage.
   */
  public function testFrontPage() {
    $site_name = $this->randomMachineName();
    $this->config('system.site')
      ->set('name', $site_name)
      ->save();

    $view = Views::getView('frontpage');

    // Tests \Drupal\node\Plugin\views\row\RssPluginBase::calculateDependencies().
    $expected = [
      'config' => [
        'core.entity_view_mode.node.rss',
        'core.entity_view_mode.node.teaser',
      ],
      'module' => [
        'node',
        'user',
      ],
    ];
    $this->assertIdentical($expected, $view->getDependencies());

    $view->setDisplay('page_1');
    $this->executeView($view);
    $view->preview();

    $this->assertEqual($view->getTitle(), new FormattableMarkup('Welcome to @site_name', ['@site_name' => $site_name]), 'The welcome title is used for the empty view.');
    $view->destroy();

    // Create some nodes on the frontpage view. Add more than 10 nodes in order
    // to enable paging.
    $expected = [];
    for ($i = 0; $i < 20; $i++) {
      $values = [];
      $values['type'] = 'article';
      $values['title'] = $this->randomMachineName();
      $values['promote'] = TRUE;
      $values['status'] = TRUE;
      // Test descending sort order.
      $values['created'] = REQUEST_TIME - $i;
      // Test the sticky order.
      if ($i == 5) {
        $values['sticky'] = TRUE;
        $node = $this->nodeStorage->create($values);
        $node->save();
        // Put the sticky on at the front.
        array_unshift($expected, ['nid' => $node->id()]);
      }
      else {
        $values['sticky'] = FALSE;
        $node = $this->nodeStorage->create($values);
        $node->save();
        array_push($expected, ['nid' => $node->id()]);
      }
    }

    // Create some nodes which aren't on the frontpage, either because they
    // aren't promoted or because they aren't published.
    $not_expected_nids = [];

    $values = [];
    $values['type'] = 'article';
    $values['title'] = $this->randomMachineName();
    $values['status'] = TRUE;
    $values['promote'] = FALSE;
    $node = $this->nodeStorage->create($values);
    $node->save();
    $not_expected_nids[] = $node->id();

    $values['promote'] = TRUE;
    $values['status'] = FALSE;
    $values['title'] = $this->randomMachineName();
    $node = $this->nodeStorage->create($values);
    $node->save();
    $not_expected_nids[] = $node->id();

    $values['promote'] = TRUE;
    $values['sticky'] = TRUE;
    $values['status'] = FALSE;
    $values['title'] = $this->randomMachineName();
    $node = $this->nodeStorage->create($values);
    $node->save();
    $not_expected_nids[] = $node->id();

    $column_map = ['nid' => 'nid'];

    $view->setDisplay('page_1');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, array_slice($expected, 0, 10), $column_map, 'Ensure that the right nodes are displayed on the frontpage.');
    $this->assertNotInResultSet($view, $not_expected_nids, 'Ensure no unexpected node is in the result.');
    $view->destroy();

    $view->setDisplay('page_1');
    $view->setCurrentPage(1);
    $this->executeView($view);
    $this->assertIdenticalResultset($view, array_slice($expected, 10, 10), $column_map, 'Ensure that the right nodes are displayed on second page of the frontpage.');
    $this->assertNotInResultSet($view, $not_expected_nids, 'Ensure no unexpected node is in the result.');
    $view->destroy();
  }

  /**
   * Verifies that an amount of nids aren't in the result.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed View.
   * @param array $not_expected_nids
   *   An array of nids which should not be part of the resultset.
   * @param string $message
   *   (optional) A custom message to display with the assertion.
   */
  protected function assertNotInResultSet(ViewExecutable $view, array $not_expected_nids, $message = '') {
    $found_nids = array_filter($view->result, function ($row) use ($not_expected_nids) {
      return in_array($row->nid, $not_expected_nids);
    });
    $this->assertFalse($found_nids, $message);
  }

  /**
   * Tests the frontpage when logged in as admin.
   */
  public function testAdminFrontPage() {
    // When a user with sufficient permissions is logged in, views_ui adds
    // contextual links to the homepage view. This verifies there are no errors.
    \Drupal::service('module_installer')->install(['views_ui']);
    // Log in root user with sufficient permissions.
    $this->drupalLogin($this->rootUser);
    // Test frontpage view.
    $this->drupalGet('node');
    $this->assertResponse(200);
    // Check that the frontpage view was rendered.
    $this->assertPattern('/class=".+view-frontpage/', 'Frontpage view was rendered');
  }

  /**
   * Tests the cache tags when using the "none" cache plugin.
   */
  public function testCacheTagsWithCachePluginNone() {
    $this->enablePageCaching();
    $this->doTestFrontPageViewCacheTags(FALSE);
  }

  /**
   * Tests the cache tags when using the "tag" cache plugin.
   */
  public function testCacheTagsWithCachePluginTag() {
    $this->enablePageCaching();

    $view = Views::getView('frontpage');
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'tag',
    ]);
    $view->save();

    $this->doTestFrontPageViewCacheTags(TRUE);
  }

  /**
   * Tests the cache tags when using the "time" cache plugin.
   */
  public function testCacheTagsWithCachePluginTime() {
    $this->enablePageCaching();

    $view = Views::getView('frontpage');
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => 3600,
        'output_lifespan' => 3600,
      ],
    ]);
    $view->save();

    $this->doTestFrontPageViewCacheTags(TRUE);
  }

  /**
   * Tests the cache tags on the front page.
   *
   * @param bool $do_assert_views_caches
   *   Whether to check Views' result & output caches.
   */
  protected function doTestFrontPageViewCacheTags($do_assert_views_caches) {
    $view = Views::getView('frontpage');
    $view->setDisplay('page_1');

    $cache_contexts = [
      // Cache contexts associated with the view.
      'user.node_grants:view',
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      // Cache contexts associated with the route's access checking.
      'user.permissions',
      // Default cache contexts of the renderer.
      'theme',
      'url.query_args',
      // Attached feed.
      'url.site',
    ];

    $cache_context_tags = \Drupal::service('cache_contexts_manager')->convertTokensToKeys($cache_contexts)->getCacheTags();

    // Test before there are any nodes.
    $empty_node_listing_cache_tags = [
      'config:views.view.frontpage',
      'node_list',
    ];

    $render_cache_tags = Cache::mergeTags($empty_node_listing_cache_tags, $cache_context_tags);
    $render_cache_tags = Cache::mergeTags($render_cache_tags, ['config:system.site']);
    $this->assertViewsCacheTags(
      $view,
      $empty_node_listing_cache_tags,
      $do_assert_views_caches,
      $render_cache_tags
    );
    $expected_tags = Cache::mergeTags($empty_node_listing_cache_tags, $cache_context_tags);
    $expected_tags = Cache::mergeTags($expected_tags, ['http_response', 'rendered', 'config:user.role.anonymous', 'config:system.site']);
    $this->assertPageCacheContextsAndTags(
      Url::fromRoute('view.frontpage.page_1'),
      $cache_contexts,
      $expected_tags
    );

    // Create some nodes on the frontpage view. Add more than 10 nodes in order
    // to enable paging.
    $this->drupalCreateContentType(['type' => 'article']);
    for ($i = 0; $i < 15; $i++) {
      $node = Node::create([
        'body' => [
          [
            'value' => $this->randomMachineName(32),
            'format' => filter_default_format(),
          ],
        ],
        'type' => 'article',
        'created' => $i,
        'title' => $this->randomMachineName(8),
        'nid' => $i + 1,
      ]);
      $node->enforceIsNew(TRUE);
      $node->save();
    }
    $cache_contexts = Cache::mergeContexts($cache_contexts, [
      'timezone',
    ]);

    $this->pass('First page');
    // First page.
    $first_page_result_cache_tags = [
      'config:views.view.frontpage',
      'node_list',
      'node:6',
      'node:7',
      'node:8',
      'node:9',
      'node:10',
      'node:11',
      'node:12',
      'node:13',
      'node:14',
      'node:15',
    ];
    $cache_context_tags = \Drupal::service('cache_contexts_manager')->convertTokensToKeys($cache_contexts)->getCacheTags();
    $first_page_output_cache_tags = Cache::mergeTags($first_page_result_cache_tags, $cache_context_tags);
    $first_page_output_cache_tags = Cache::mergeTags($first_page_output_cache_tags, [
        'config:filter.format.plain_text',
        'node_view',
        'user_view',
        'user:0',
      ]
    );
    $view->setDisplay('page_1');
    $view->setCurrentPage(0);
    $this->assertViewsCacheTags(
      $view,
      $first_page_result_cache_tags,
      $do_assert_views_caches,
      $first_page_output_cache_tags
    );
    $this->assertPageCacheContextsAndTags(
      Url::fromRoute('view.frontpage.page_1'),
      $cache_contexts,
      Cache::mergeTags($first_page_output_cache_tags, ['http_response', 'rendered', 'config:user.role.anonymous'])
    );

    // Second page.
    $this->pass('Second page');
    $this->assertPageCacheContextsAndTags(Url::fromRoute('view.frontpage.page_1', [], ['query' => ['page' => 1]]), $cache_contexts, [
      // The cache tags for the listed nodes.
      'node:1',
      'node:2',
      'node:3',
      'node:4',
      'node:5',
      // The rest.
      'config:filter.format.plain_text',
      'config:views.view.frontpage',
      'node_list',
      'node_view',
      'user_view',
      'user:0',
      'http_response',
      'rendered',
      // FinishResponseSubscriber adds this cache tag to responses that have the
      // 'user.permissions' cache context for anonymous users.
      'config:user.role.anonymous',
    ]);

    // Let's update a node title on the first page and ensure that the page
    // cache entry invalidates.
    $node = Node::load(10);
    $title = $node->getTitle() . 'a';
    $node->setTitle($title);
    $node->save();

    $this->drupalGet(Url::fromRoute('view.frontpage.page_1'));
    $this->assertText($title);
  }

}
