<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Recipe
 */
class EntityMethodConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test', 'entity_test', 'system'];

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

}
