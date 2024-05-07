<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * @covers \Drupal\field\Plugin\ConfigAction\AddToAllBundles
 *
 * @group Recipe
 * @group field
 */
class AddToAllBundlesConfigActionTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'system', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create([
      'type' => 'one',
      'name' => 'One',
    ])->save();
    NodeType::create([
      'type' => 'two',
      'name' => 'Two',
    ])->save();
  }

  /**
   * Tests instantiating a field on all bundles of an entity type.
   */
  public function testInstantiateNewFieldOnAllBundles(): void {
    // Ensure the body field doesn't actually exist yet.
    $storage_definitions = $this->container->get(EntityFieldManagerInterface::class)
      ->getFieldStorageDefinitions('node');
    $this->assertArrayNotHasKey('body', $storage_definitions);

    $this->applyAction('field.storage.node.body');

    // Fields and expected data exist.
    /** @var \Drupal\field\FieldConfigInterface[] $body_fields */
    $body_fields = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => 'node',
        'field_name' => 'body',
      ]);
    ksort($body_fields);
    $this->assertSame(['node.one.body', 'node.two.body'], array_keys($body_fields));
    foreach ($body_fields as $field) {
      $this->assertSame('Body field label', $field->label());
      $this->assertSame('Set by config actions.', $field->getDescription());
    }

    // Expect an error when the 'addToAllBundles' action is invoked on anything
    // other than a field storage config entity.
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "addToAllBundles" plugin does not exist.');
    $this->applyAction('user.role.anonymous');
  }

  /**
   * Tests that the action can be set to fail if the field already exists.
   */
  public function testFailIfExists(): void {
    $this->installConfig('node');
    node_add_body_field(NodeType::load('one'));

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('Field node.one.body already exists.');
    $this->applyAction('field.storage.node.body', TRUE);
  }

  /**
   * Tests that the action will ignore existing fields by default.
   */
  public function testIgnoreExistingFields(): void {
    $this->installConfig('node');

    node_add_body_field(NodeType::load('one'))
      ->setLabel('Original label')
      ->setDescription('Original description')
      ->save();

    $this->applyAction('field.storage.node.body');

    // The existing field should not be changed.
    $field = FieldConfig::loadByName('node', 'one', 'body');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('Original label', $field->label());
    $this->assertSame('Original description', $field->getDescription());

    // But the new field should be created as expected.
    $field = FieldConfig::loadByName('node', 'two', 'body');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('Body field label', $field->label());
    $this->assertSame('Set by config actions.', $field->getDescription());
  }

  /**
   * Applies a recipe with the addToAllBundles action.
   *
   * @param string $config_name
   *   The name of the config object which should run the addToAllBundles
   *   action.
   * @param bool $fail_if_exists
   *   (optional) Whether the action should fail if the field already exists on
   *   any bundle. Defaults to FALSE.
   */
  private function applyAction(string $config_name, bool $fail_if_exists = FALSE): void {
    $fail_if_exists = var_export($fail_if_exists, TRUE);
    $contents = <<<YAML
name: Instantiate field on all bundles
config:
  import:
    node:
      - field.storage.node.body
  actions:
    $config_name:
      addToAllBundles:
        label: Body field label
        description: Set by config actions.
        fail_if_exists: $fail_if_exists
YAML;
    $recipe = $this->createRecipe($contents);
    RecipeRunner::processRecipe($recipe);
  }

}
