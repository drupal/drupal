<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\Role;
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
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field'];

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
  protected function setUpFixtures(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['entity_test']);

    // Create user 1 so that the user created later in the test has a different
    // user ID.
    // @todo Remove in https://www.drupal.org/node/540008.
    User::create(['uid' => 1, 'name' => 'user1'])->save();

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

    Role::create([
      'id' => 'test_role',
      'label' => 'Can view test entities',
      'permissions' => ['view test entity'],
    ])->save();

    $this->user = User::create([
      'name' => 'test user',
      'roles' => ['test_role'],
    ]);
    $this->user->save();

    parent::setUpFixtures();
  }

  /**
   * Tests the default rendered entity output.
   */
  public function testRenderedEntityWithoutAndWithField(): void {
    // First test without test_field displayed.
    \Drupal::currentUser()->setAccount($this->user);

    $display = EntityViewDisplay::load('entity_test.entity_test.foobar');
    $display->removeComponent('test_field')
      ->save();

    $view = Views::getView('test_field_entity_test_rendered');
    $build = [
      '#type' => 'view',
      '#name' => 'test_field_entity_test_rendered',
      '#view' => $view,
      '#display_id' => 'default',
    ];
    $renderer = \Drupal::service('renderer');
    $renderer->renderInIsolation($build);
    for ($i = 1; $i <= 3; $i++) {
      $view_field = (string) $view->style_plugin->getField($i - 1, 'rendered_entity');
      $search_result = str_contains($view_field, "Test $i");
      $this->assertFalse($search_result, "The text 'Test $i' not found in the view.");
    }

    $this->assertConfigDependencies($view->storage);
    $this->assertCacheabilityMetadata($build);

    // Now show the test_field on the entity_test.entity_test.foobar view
    // display to confirm render is updated correctly.
    $display->setComponent('test_field', ['type' => 'string', 'label' => 'above'])->save();
    // Need to reload the view because the rendered fields are statically cached
    // in the object.
    $view = Views::getView('test_field_entity_test_rendered');
    $build = [
      '#type' => 'view',
      '#name' => 'test_field_entity_test_rendered',
      '#view' => $view,
      '#display_id' => 'default',
    ];

    $renderer->renderInIsolation($build);
    for ($i = 1; $i <= 3; $i++) {
      $view_field = (string) $view->style_plugin->getField($i - 1, 'rendered_entity');
      $search_result = str_contains($view_field, "Test $i");
      $this->assertTrue($search_result, "The text 'Test $i' found in the view.");
    }

    $this->assertConfigDependencies($view->storage);
    $this->assertCacheabilityMetadata($build);
  }

  /**
   * Ensures that the expected cacheability metadata is applied.
   *
   * @param array $build
   *   The render array
   *
   * @internal
   */
  protected function assertCacheabilityMetadata(array $build): void {
    $this->assertEqualsCanonicalizing([
      'config:views.view.test_field_entity_test_rendered',
      'entity_test:1',
      'entity_test:2',
      'entity_test:3',
      'entity_test_list',
      'entity_test_view',
    ], $build['#cache']['tags']);

    $this->assertEqualsCanonicalizing([
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
   *   The view storage.
   *
   * @internal
   */
  protected function assertConfigDependencies(View $storage): void {
    $storage->calculateDependencies();
    $this->assertEquals([
      'config' => ['core.entity_view_mode.entity_test.foobar'],
      'module' => ['entity_test'],
    ], $storage->getDependencies());
  }

}
