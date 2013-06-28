<?php

/**
 * @file
 * Contains \Drupal\condition\Tests\Condition\NodeConditionTest.
 */

namespace Drupal\node\Tests\Condition;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the node conditions.
 */
class NodeConditionTest extends DrupalUnitTestBase {

  public static $modules = array('system', 'node', 'field');

  public static function getInfo() {
    return array(
      'name' => 'Node Condition Plugins',
      'description' => 'Tests that conditions, provided by the node module, are working properly.',
      'group' => 'Condition API',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', 'node');
    $this->installSchema('node', 'node_field_data');
    $this->installSchema('node', 'node_field_revision');
  }

  /**
   * Tests conditions.
   */
  function testConditions() {
    $manager = $this->container->get('plugin.manager.condition', $this->container->get('container.namespaces'));

    // Get some nodes of various types to check against.
    $page = entity_create('node', array('type' => 'page', 'title' => $this->randomName()));
    $page->save();
    $article = entity_create('node', array('type' => 'article', 'title' => $this->randomName()));
    $article->save();
    $test = entity_create('node', array('type' => 'test', 'title' => $this->randomName()));
    $test->save();

    // Grab the node type condition and configure it to check against node type
    // of 'article' and set the context to the page type node.
    $condition = $manager->createInstance('node_type')
      ->setConfig('bundles', array('article'))
      ->setContextValue('node', $page);
    $this->assertFalse($condition->execute(), 'Page type nodes fail node type checks for articles.');
    // Check for the proper summary.
    $this->assertEqual('The node bundle is article', $condition->summary());

    // Set the node type check to page.
    $condition->setConfig('bundles', array('page'));
    $this->assertTrue($condition->execute(), 'Page type nodes pass node type checks for pages');
    // Check for the proper summary.
    $this->assertEqual('The node bundle is page', $condition->summary());

    // Set the node type check to page or article.
    $condition->setConfig('bundles', array('page', 'article'));
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
    $condition->setConfig('bundles', array('page', 'article', 'test'));
    $this->assertEqual('The node bundle is page, article or test', $condition->summary());

    // Test Constructor injection.
    $condition = $manager->createInstance('node_type', array('bundles' => array('article'), 'context' => array('node' => $article)));
    $this->assertTrue($condition->execute(), 'Constructor injection of context and configuration working as anticipated.');
  }
}
