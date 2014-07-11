<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\ForumManagerTest.
 */

namespace Drupal\forum\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\forum\ForumManager
 * @group forum
 */
class ForumManagerTest extends UnitTestCase {

  /**
   * Tests ForumManager::getIndex().
   */
  public function testGetIndex() {
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $storage = $this->getMockBuilder('\Drupal\taxonomy\VocabularyStorage')
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
      ->method('getStorage')
      ->will($this->returnValue($storage));

    // This is sufficient for testing purposes.
    $term = new \stdClass();

    $storage->expects($this->once())
      ->method('create')
      ->will($this->returnValue($term));

    $connection = $this->getMockBuilder('\Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $translation_manager = $this->getMockBuilder('\Drupal\Core\StringTranslation\TranslationManager')
      ->disableOriginalConstructor()
      ->getMock();

    $comment_manager = $this->getMockBuilder('\Drupal\comment\CommentManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $manager = $this->getMock('\Drupal\forum\ForumManager', array('getChildren'), array(
      $config_factory,
      $entity_manager,
      $connection,
      $translation_manager,
      $comment_manager,
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
