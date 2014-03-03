<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockStorageUnitTest.
 */

namespace Drupal\block\Tests;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\block_test\Plugin\Block\TestHtmlBlock;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\block\Entity\Block;
use Drupal\block\BlockInterface;

/**
 * Tests the storage of blocks.
 */
class BlockStorageUnitTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test', 'system');

  /**
   * The block storage controller.
   *
   * @var \Drupal\Core\Config\Entity\ConfigStorageControllerInterface.
   */
  protected $controller;

  public static function getInfo() {
    return array(
      'name' => 'Block storage',
      'description' => 'Tests the storage of blocks.',
      'group' => 'Block'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->controller = $this->container->get('entity.manager')->getStorageController('block');
  }

  /**
   * Tests CRUD operations.
   */
  public function testBlockCRUD() {
    $this->assertTrue($this->controller instanceof ConfigStorageController, 'The block storage controller is loaded.');

    // Run each test method in the same installation.
    $this->createTests();
    $this->loadTests();
    $this->renderTests();
    $this->deleteTests();
  }

  /**
   * Tests the creation of blocks.
   */
  protected function createTests() {
    // Attempt to create a block without a plugin.
    try {
      $entity = $this->controller->create(array());
      $entity->getPlugin();
      $this->fail('A block without a plugin was created with no exception thrown.');
    }
    catch (PluginException $e) {
      $this->assertEqual('The block \'\' did not specify a plugin.', $e->getMessage(), 'An exception was thrown when a block was created without a plugin.');
    }

    // Create a block with only required values.
    $entity = $this->controller->create(array(
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'test_html',
    ));
    $entity->save();

    $this->assertTrue($entity instanceof Block, 'The newly created entity is a Block.');

    // Verify all of the block properties.
    $actual_properties = \Drupal::config('block.block.test_block')->get();
    $this->assertTrue(!empty($actual_properties['uuid']), 'The block UUID is set.');
    unset($actual_properties['uuid']);

    // Ensure that default values are filled in.
    $expected_properties = array(
      'id' => 'test_block',
      'weight' => NULL,
      'status' => TRUE,
      'langcode' => language_default()->id,
      'theme' => 'stark',
      'region' => '-1',
      'plugin' => 'test_html',
      'settings' => array(
        'label' => '',
        'module' => 'block_test',
        'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
        'cache' => DRUPAL_NO_CACHE,
      ),
      'visibility' => NULL,
    );
    $this->assertIdentical($actual_properties, $expected_properties);

    $this->assertTrue($entity->getPlugin() instanceof TestHtmlBlock, 'The entity has an instance of the correct block plugin.');
  }

  /**
   * Tests the rendering of blocks.
   */
  protected function loadTests() {
    $entity = $this->controller->load('test_block');

    $this->assertTrue($entity instanceof Block, 'The loaded entity is a Block.');

    // Verify several properties of the block.
    $this->assertEqual($entity->get('region'), '-1');
    $this->assertTrue($entity->get('status'));
    $this->assertEqual($entity->get('theme'), 'stark');
    $this->assertTrue($entity->uuid());
  }

  /**
   * Tests the rendering of blocks.
   */
  protected function renderTests() {
    // Test the rendering of a block.
    $entity = entity_load('block', 'test_block');
    $output = entity_view($entity, 'block');
    $expected = array();
    $expected[] = '<div class="block block-block-test" id="block-test-block">';
    $expected[] = '  ';
    $expected[] = '    ';
    $expected[] = '';
    $expected[] = '  <div class="content">';
    $expected[] = '          ';
    $expected[] = '      </div>';
    $expected[] = '</div>';
    $expected[] = '';
    $expected_output = implode("\n", $expected);
    $this->assertEqual(drupal_render($output), $expected_output);

    // Reset the HTML IDs so that the next render is not affected.
    drupal_static_reset('drupal_html_id');

    // Test the rendering of a block with a given title.
    $entity = $this->controller->create(array(
      'id' => 'test_block2',
      'theme' => 'stark',
      'plugin' => 'test_html',
      'settings' => array(
        'label' => 'Powered by Bananas',
      ),
    ));
    $entity->save();
    $output = entity_view($entity, 'block');
    $expected = array();
    $expected[] = '<div class="block block-block-test" id="block-test-block2">';
    $expected[] = '  ';
    $expected[] = '      <h2>Powered by Bananas</h2>';
    $expected[] = '    ';
    $expected[] = '';
    $expected[] = '  <div class="content">';
    $expected[] = '          ';
    $expected[] = '      </div>';
    $expected[] = '</div>';
    $expected[] = '';
    $expected_output = implode("\n", $expected);
    $this->assertEqual(drupal_render($output), $expected_output);
    // Clean up this entity.
    $entity->delete();
  }

  /**
   * Tests the deleting of blocks.
   */
  protected function deleteTests() {
    $entity = $this->controller->load('test_block');

    // Ensure that the storage isn't currently empty.
    $config_storage = $this->container->get('config.storage');
    $config = $config_storage->listAll('block.block.');
    $this->assertFalse(empty($config), 'There are blocks in config storage.');

    // Delete the block.
    $entity->delete();

    // Ensure that the storage is now empty.
    $config = $config_storage->listAll('block.block.');
    $this->assertTrue(empty($config), 'There are no blocks in config storage.');
  }

  /**
   * Tests the installation of default blocks.
   */
  public function testDefaultBlocks() {
    $entities = $this->controller->loadMultiple();
    $this->assertTrue(empty($entities), 'There are no blocks initially.');

    // Install the block_test.module, so that its default config is installed.
    $this->installConfig(array('block_test'));

    $entities = $this->controller->loadMultiple();
    $entity = reset($entities);
    $this->assertEqual($entity->id(), 'test_block', 'The default test block was loaded.');
  }

}
