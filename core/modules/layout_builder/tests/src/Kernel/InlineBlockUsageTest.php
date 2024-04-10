<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\InlineBlockUsageInterface;

/**
 * Class for testing the InlineBlockUsage service.
 *
 * @coversDefaultClass \Drupal\layout_builder\InlineBlockUsage
 *
 * @group layout_builder
 */
class InlineBlockUsageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'entity_test',
    'user',
  ];

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The inline block usage service.
   */
  protected InlineBlockUsageInterface $inlineBlockUsage;

  /**
   * The entity for testing.
   */
  protected EntityTest $entity;

  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->container->get('database');
    $this->inlineBlockUsage = $this->container->get('inline_block.usage');
    $this->installSchema('layout_builder', ['inline_block_usage']);
    entity_test_create_bundle('bundle_with_extra_fields');
    $this->installEntitySchema('entity_test');
    $this->entity = EntityTest::create();
    $this->entity->save();
  }

  /**
   * Covers ::addUsage.
   */
  public function testAddUsage(): void {
    $this->inlineBlockUsage->addUsage('1', $this->entity);
    $results = $this->database->select('inline_block_usage')
      ->fields('inline_block_usage')
      ->condition('block_content_id', 1)
      ->condition('layout_entity_id', $this->entity->id())
      ->condition('layout_entity_type', $this->entity->getEntityTypeId())
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $results);
  }

  /**
   * Covers ::getUnused.
   */
  public function testGetUnused(): void {
    // Add a valid usage.
    $this->inlineBlockUsage->addUsage('1', $this->entity);
    $this->assertEmpty($this->inlineBlockUsage->getUnused());
    // Add an invalid usage.
    $this->database->merge('inline_block_usage')
      ->keys([
        'block_content_id' => 2,
        'layout_entity_id' => NULL,
        'layout_entity_type' => NULL,
      ])->execute();
    $this->assertCount(1, $this->inlineBlockUsage->getUnused());
  }

  /**
   * Covers ::removeByLayoutEntity.
   */
  public function testRemoveByLayoutEntity(): void {
    $this->inlineBlockUsage->addUsage('1', $this->entity);
    $this->inlineBlockUsage->removeByLayoutEntity($this->entity);
    $results = $this->database->select('inline_block_usage')
      ->fields('inline_block_usage')
      ->condition('block_content_id', '1')
      ->isNull('layout_entity_id')
      ->isNull('layout_entity_type')
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $results);
  }

  /**
   * Covers ::deleteUsage.
   */
  public function testDeleteUsage(): void {
    $this->inlineBlockUsage->addUsage('1', $this->entity);
    $this->inlineBlockUsage->deleteUsage(['1']);
    $results = $this->database->select('inline_block_usage')
      ->fields('inline_block_usage')
      ->condition('block_content_id', 1)
      ->condition('layout_entity_id', $this->entity->id())
      ->condition('layout_entity_type', $this->entity->getEntityTypeId())
      ->execute()
      ->fetchAll();
    $this->assertEmpty($results);
  }

  /**
   * Covers ::getUsage.
   */
  public function testGetUsage(): void {
    $this->inlineBlockUsage->addUsage('1', $this->entity);
    $result = $this->inlineBlockUsage->getUsage('1');
    $this->assertEquals($this->entity->id(), $result->layout_entity_id);
    $this->assertEquals($this->entity->getEntityTypeId(), $result->layout_entity_type);
  }

}
