<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests row render caching.
 *
 * @group views
 */
class RowRenderCacheTest extends ViewsKernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'node');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_row_render_cache');

  /**
   * An editor user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * A power user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $powerUser;

  /**
   * A regular user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    parent::setUpFixtures();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');

    $type = NodeType::create(['type' => 'test']);
    $type->save();

    $this->editorUser = $this->createUser(['bypass node access']);
    $this->powerUser = $this->createUser(['access content', 'create test content', 'edit own test content', 'delete own test content']);
    $this->regularUser = $this->createUser(['access content']);

    // Create some test entities.
    for ($i = 0; $i < 5; $i++) {
      Node::create(['title' => 'b' . $i . $this->randomMachineName(), 'type' => 'test'])->save();
    }

    // Create a power user node.
    Node::create(['title' => 'z' . $this->randomMachineName(), 'uid' => $this->powerUser->id(), 'type' => 'test'])->save();
  }

  /**
   * Test complex field rewriting and uncacheable field handlers.
   */
  public function testAdvancedCaching() {
    // Test that row field output is actually cached and with the proper cache
    // contexts.
    $this->doTestRenderedOutput($this->editorUser);
    $this->doTestRenderedOutput($this->editorUser, TRUE);
    $this->doTestRenderedOutput($this->powerUser);
    $this->doTestRenderedOutput($this->powerUser, TRUE);
    $this->doTestRenderedOutput($this->regularUser);
    $this->doTestRenderedOutput($this->regularUser, TRUE);

    // Alter the result set order and check that counter is still working
    // correctly.
    $this->doTestRenderedOutput($this->editorUser);
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load(6);
    $node->setTitle('a' . $this->randomMachineName());
    $node->save();
    $this->doTestRenderedOutput($this->editorUser);
  }

  /**
   * Check whether the rendered output matches expectations.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to tests rendering with.
   * @param bool $check_cache
   *   (optional) Whether explicitly test render cache entries.
   */
  protected function doTestRenderedOutput(AccountInterface $account, $check_cache = FALSE) {
    $this->setCurrentUser($account);
    $view = Views::getView('test_row_render_cache');
    $view->setDisplay();
    $view->preview();

    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = $this->container->get('render_cache');

    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache_plugin */
    $cache_plugin = $view->display_handler->getPlugin('cache');

    // Retrieve nodes and sort them in alphabetical order to match view results.
    $nodes = Node::loadMultiple();
    usort($nodes, function (NodeInterface $a, NodeInterface $b) {
      return strcmp($a->label(), $b->label());
    });

    $index = 0;
    foreach ($nodes as $node) {
      $nid = $node->id();
      $access = $node->access('update');

      $counter = $index + 1;
      $expected = "$nid: $counter (just in case: $nid)";
      $counter_output = $view->style_plugin->getField($index, 'counter');
      $this->assertEqual($counter_output, $expected);

      $node_url = $node->url();
      $expected = "<a href=\"$node_url\"><span class=\"da-title\">{$node->label()}</span> <span class=\"counter\">$counter_output</span></a>";
      $output = $view->style_plugin->getField($index, 'title');
      $this->assertEqual($output, $expected);

      $expected = $access ? "<a href=\"$node_url/edit?destination=/\" hreflang=\"en\">edit</a>" : "";
      $output = $view->style_plugin->getField($index, 'edit_node');
      $this->assertEqual($output, $expected);

      $expected = $access ? "<a href=\"$node_url/delete?destination=/\" hreflang=\"en\">delete</a>" : "";
      $output = $view->style_plugin->getField($index, 'delete_node');
      $this->assertEqual($output, $expected);
      $expected = $access ? '  <div class="dropbutton-wrapper"><div class="dropbutton-widget"><ul class="dropbutton">' .
        '<li><a href="' . $node_url . '/edit?destination=/" hreflang="en">Edit</a></li>' .
        '<li><a href="' . $node_url . '/delete?destination=/" hreflang="en">Delete</a></li>' .
        '</ul></div></div>' : '';
      $output = $view->style_plugin->getField($index, 'operations');
      $this->assertEqual($output, $expected);

      if ($check_cache) {
        $keys = $cache_plugin->getRowCacheKeys($view->result[$index]);
        $user_context = !$account->hasPermission('edit any test content') ? 'user' : 'user.permissions';
        $element = $render_cache->get(['#cache' => ['keys' => $keys, 'contexts' => ['languages:language_interface', 'theme', $user_context]]]);
        $this->assertTrue($element);
      }

      $index++;
    }
  }

}
