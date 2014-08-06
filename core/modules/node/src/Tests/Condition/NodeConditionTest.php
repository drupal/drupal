<?php

/**
 * @file
 * Contains \Drupal\condition\Tests\Condition\NodeConditionTest.
 */

namespace Drupal\node\Tests\Condition;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests that conditions, provided by the node module, are working properly.
 *
 * @group node
 */
class NodeConditionTest extends EntityUnitTestBase {

  public static $modules = array('node');

  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');

    // Create the node bundles required for testing.
    $type = entity_create('node_type', array('type' => 'page', 'name' => 'page'));
    $type->save();
    $type = entity_create('node_type', array('type' => 'article', 'name' => 'article'));
    $type->save();
    $type = entity_create('node_type', array('type' => 'test', 'name' => 'test'));
    $type->save();
  }

  /**
   * Tests conditions.
   */
  function testConditions() {
    $manager = $this->container->get('plugin.manager.condition', $this->container->get('container.namespaces'));
    $this->createUser();

    // Get some nodes of various types to check against.
    $page = entity_create('node', array('type' => 'page', 'title' => $this->randomMachineName(), 'uid' => 1));
    $page->save();
    $article = entity_create('node', array('type' => 'article', 'title' => $this->randomMachineName(), 'uid' => 1));
    $article->save();
    $test = entity_create('node', array('type' => 'test', 'title' => $this->randomMachineName(), 'uid' => 1));
    $test->save();

    // Grab the node type condition and configure it to check against node type
    // of 'article' and set the context to the page type node.
    $condition = $manager->createInstance('node_type')
      ->setConfig('bundles', array('article' => 'article'))
      ->setContextValue('node', $page);
    $this->assertFalse($condition->execute(), 'Page type nodes fail node type checks for articles.');
    // Check for the proper summary.
    $this->assertEqual('The node bundle is article', $condition->summary());

    // Set the node type check to page.
    $condition->setConfig('bundles', array('page' => 'page'));
    $this->assertTrue($condition->execute(), 'Page type nodes pass node type checks for pages');
    // Check for the proper summary.
    $this->assertEqual('The node bundle is page', $condition->summary());

    // Set the node type check to page or article.
    $condition->setConfig('bundles', array('page' => 'page', 'article' => 'article'));
    $this->assertTrue($condition->execute(), 'Page type nodes pass node type checks for pages or articles');
    // Check for the proper summary.
    $this->assertEqual('The node bundle is page or article', $condition->summary());

    // Set the context to the article node.
    $condition->setContextValue('node', $article);
    $this->assertTrue($condition->execute(), 'Article type nodes pass node type checks for pages or articles');

    // Set the context to the test node.
    $condition->setContextValue('node', $test);
    $this->assertFalse($condition->execute(), 'Test type nodes pass node type checks for pages or articles');

    // Check a greater than 2 bundles summary scenario.
    $condition->setConfig('bundles', array('page' => 'page', 'article' => 'article', 'test' => 'test'));
    $this->assertEqual('The node bundle is page, article or test', $condition->summary());

    // Test Constructor injection.
    $condition = $manager->createInstance('node_type', array('bundles' => array('article' => 'article'), 'context' => array('node' => $article)));
    $this->assertTrue($condition->execute(), 'Constructor injection of context and configuration working as anticipated.');
  }
}
