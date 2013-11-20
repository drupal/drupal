<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\MigrateExecutableTest.
 */

namespace Drupal\migrate\Tests;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;

/**
 * Tests the migrate executable.
 *
 * @group Drupal
 * @group migrate
 *
 * @covers \Drupal\migrate\Tests\MigrateExecutableTest
 */
class MigrateExecutableTest extends MigrateTestCase {

  /**
   * The mocked migration entity.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $migration;

  /**
   * The mocked migrate message.
   *
   * @var \Drupal\migrate\MigrateMessageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $message;

  /**
   * The tested migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutable
   */
  protected $executable;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Migrate executable',
      'description' => 'Tests the migrate executable.',
      'group' => 'Migrate',
    );
  }

  protected function setUp() {
    $this->migration = $this->getMock('Drupal\migrate\Entity\MigrationInterface');
    $this->message = $this->getMock('Drupal\migrate\MigrateMessageInterface');
    $id_map = $this->getMock('Drupal\migrate\Plugin\MigrateIdMapInterface');

    $this->migration->expects($this->any())
      ->method('getIdMap')
      ->will($this->returnValue($id_map));

    $this->executable = new TestMigrateExecutable($this->migration, $this->message);
    $this->executable->setTranslationManager($this->getStringTranslationStub());
  }

  /**
   * Tests an import with an incomplete rewinding.
   */
  public function testImportWithFailingRewind() {
    $iterator = $this->getMock('\Iterator');
    $iterator->expects($this->once())
      ->method('valid')
      ->will($this->returnCallback(function() {
        throw new \Exception('invalid source iteration');
      }));
    $source = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $source->expects($this->any())
      ->method('getIterator')
      ->will($this->returnValue($iterator));

    $this->migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($source));

    // Ensure that a message with the proper message was added.
    $this->message->expects($this->once())
      ->method('display')
      ->with('Migration failed with source plugin exception: invalid source iteration');

    $result = $this->executable->import();
    $this->assertEquals(MigrationInterface::RESULT_FAILED, $result);
  }

}

class TestMigrateExecutable extends MigrateExecutable {

  public function setTranslationManager(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
  }
}
