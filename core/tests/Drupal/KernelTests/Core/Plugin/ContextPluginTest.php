<?php

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\plugin_test\Plugin\MockBlockManager;
use Drupal\user\Entity\User;

/**
 * Tests that contexts are properly set and working within plugins.
 *
 * @group Plugin
 */
class ContextPluginTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'filter',
    'text',
  ];

  /**
   * Tests basic context definition and value getters and setters.
   */
  public function testContext() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $type = NodeType::create(['type' => 'page', 'name' => 'Page']);
    $type->save();

    $name = $this->randomMachineName();
    $manager = new MockBlockManager();
    $plugin = $manager->createInstance('user_name');
    // Create a node, add it as context, catch the exception.
    $node = Node::create(['type' => 'page', 'title' => $name]);

    // Try to get context that is missing its definition.
    try {
      $plugin->getContextDefinition('not_exists');
      $this->fail('The user context should not yet be set.');
    }
    catch (ContextException $e) {
      $this->assertEquals('The not_exists context is not a valid context.', $e->getMessage());
    }

    // Test the getContextDefinitions() method.
    $user_context_definition = EntityContextDefinition::fromEntityTypeId('user')->setLabel(t('User'));
    $this->assertEquals($plugin->getContextDefinitions()['user']->getLabel(), $user_context_definition->getLabel());

    // Test the getContextDefinition() method for a valid context.
    $this->assertEquals($plugin->getContextDefinition('user')->getLabel(), $user_context_definition->getLabel());

    // Try to get a context with valid definition.
    $this->assertNotNull($plugin->getContext('user'), 'Succeeded to get a context with a valid definition.');

    // Try to get a value of a valid context, while this value has not been set.
    try {
      $plugin->getContextValue('user');
    }
    catch (ContextException $e) {
      $this->assertSame("The 'entity:user' context is required and not present.", $e->getMessage(), 'Requesting a non-set value of a required context should throw a context exception.');
    }

    // Try to pass the wrong class type as a context value.
    $plugin->setContextValue('user', $node);
    $violations = $plugin->validateContexts();
    $this->assertTrue(!empty($violations), 'The provided context value does not pass validation.');

    // Set an appropriate context value and check to make sure its methods work
    // as expected.
    $user = User::create(['name' => $name]);
    $plugin->setContextValue('user', $user);

    $this->assertEquals($user->getAccountName(), $plugin->getContextValue('user')->getAccountName());
    $this->assertEquals($user->label(), $plugin->getTitle());

    // Test Optional context handling.
    $plugin = $manager->createInstance('user_name_optional');
    $this->assertNull($plugin->getContextValue('user'), 'Requesting a non-set value of a valid context should return NULL.');

    // Test Complex compound context handling.
    $complex_plugin = $manager->createInstance('complex_context');
    $complex_plugin->setContextValue('user', $user);

    // With only the user context set, try to get the context values.
    $values = $complex_plugin->getContextValues();
    $this->assertNull($values['node'], 'The node context is not yet set.');
    $this->assertNotNull($values['user'], 'The user context is set');

    $complex_plugin->setContextValue('node', $node);
    $context_wrappers = $complex_plugin->getContexts();
    // Make sure what came out of the wrappers is good.
    $this->assertEquals($user->label(), $context_wrappers['user']->getContextValue()->label());
    $this->assertEquals($node->label(), $context_wrappers['node']->getContextValue()->label());

    // Make sure what comes out of the context values is good.
    $contexts = $complex_plugin->getContextValues();
    $this->assertEquals($user->label(), $contexts['user']->label());
    $this->assertEquals($node->label(), $contexts['node']->label());

    // Test the title method for the complex context plugin.
    $this->assertEquals($user->label() . ' -- ' . $node->label(), $complex_plugin->getTitle());
  }

}
