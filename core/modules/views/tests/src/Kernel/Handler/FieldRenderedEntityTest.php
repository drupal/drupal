<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\Core\Entity\Entity\EntityViewMode;

/**
 * Tests the core Drupal\views\Plugin\views\field\RenderedEntity handler.
 *
 * @group views
 */
class FieldRenderedEntityTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'field'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_entity_test_rendered'];

  /**
   * The logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['entity_test']);

    EntityViewMode::create([
      'id' => 'entity_test.foobar',
      'targetEntityType' => 'entity_test',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'My view mode',
    ])->save();

    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'foobar',
      'label' => 'My view mode',
      'status' => TRUE,
    ]);
    $display->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'string',
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field_config->save();

    // Create some test entities.
    for ($i = 1; $i <= 3; $i++) {
      EntityTest::create([
        'name' => "Article title $i",
        'test_field' => "Test $i",
      ])->save();
    }

    $this->user = User::create([
      'name' => 'test user',
    ]);
    $this->user->save();

    parent::setUpFixtures();
  }

  /**
   * Tests the default rendered entity output.
   */
  public function testRenderedEntityWithoutField() {
    \Drupal::currentUser()->setAccount($this->user);

    EntityViewDisplay::load('entity_test.entity_test.foobar')
      ->removeComponent('test_field')
      ->save();

    $view = Views::getView('test_field_entity_test_rendered');
    $build = [
      '#type' => 'view',
      '#name' => 'test_field_entity_test_rendered',
      '#view' => $view,
      '#display_id' => 'default',
    ];
    $renderer = \Drupal::service('renderer');
    $renderer->renderPlain($build);
    for ($i = 1; $i <= 3; $i++) {
      $view_field = $view->style_plugin->getField($i - 1, 'rendered_entity');
      $search_result = strpos($view_field, "Test $i") !== FALSE;
      $this->assertFalse($search_result, "The text 'Test $i' not found in the view.");
    }

    $this->assertConfigDependencies($view->storage);
    $this->assertCacheabilityMetadata($build);
  }

  /**
   * Ensures that the expected cacheability metadata is applied.
   *
   * @param array $build
   *   The render array
   */
  protected function assertCacheabilityMetadata($build) {
    $this->assertEqual([
      'config:core.entity_view_display.entity_test.entity_test.foobar',
      'config:views.view.test_field_entity_test_rendered',
      'entity_test:1',
      'entity_test:2',
      'entity_test:3',
      'entity_test_list',
      'entity_test_view',
    ], $build['#cache']['tags']);

    $this->assertEqual([
      'entity_test_view_grants',
      'languages:language_interface',
      'theme',
      'url.query_args',
      'user.permissions',
    ], $build['#cache']['contexts']);
  }

  /**
   * Ensures that the config dependencies are calculated the right way.
   *
   * @param \Drupal\views\Entity\View $storage
   */
  protected function assertConfigDependencies(View $storage) {
    $storage->calculateDependencies();
    $this->assertEqual([
      'config' => ['core.entity_view_mode.entity_test.foobar'],
      'module' => ['entity_test'],
    ], $storage->getDependencies());
  }

  /**
   * Tests the rendered entity output with the test field configured to show.
   */
  public function testRenderedEntityWithField() {
    \Drupal::currentUser()->setAccount($this->user);

    // Show the test_field on the entity_test.entity_test.foobar view display.
    EntityViewDisplay::load('entity_test.entity_test.foobar')->setComponent('test_field', ['type' => 'string', 'label' => 'above'])->save();

    $view = Views::getView('test_field_entity_test_rendered');
    $build = [
      '#type' => 'view',
      '#name' => 'test_field_entity_test_rendered',
      '#view' => $view,
      '#display_id' => 'default',
    ];

    $renderer = \Drupal::service('renderer');
    $renderer->renderPlain($build);
    for ($i = 1; $i <= 3; $i++) {
      $view_field = $view->style_plugin->getField($i - 1, 'rendered_entity');
      $search_result = strpos($view_field, "Test $i") !== FALSE;
      $this->assertTrue($search_result, "The text 'Test $i' found in the view.");
    }

    $this->assertConfigDependencies($view->storage);
    $this->assertCacheabilityMetadata($build);
  }

}
