<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Component\Render\FormattableMarkup;
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
  public static $modules = ['node', 'views_ui', 'block'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

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
    $this->assertEqual($view->argument[$id]->getDefaultArgument(), 'John', 'The correct argument default value is returned.');
    // Don't pass in a value for the default argument and make sure the query
    // just returns John.
    $this->executeView($view);
    $this->assertEqual($view->argument[$id]->getValue(), 'John', 'The correct argument value is used.');
    $expected_result = [['name' => 'John']];
    $this->assertIdenticalResultset($view, $expected_result, ['views_test_data_name' => 'name']);

    // Pass in value as argument to be sure that not the default value is used.
    $view->destroy();
    $this->executeView($view, ['George']);
    $this->assertEqual($view->argument[$id]->getValue(), 'George', 'The correct argument value is used.');
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
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_argument_default_current_user/default/argument/uid', $edit, t('Apply'));

    // Note, the undefined index error has two spaces after it.
    $error = [
      '%type' => 'Notice',
      '@message' => 'Undefined index:  ' . $argument_type,
      '%function' => 'views_handler_argument->validateOptionsForm()',
    ];
    $message = t('%type: @message in %function', $error);
    $this->assertNoRaw($message, new FormattableMarkup('Did not find error message: @message.', ['@message' => $message]));
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

    $this->assertEqual($view->argument['null']->getDefaultArgument(), $random, 'Fixed argument should be used by default.');

    // Make sure that a normal argument provided is used
    $random_string = $this->randomMachineName();
    $view->executeDisplay('default', [$random_string]);

    $this->assertEqual($view->args[0], $random_string, 'Provided argument should be used.');
  }

  /**
   * @todo Test php default argument.
   */
  // function testArgumentDefaultPhp() {}

  /**
   * Test node default argument.
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
    $xpath = '//*[@id="block-' . $id . '"]';
    $this->drupalGet('node/' . $node1->id());
    $this->assertStringContainsString($node1->getTitle(), $this->xpath($xpath)[0]->getText());
    $this->drupalGet('node/' . $node2->id());
    $this->assertStringContainsString($node2->getTitle(), $this->xpath($xpath)[0]->getText());
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
    $this->assertEqual($view->argument['type']->getDefaultArgument(), 'all');

    // Check the query parameter default argument with a value.
    $request->query->add(['the_node_type' => 'page']);
    $view->setRequest($request);
    $view->initHandlers();
    $this->assertEqual($view->argument['type']->getDefaultArgument(), 'page');
  }

}
