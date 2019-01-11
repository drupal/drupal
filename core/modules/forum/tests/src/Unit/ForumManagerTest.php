<?php

namespace Drupal\Tests\forum\Unit;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);

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

    $entity_type_manager->expects($this->once())
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

    $manager = $this->getMock('\Drupal\forum\ForumManager', ['getChildren'], [
      $config_factory,
      $entity_type_manager,
      $connection,
      $translation_manager,
      $comment_manager,
      $entity_field_manager,
    ]);

    $manager->expects($this->once())
      ->method('getChildren')
      ->will($this->returnValue([]));

    // Get the index once.
    $index1 = $manager->getIndex();

    // Get it again. This should not return the previously generated index. If
    // it does not, then the test will fail as the mocked methods will be called
    // more than once.
    $index2 = $manager->getIndex();

    $this->assertEquals($index1, $index2);
  }

}
