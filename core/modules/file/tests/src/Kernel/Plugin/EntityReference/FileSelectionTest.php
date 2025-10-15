<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\EntityReference;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests file selection plugin.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class FileSelectionTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'file', 'user', 'field'];

  /**
   * The selection handler.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  protected SelectionInterface $selectionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');

    $field_name = $this->randomMachineName();
    $this->createEntityReferenceField('entity_test', 'entity_test', $field_name, $this->randomString(), 'file');
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $field_name);
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);
  }

  /**
   * Tests that entity reference should refer permanent files.
   */
  public function testCanReferToPermanentFiles(): void {
    $expected_result = [];
    \file_put_contents('public://file.png', str_repeat('a', 10));
    $file = File::create([
      'uri' => 'public://file.png',
      'filename' => 'file.png',
      'label' => $this->randomMachineName(),
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();
    $expected_result[$file->id()] = Html::escape($file->label());

    // Assert the result.
    $result = $this->selectionHandler->getReferenceableEntities();
    self::assertEquals($expected_result, $result['file'], 'Permanent files can be referred by handler.');
  }

  /**
   * Tests that entity reference should avoid referring to temporary files.
   */
  public function testCanNotReferToTemporaryFiles(): void {
    \file_put_contents('public://test.png', str_repeat('b', 10));
    $file = File::create([
      'uri' => 'public://test.png',
      'filename' => 'test.png',
      'label' => $this->randomMachineName(),
    ]);
    $file->save();

    // Assert the result.
    $result = $this->selectionHandler->getReferenceableEntities();
    self::assertEmpty($result, 'Temporary files can not be referred by handler.');
  }

}
