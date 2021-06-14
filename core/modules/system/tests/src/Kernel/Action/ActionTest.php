<?php

namespace Drupal\Tests\system\Kernel\Action;

use Drupal\Core\Action\ActionInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Action;
use Drupal\user\RoleInterface;

/**
 * Tests action plugins.
 *
 * @group Action
 */
class ActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'field', 'user', 'action_test'];

  /**
   * The action manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->actionManager = $this->container->get('plugin.manager.action');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests the functionality of test actions.
   */
  public function testOperations() {
    // Test that actions can be discovered.
    $definitions = $this->actionManager->getDefinitions();
    // Verify that the action definitions are found.
    $this->assertGreaterThan(1, count($definitions));
    $this->assertTrue(!empty($definitions['action_test_no_type']), 'The test action is among the definitions found.');

    $definition = $this->actionManager->getDefinition('action_test_no_type');
    $this->assertTrue(!empty($definition), 'The test action definition is found.');

    $definitions = $this->actionManager->getDefinitionsByType('user');
    $this->assertTrue(empty($definitions['action_test_no_type']), 'An action with no type is not found.');

    // Create an instance of the 'save entity' action.
    $action = $this->actionManager->createInstance('action_test_save_entity');
    $this->assertInstanceOf(ActionInterface::class, $action);

    // Create a new unsaved user.
    $name = $this->randomMachineName();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
    $account = $user_storage->create(['name' => $name, 'bundle' => 'user']);
    $loaded_accounts = $user_storage->loadMultiple();
    $this->assertCount(0, $loaded_accounts);

    // Execute the 'save entity' action.
    $action->execute($account);
    $loaded_accounts = $user_storage->loadMultiple();
    $this->assertCount(1, $loaded_accounts);
    $account = reset($loaded_accounts);
    $this->assertEquals($name, $account->label());
  }

  /**
   * Tests the dependency calculation of actions.
   */
  public function testDependencies() {
    // Create a new action that depends on a user role.
    $action = Action::create([
      'id' => 'user_add_role_action.' . RoleInterface::ANONYMOUS_ID,
      'type' => 'user',
      'label' => t('Add the anonymous role to the selected users'),
      'configuration' => [
        'rid' => RoleInterface::ANONYMOUS_ID,
      ],
      'plugin' => 'user_add_role_action',
    ]);
    $action->save();

    $expected = [
      'config' => [
        'user.role.' . RoleInterface::ANONYMOUS_ID,
      ],
      'module' => [
        'user',
      ],
    ];
    $this->assertSame($expected, $action->calculateDependencies()->getDependencies());
  }

}
