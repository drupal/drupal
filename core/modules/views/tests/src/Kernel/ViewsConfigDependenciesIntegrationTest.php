<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;

/**
 * Tests integration of views with other modules.
 *
 * @group views
 */
class ViewsConfigDependenciesIntegrationTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'entity_test',
    'user',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['entity_test_fields'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests integration with image module.
   */
  public function testImage() {
    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = ImageStyle::create(['name' => 'foo']);
    $style->save();

    // Create a new image field 'bar' to be used in 'entity_test_fields' view.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'bar',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'bar',
    ])->save();

    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('entity_test_fields');
    $display =& $view->getDisplay('default');

    // Add the 'bar' image field to 'entity_test_fields' view.
    $display['display_options']['fields']['bar'] = [
      'id' => 'bar',
      'field' => 'bar',
      'plugin_id' => 'field',
      'table' => 'entity_test__bar',
      'entity_type' => 'entity_test',
      'entity_field' => 'bar',
      'type' => 'image',
      'settings' => ['image_style' => 'foo', 'image_link' => ''],
    ];
    $view->save();

    $dependencies = $view->getDependencies() + ['config' => []];

    // Checks that style 'foo' is a dependency of view 'entity_test_fields'.
    $this->assertContains('image.style.foo', $dependencies['config']);

    // Delete the 'foo' image style.
    $style->delete();

    $view = View::load('entity_test_fields');

    // Checks that the view has not been deleted too.
    $this->assertNotNull(View::load('entity_test_fields'));

    // Checks that the image field was removed from the View.
    $display = $view->getDisplay('default');
    $this->assertFalse(isset($display['display_options']['fields']['bar']));

    // Checks that the view has been disabled.
    $this->assertFalse($view->status());

    $dependencies = $view->getDependencies() + ['config' => []];
    // Checks that the dependency on style 'foo' has been removed.
    $this->assertNotContains('image.style.foo', $dependencies['config']);
  }

  /**
   * Tests removing a config dependency that deletes the View.
   */
  public function testConfigRemovalRole() {
    // Create a role we can add to the View and delete.
    $role = Role::create([
      'id' => 'dummy',
      'label' => 'dummy',
    ]);

    $role->save();

    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('entity_test_fields');
    $display = &$view->getDisplay('default');

    // Set the access to be restricted by the dummy role.
    $display['display_options']['access'] = [
      'type' => 'role',
      'options' => [
        'role' => [
          $role->id() => $role->id(),
        ],
      ],
    ];
    $view->save();

    // Check that the View now has a dependency on the Role.
    $dependencies = $view->getDependencies() + ['config' => []];
    $this->assertContains('user.role.dummy', $dependencies['config']);

    // Delete the role.
    $role->delete();

    $view = View::load('entity_test_fields');

    // Checks that the view has been deleted too.
    $this->assertNull($view);
  }

  /**
   * Tests uninstalling a module that provides a base table for a View.
   */
  public function testConfigRemovalBaseTable() {
    // Find all the entity types provided by the entity_test module and install
    // the schema for them so we can uninstall them.
    $entities = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entities as $entity_type_id => $definition) {
      if ($definition instanceof ContentEntityTypeInterface && $definition->getProvider() == 'entity_test') {
        $this->installEntitySchema($entity_type_id);
      }
    }

    // Check that removing the module that provides the base table for a View,
    // deletes the View.
    $this->assertNotNull(View::load('entity_test_fields'));
    $this->container->get('module_installer')->uninstall(['entity_test']);
    // Check that the View has been deleted.
    $this->assertNull(View::load('entity_test_fields'));
  }

}
