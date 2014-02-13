<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\ForumManagerTest.
 */

namespace Drupal\forum\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the ForumManager.
 *
 * @see \Drupal\forum\ForumManager
 */
class ForumManagerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Forum Manager',
      'description' => 'Tests the forum manager functionality.',
      'group' => 'Forum',
    );
  }

  /**
   * Tests ForumManager::getIndex().
   */
  public function testGetIndex() {
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $storage_controller = $this->getMockBuilder('\Drupal\taxonomy\VocabularyStorageController')
      ->disableOriginalConstructor()
      ->getMock();

    $config_factory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');

    $config = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();

    $config_factory->expects($this->once())
      ->method('get')
      ->will($this->returnValue($config));

    $config->expects($this->once())
      ->method('get')
      ->will($this->returnValue('forums'));

    $entity_manager->expects($this->once())
      ->method('getStorageController')
      ->will($this->returnValue($storage_controller));

    // This is sufficient for testing purposes.
    $term = new \stdClass();

    $storage_controller->expects($this->once())
      ->method('create')
      ->will($this->returnValue($term));

    $connection = $this->getMockBuilder('\Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $translation_manager = $this->getMockBuilder('\Drupal\Core\StringTranslation\TranslationManager')
      ->disableOriginalConstructor()
      ->getMock();

    $field_info = $this->getMockBuilder('\Drupal\field\FieldInfo')
      ->disableOriginalConstructor()
      ->getMock();

    $manager = $this->getMock('\Drupal\forum\ForumManager', array('getChildren'), array(
      $config_factory,
      $entity_manager,
      $connection,
      $field_info,
      $translation_manager,
    ));

    $manager->expects($this->once())
      ->method('getChildren')
      ->will($this->returnValue(array()));

    // Get the index once.
    $index1 = $manager->getIndex();

    // Get it again. This should not return the previously generated index. If
    // it does not, then the test will fail as the mocked methods will be called
    // more than once.
    $index2 = $manager->getIndex();

    $this->assertEquals($index1, $index2);
  }

}
