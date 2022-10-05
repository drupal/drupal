<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\argument_default\ArgumentDefaultTest as ArgumentDefaultTestPlugin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests pluggable argument_default for views.
 *
 * @group views
 */
class ArgumentDefaultTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_view',
    'test_argument_default_fixed',
    'test_argument_default_current_user',
    'test_argument_default_node',
    'test_argument_default_query_param',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests the argument default test plugin.
   *
   * @see \Drupal\views_test_data\Plugin\views\argument_default\ArgumentDefaultTest
   */
  public function testArgumentDefaultPlugin() {
    $view = Views::getView('test_view');

    // Add a new argument and set the test plugin for the argument_default.
    $options = [
      'default_argument_type' => 'argument_default_test',
      'default_argument_options' => [
        'value' => 'John',
      ],
      'default_action' => 'default',
    ];
    $id = $view->addHandler('default', 'argument', 'views_test_data', 'name', $options);
    $view->initHandlers();
    $plugin = $view->argument[$id]->getPlugin('argument_default');
    $this->assertInstanceOf(ArgumentDefaultTestPlugin::class, $plugin);

    // Check that the value of the default argument is as expected.
    $this->assertEquals('John', $view->argument[$id]->getDefaultArgument(), 'The correct argument default value is returned.');
    // Don't pass in a value for the default argument and make sure the query
    // just returns John.
    $this->executeView($view);
    $this->assertEquals('John', $view->argument[$id]->getValue(), 'The correct argument value is used.');
    $expected_result = [['name' => 'John']];
    $this->assertIdenticalResultset($view, $expected_result, ['views_test_data_name' => 'name']);

    // Pass in value as argument to be sure that not the default value is used.
    $view->destroy();
    $this->executeView($view, ['George']);
    $this->assertEquals('George', $view->argument[$id]->getValue(), 'The correct argument value is used.');
    $expected_result = [['name' => 'George']];
    $this->assertIdenticalResultset($view, $expected_result, ['views_test_data_name' => 'name']);
  }

  /**
   * Tests the use of a default argument plugin that provides no options.
   */
  public function testArgumentDefaultNoOptions() {
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // The current_user plugin has no options form, and should pass validation.
    $argument_type = 'current_user';
    $edit = [
      'options[default_argument_type]' => $argument_type,
    ];
    $this->drupalGet('admin/structure/views/nojs/handler/test_argument_default_current_user/default/argument/uid');
    $this->submitForm($edit, 'Apply');

    // Note, the undefined index error has two spaces after it.
    $this->assertSession()->pageTextNotContains("Notice: Undefined index:  {$argument_type} in views_handler_argument->validateOptionsForm()");
  }

  /**
   * Tests fixed default argument.
   */
  public function testArgumentDefaultFixed() {
    $random = $this->randomMachineName();
    $view = Views::getView('test_argument_default_fixed');
    $view->setDisplay();
    $options = $view->display_handler->getOption('arguments');
    $options['null']['default_argument_options']['argument'] = $random;
    $view->display_handler->overrideOption('arguments', $options);
    $view->initHandlers();

    $this->assertEquals($random, $view->argument['null']->getDefaultArgument(), 'Fixed argument should be used by default.');

    // Make sure that a normal argument provided is used
    $random_string = $this->randomMachineName();
    $view->executeDisplay('default', [$random_string]);

    $this->assertEquals($random_string, $view->args[0], 'Provided argument should be used.');
  }

  /**
   * @todo Test php default argument.
   */
  // function testArgumentDefaultPhp() {}

  /**
   * Tests node default argument.
   */
  public function testArgumentDefaultNode() {
    // Create a user that has permission to place a view block.
    $permissions = [
      'administer views',
      'administer blocks',
      'bypass node access',
      'access user profiles',
      'view all revisions',
    ];
    $views_admin = $this->drupalCreateUser($permissions);
    $this->drupalLogin($views_admin);

    // Create nodes where should show themselves again as view block.
    $node_type = NodeType::create(['type' => 'page', 'label' => 'Page']);
    $node_type->save();
    $node1 = Node::create(['title' => 'Test node 1', 'type' => 'page']);
    $node1->save();
    $node2 = Node::create(['title' => 'Test node 2', 'type' => 'page']);
    $node2->save();

    // Place the block, visit the pages that display the block, and check that
    // the nodes we expect appear in the respective pages.
    $id = 'view-block-id';
    $this->drupalPlaceBlock("views_block:test_argument_default_node-block_1", ['id' => $id]);
    $this->drupalGet('node/' . $node1->id());
    $this->assertSession()->elementTextContains('xpath', '//*[@id="block-' . $id . '"]', $node1->getTitle());
    $this->drupalGet('node/' . $node2->id());
    $this->assertSession()->elementTextContains('xpath', '//*[@id="block-' . $id . '"]', $node2->getTitle());
  }

  /**
   * Tests the query parameter default argument.
   */
  public function testArgumentDefaultQueryParameter() {
    $view = Views::getView('test_argument_default_query_param');

    $request = Request::create(Url::fromUri('internal:/whatever', ['absolute' => TRUE])->toString());

    // Check the query parameter default argument fallback value.
    $view->setRequest($request);
    $view->initHandlers();
    $this->assertEquals('all', $view->argument['type']->getDefaultArgument());

    // Check the query parameter default argument with a value.
    $request->query->add(['the_node_type' => 'page']);
    $view->setRequest($request);
    $view->initHandlers();
    $this->assertEquals('page', $view->argument['type']->getDefaultArgument());
  }

}
