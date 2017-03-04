<?php

namespace Drupal\Tests\comment\Unit;

use Drupal\comment\CommentManager;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\comment\CommentManager
 * @group comment
 */
class CommentManagerTest extends UnitTestCase {

  /**
   * Tests the getFields method.
   *
   * @covers ::getFields
   */
  public function testGetFields() {
    // Set up a content entity type.
    $entity_type = $this->getMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue('Node'));
    $entity_type->expects($this->any())
      ->method('entityClassImplements')
      ->with(FieldableEntityInterface::class)
      ->will($this->returnValue(TRUE));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $entity_manager->expects($this->once())
      ->method('getFieldMapByFieldType')
      ->will($this->returnValue([
        'node' => [
          'field_foobar' => [
            'type' => 'comment',
          ],
        ],
      ]));

    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($entity_type));

    $comment_manager = new CommentManager(
      $entity_manager,
      $this->getMock('Drupal\Core\Config\ConfigFactoryInterface'),
      $this->getMock('Drupal\Core\StringTranslation\TranslationInterface'),
      $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface'),
      $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface'),
      $this->getMock('Drupal\Core\Session\AccountInterface')
    );
    $comment_fields = $comment_manager->getFields('node');
    $this->assertArrayHasKey('field_foobar', $comment_fields);
  }

}
