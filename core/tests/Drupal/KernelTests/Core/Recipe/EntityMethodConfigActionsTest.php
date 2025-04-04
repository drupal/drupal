<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * @group Recipe
 */
class EntityMethodConfigActionsTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'config_test', 'entity_test', 'system'];

  /**
   * The configuration action manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    EntityTestBundle::create([
      'id' => 'test',
      'label' => $this->randomString(),
    ])->save();

    $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('entity_test_with_bundle', 'test')
      ->save();

    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   *  @covers \Drupal\Core\Config\Entity\ConfigEntityBase::getThirdPartySetting
   */
  public function testSetSingleThirdPartySetting(): void {
    $this->configActionManager->applyAction(
      'entity_method:core.entity_view_display:setThirdPartySetting',
      'core.entity_view_display.entity_test_with_bundle.test.default',
      [
        'module' => 'entity_test',
        'key' => 'verb',
        'value' => 'Save',
      ],
    );

    /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $display */
    $display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('entity_test_with_bundle', 'test');
    $this->assertSame('Save', $display->getThirdPartySetting('entity_test', 'verb'));
  }

  /**
   * Tests setting multiple third party settings.
   */
  public function testSetMultipleThirdPartySettings(): void {
    $this->configActionManager->applyAction(
      'entity_method:core.entity_view_display:setThirdPartySettings',
      'core.entity_view_display.entity_test_with_bundle.test.default',
      [
        [
          'module' => 'entity_test',
          'key' => 'noun',
          'value' => 'Spaceship',
        ],
        [
          'module' => 'entity_test',
          'key' => 'verb',
          'value' => 'Explode',
        ],
      ],
    );

    /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $display */
    $display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('entity_test_with_bundle', 'test');
    $this->assertSame('Spaceship', $display->getThirdPartySetting('entity_test', 'noun'));
    $this->assertSame('Explode', $display->getThirdPartySetting('entity_test', 'verb'));
  }

  /**
   * @testWith ["set", {"property_name": "protected_property", "value": "Here be sandworms..."}]
   *   ["setMultiple", [{"property_name": "protected_property", "value": "Here be sandworms..."}, {"property_name": "label", "value": "New face"}]]
   */
  public function testSet(string $action_name, array $value): void {
    $storage = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('config_test');

    $entity = $storage->create([
      'id' => 'foo',
      'label' => 'Behold!',
      'protected_property' => 'Here be dragons...',
    ]);
    $this->assertSame('Behold!', $entity->get('label'));
    $this->assertSame('Here be dragons...', $entity->get('protected_property'));
    $entity->save();

    $this->configActionManager->applyAction(
      "entity_method:config_test.dynamic:$action_name",
      $entity->getConfigDependencyName(),
      $value,
    );

    $expected_values = array_is_list($value) ? $value : [$value];
    $entity = $storage->load('foo');
    foreach ($expected_values as ['property_name' => $name, 'value' => $value]) {
      $this->assertSame($value, $entity->get($name));
    }
  }

  /**
   * @testWith [true, "setStatus", false, false]
   *   [false, "setStatus", true, true]
   *   [true, "disable", [], false]
   *   [false, "enable", [], true]
   */
  public function testSetStatus(bool $initial_status, string $action_name, array|bool $value, bool $expected_status): void {
    $storage = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('config_test');

    $entity = $storage->create([
      'id' => 'foo',
      'label' => 'Behold!',
      'status' => $initial_status,
    ]);
    $this->assertSame($initial_status, $entity->status());
    $entity->save();

    $this->configActionManager->applyAction(
      "entity_method:config_test.dynamic:$action_name",
      $entity->getConfigDependencyName(),
      $value,
    );

    $this->assertSame($expected_status, $storage->load('foo')->status());
  }

  /**
   * @testWith ["hideComponent"]
   *   ["hideComponents"]
   */
  public function testRemoveComponentFromDisplay(string $action_name): void {
    $this->assertStringStartsWith('hideComponent', $action_name);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $repository */
    $repository = $this->container->get(EntityDisplayRepositoryInterface::class);

    $view_display = $repository->getViewDisplay('entity_test_with_bundle', 'test');
    $this->assertIsArray($view_display->getComponent('name'));

    // The `hideComponent` action is an alias for `removeComponent`, proving
    // that entity methods can be aliased.
    $this->configActionManager->applyAction(
      "entity_method:core.entity_view_display:$action_name",
      $view_display->getConfigDependencyName(),
      $action_name === 'hideComponents' ? ['name'] : 'name',
    );

    $view_display = $repository->getViewDisplay('entity_test_with_bundle', 'test');
    $this->assertNull($view_display->getComponent('name'));

    // The underlying action name should not be available. It should be hidden
    // by the alias.
    $plugin_id = str_replace('hide', 'remove', $action_name);
    $this->assertFalse($this->configActionManager->hasDefinition($plugin_id));
  }

  /**
   * Test setting a nested property on a config entity.
   */
  public function testSetNestedProperty(): void {
    $this->container->get(ThemeInstallerInterface::class)
      ->install(['claro']);
    $block = $this->placeBlock('local_tasks_block', ['theme' => 'claro']);

    $this->configActionManager->applyAction(
      'setProperties',
      $block->getConfigDependencyName(),
      ['settings.label' => 'Magic!'],
    );
    $settings = Block::load($block->id())->get('settings');
    $this->assertSame('Magic!', $settings['label']);

    // If the property is not nested, it should still work.
    $settings['label'] = 'Mundane';
    $this->configActionManager->applyAction(
      'setProperties',
      $block->getConfigDependencyName(),
      ['settings' => $settings],
    );
    $settings = Block::load($block->id())->get('settings');
    $this->assertSame('Mundane', $settings['label']);

    // We can use this to set a scalar property normally.
    $this->configActionManager->applyAction(
      'setProperties',
      $block->getConfigDependencyName(),
      ['region' => 'highlighted'],
    );
    $this->assertSame('highlighted', Block::load($block->id())->getRegion());

    // We should get an exception if we try to set a nested value on a property
    // that isn't an array.
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('The setProperties config action can only set nested values on arrays.');
    $this->configActionManager->applyAction(
      'setProperties',
      $block->getConfigDependencyName(),
      ['theme.name' => 'stark'],
    );
  }

  /**
   * Tests that the setProperties action refuses to modify entity IDs or UUIDs.
   *
   * @testWith ["id"]
   *   ["uuid"]
   */
  public function testSetPropertiesWillNotChangeEntityKeys(string $key): void {
    $view_display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('entity_test_with_bundle', 'test');
    $this->assertFalse($view_display->isNew());

    $property_name = $view_display->getEntityType()->getKey($key);
    $this->assertNotEmpty($property_name);

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage("Entity key '$property_name' cannot be changed by the setProperties config action.");
    $this->configActionManager->applyAction(
      'setProperties',
      $view_display->getConfigDependencyName(),
      [$property_name => '12345'],
    );
  }

  /**
   * Tests that the simpleConfigUpdate action cannot be used on entities.
   *
   * @group legacy
   */
  public function testSimpleConfigUpdateFailsOnEntities(): void {
    $view_display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('entity_test_with_bundle', 'test');
    $view_display->save();

    $this->expectDeprecation('Using the simpleConfigUpdate config action on config entities is deprecated in drupal:11.2.0 and throws an exception in drupal:12.0.0. Use the setProperties action instead. See https://www.drupal.org/node/3515543');
    $this->configActionManager->applyAction(
      'simpleConfigUpdate',
      $view_display->getConfigDependencyName(),
      ['hidden.uid' => TRUE],
    );
  }

}
