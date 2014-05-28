<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\ContextPluginTest.
 */

namespace Drupal\system\Tests\Plugin;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\plugin_test\Plugin\MockBlockManager;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Tests that context aware plugins function correctly.
 */
class ContextPluginTest extends DrupalUnitTestBase {

  public static $modules = array('system', 'user', 'node', 'field', 'filter', 'text');

  public static function getInfo() {
    return array(
      'name' => 'Contextual Plugins',
      'description' => 'Tests that contexts are properly set and working within plugins.',
      'group' => 'Plugin API',
    );
  }

  /**
   * Tests basic context definition and value getters and setters.
   */
  function testContext() {
    $name = $this->randomName();
    $manager = new MockBlockManager();
    $plugin = $manager->createInstance('user_name');
    // Create a node, add it as context, catch the exception.
    $node = entity_create('node', array('title' => $name, 'type' => 'page'));

    // Try to get a valid context that has not been set.
    try {
      $plugin->getContext('user');
      $this->fail('The user context should not yet be set.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The user context is not yet set.');
    }

    // Try to get an invalid context.
    try {
      $plugin->getContext('node');
      $this->fail('The node context should not be a valid context.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The node context is not a valid context.');
    }

    // Try to get a valid context value that has not been set.
    try {
      $plugin->getContextValue('user');
      $this->fail('The user context should not yet be set.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The user context is not yet set.');
    }

    // Try to call a method of the plugin that requires context before it has
    // been set.
    try {
      $plugin->getTitle();
      $this->fail('The user context should not yet be set.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The user context is not yet set.');
    }

    // Try to get a context value that is not valid.
    try {
      $plugin->getContextValue('node');
      $this->fail('The node context should not be a valid context.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The node context is not a valid context.');
    }

    // Try to pass the wrong class type as a context value.
    try {
      $plugin->setContextValue('user', $node);
      $this->fail('The node context should fail validation for a user context.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The provided context value does not pass validation.');
    }

    // Set an appropriate context value appropriately and check to make sure
    // its methods work as expected.
    $user = entity_create('user', array('name' => $name));
    $plugin->setContextValue('user', $user);
    $this->assertEqual($user->label(), $plugin->getTitle());

    // Test the getContextDefinitions() method.
    $this->assertIdentical($plugin->getContextDefinitions(), array('user' => array('class' => 'Drupal\user\UserInterface')));

    // Test the getContextDefinition() method for a valid context.
    $this->assertEqual($plugin->getContextDefinition('user'), array('class' => 'Drupal\user\UserInterface'));

    // Test the getContextDefinition() method for an invalid context.
    try {
      $plugin->getContextDefinition('node');
      $this->fail('The node context should not be a valid context.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The node context is not a valid context.');
    }

    // Test typed data context plugins.
    $typed_data_plugin = $manager->createInstance('string_context');

    // Try to get a valid context value that has not been set.
    try {
      $typed_data_plugin->getContextValue('string');
      $this->fail('The string context should not yet be set.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The string context is not yet set.');
    }

    // Try to call a method of the plugin that requires a context value before
    // it has been set.
    try {
      $typed_data_plugin->getTitle();
      $this->fail('The string context should not yet be set.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The string context is not yet set.');
    }

    // Set the context value appropriately and check the title.
    $typed_data_plugin->setContextValue('string', $name);
    $this->assertEqual($name, $typed_data_plugin->getTitle());

    // Test Complex compound context handling.
    $complex_plugin = $manager->createInstance('complex_context');

    // With no contexts set, try to get the contexts.
    try {
      $complex_plugin->getContexts();
      $this->fail('There should not be any contexts set yet.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'There are no set contexts.');
    }

    // With no contexts set, try to get the context values.
    $values = $complex_plugin->getContextValues();
    $this->assertIdentical(array_filter($values), array(), 'There are no set contexts.');

    // Set the user context value.
    $complex_plugin->setContextValue('user', $user);

    // With only the user context set, try to get the contexts.
    try {
      $complex_plugin->getContexts();
      $this->fail('The node context should not yet be set.');
    }
    catch (PluginException $e) {
      $this->assertEqual($e->getMessage(), 'The node context is not yet set.');
    }

    // With only the user context set, try to get the context values.
    $values = $complex_plugin->getContextValues();
    $this->assertNull($values['node'], 'The node context is not yet set.');
    $this->assertNotNull($values['user'], 'The user context is set');

    $complex_plugin->setContextValue('node', $node);
    $context_wrappers = $complex_plugin->getContexts();
    // Make sure what came out of the wrappers is good.
    $this->assertEqual($context_wrappers['user']->getContextValue()->label(), $user->label());
    $this->assertEqual($context_wrappers['node']->getContextValue()->label(), $node->label());

    // Make sure what comes out of the context values is good.
    $contexts = $complex_plugin->getContextValues();
    $this->assertEqual($contexts['user']->label(), $user->label());
    $this->assertEqual($contexts['node']->label(), $node->label());

    // Test the title method for the complex context plugin.
    $this->assertEqual($user->label() . ' -- ' . $node->label(), $complex_plugin->getTitle());
  }
}
