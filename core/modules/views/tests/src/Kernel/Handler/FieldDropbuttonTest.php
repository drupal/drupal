<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Render\RenderContext;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Dropbutton handler.
 *
 * @group views
 */
class FieldDropbuttonTest extends ViewsKernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_dropbutton'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'views',
  ];

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node3;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installConfig('node');
    $this->installConfig('filter');

    ViewTestData::createTestViews(static::class, ['views_test_config']);
    // Create two node types.
    $this->createContentType(['type' => 'foo']);
    $this->createContentType(['type' => 'bar']);

    // Create user 1.
    $admin = $this->createUser();

    // And three nodes.
    $this->node1 = $this->createNode([
      'type' => 'bar',
      'title' => 'bazs',
      'status' => 1,
      'uid' => $admin->id(),
      'created' => REQUEST_TIME - 10,
    ]);
    $this->node2 = $this->createNode([
      'type' => 'foo',
      'title' => 'foo',
      'status' => 1,
      'uid' => $admin->id(),
      'created' => REQUEST_TIME - 5,
    ]);
    $this->node3 = $this->createNode([
      'type' => 'bar',
      'title' => 'bars',
      'status' => 1,
      'uid' => $admin->id(),
      'created' => REQUEST_TIME,
    ]);

    // Now create a user with the ability to edit bar but not foo.
    $this->testUser = $this->createUser([
      'access content overview',
      'access content',
      'edit any bar content',
      'delete any bar content',
    ]);
    // And switch to that user.
    $this->container->get('account_switcher')->switchTo($this->testUser);
  }

  /**
   * Tests that dropbutton markup doesn't leak between rows.
   */
  public function testDropbuttonMarkupShouldNotLeakBetweenRows() {
    $view = Views::getView('test_dropbutton');
    $view->setDisplay();
    $view->preExecute([]);
    $view->execute();

    $renderer = $this->container->get('renderer');
    $dropbutton_output = [];

    // Render each row and field in turn - the dropbutton plugin relies on
    // output being set in previous versions.
    foreach ($view->result as $index => $row) {
      foreach (array_keys($view->field) as $field) {
        $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $row, $field) {
          return $view->field[$field]->advancedRender($row);
        });
        if ($field === 'dropbutton') {
          $dropbutton_output[] = $output;
        }
      }
    }

    // The first row should contain edit links to node 3, as the user has
    // access.
    $this->assertStringContainsString($this->node3->toUrl('edit-form')->toString(), (string) $dropbutton_output[0]);
    $this->assertStringContainsString($this->node3->toUrl('delete-form')->toString(), (string) $dropbutton_output[0]);

    // Second row should be not contain links to edit/delete any content as user
    // has no edit/delete permissions.
    // It most certainly should not contain links to node 3, as node 2 is the
    // entity that forms this row.
    $this->assertStringNotContainsString($this->node3->toUrl('edit-form')->toString(), (string) $dropbutton_output[1]);
    $this->assertStringNotContainsString($this->node3->toUrl('delete-form')->toString(), (string) $dropbutton_output[1]);
    $this->assertStringNotContainsString($this->node2->toUrl('edit-form')->toString(), (string) $dropbutton_output[1]);
    $this->assertStringNotContainsString($this->node2->toUrl('delete-form')->toString(), (string) $dropbutton_output[1]);

    // Third row should contain links for node 1.
    $this->assertStringContainsString($this->node1->toUrl('edit-form')->toString(), (string) $dropbutton_output[2]);
    $this->assertStringContainsString($this->node1->toUrl('delete-form')->toString(), (string) $dropbutton_output[2]);
  }

}
