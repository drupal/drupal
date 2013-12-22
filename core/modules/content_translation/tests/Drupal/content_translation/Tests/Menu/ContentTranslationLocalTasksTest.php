<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Menu\BlockLocalTasksTest.
 */

namespace Drupal\content_translation\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;
use Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks;;

/**
 * Tests existence of block local tasks.
 *
 * @group Drupal
 * @group Block
 */
class ContentTranslationLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Content translation local tasks test',
      'description' => 'Test content translation local tasks.',
      'group' => 'Content Translation',
    );
  }

  public function setUp() {
    $this->directoryList = array(
      'content_translation' => 'core/modules/content_translation',
      'node' => 'core/modules/node',
    );
    parent::setUp();

    $content_translation_manager = $this->getMock('Drupal\content_translation\ContentTranslationManagerInterface');
    $content_translation_manager->expects($this->any())
      ->method('getSupportedEntityTypes')
      ->will($this->returnValue(array(
        'node' => array(
          'translatable' => TRUE,
          'links' => array(
            'canonical' => 'node.view',
            'drupal:content-translation-overview' => 'content_translation.translation_overview_node',
          ),
        ),
      )));
    \Drupal::getContainer()->set('content_translation.manager', $content_translation_manager);
  }

  /**
   * {@inheritdoc}
   */
  protected function getLocalTaskManager($modules, $route_name, $route_params) {
    $manager = parent::getLocalTaskManager($modules, $route_name, $route_params);

    // Duplicate content_translation_local_tasks_alter()'s code here to avoid
    // having to load the .module file.
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->will($this->returnCallback(function ($hook, &$local_tasks) {
          // Alters in tab_root_id onto the content translation local task.
          $derivative = ContentTranslationLocalTasks::create(\Drupal::getContainer(), 'content_translation.local_tasks');
          $derivative->alterLocalTasks($local_tasks);
      }));
    return $manager;
  }

  /**
   * Tests the block admin display local tasks.
   *
   * @dataProvider providerTestBlockAdminDisplay
   */
  public function testBlockAdminDisplay($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function providerTestBlockAdminDisplay() {
    return array(
      array('node.view', array(array(
        'content_translation.local_tasks:content_translation.translation_overview_node',
        'node.view',
        'node.page_edit',
        'node.delete_confirm',
        'node.revision_overview',
      ))),
      array('content_translation.translation_overview_node', array(array(
        'content_translation.local_tasks:content_translation.translation_overview_node',
        'node.view',
        'node.page_edit',
        'node.delete_confirm',
        'node.revision_overview',
      ))),
    );
  }

}
