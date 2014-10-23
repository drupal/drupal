<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Action\ActionUnitTest.
 */

namespace Drupal\system\Tests\Action;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Action\ActionInterface;

/**
 * Tests action plugins.
 *
 * @group Action
 */
class ActionUnitTest extends DrupalUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('system','entity' , 'field', 'user', 'action_test');

  /**
   * The action manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->actionManager = $this->container->get('plugin.manager.action');
    $this->installEntitySchema('user');
    $this->installSchema('system', array('sequences'));
  }

  /**
   * Tests the functionality of test actions.
   */
  public function testOperations() {
    // Test that actions can be discovered.
    $definitions = $this->actionManager->getDefinitions();
    $this->assertTrue(count($definitions) > 1, 'Action definitions are found.');
    $this->assertTrue(!empty($definitions['action_test_no_type']), 'The test action is among the definitions found.');

    $definition = $this->actionManager->getDefinition('action_test_no_type');
    $this->assertTrue(!empty($definition), 'The test action definition is found.');

    $definitions = $this->actionManager->getDefinitionsByType('user');
    $this->assertTrue(empty($definitions['action_test_no_type']), 'An action with no type is not found.');

    // Create an instance of the 'save entity' action.
    $action = $this->actionManager->createInstance('action_test_save_entity');
    $this->assertTrue($action instanceof ActionInterface, 'The action implements the correct interface.');

    // Create a new unsaved user.
    $name = $this->randomMachineName();
    $user_storage = $this->container->get('entity.manager')->getStorage('user');
    $account = $user_storage->create(array('name' => $name, 'bundle' => 'user'));
    $loaded_accounts = $user_storage->loadMultiple();
    $this->assertEqual(count($loaded_accounts), 0);

    // Execute the 'save entity' action.
    $action->execute($account);
    $loaded_accounts = $user_storage->loadMultiple();
    $this->assertEqual(count($loaded_accounts), 1);
    $account = reset($loaded_accounts);
    $this->assertEqual($name, $account->label());
  }

  /**
   * Tests the dependency calculation of actions.
   */
  public function testDependencies() {
    // Create a new action that depends on a user role.
    $action = entity_create('action', array(
      'id' => 'user_add_role_action.' . DRUPAL_ANONYMOUS_RID,
      'type' => 'user',
      'label' => t('Add the anonymous role to the selected users'),
      'configuration' => array(
        'rid' => DRUPAL_ANONYMOUS_RID,
      ),
      'plugin' => 'user_add_role_action',
    ));
    $action->save();

    $expected = array(
      'config' => array(
        'user.role.' . DRUPAL_ANONYMOUS_RID,
      ),
      'module' => array(
        'user',
      ),
    );
    $this->assertIdentical($expected, $action->calculateDependencies());
  }

}
