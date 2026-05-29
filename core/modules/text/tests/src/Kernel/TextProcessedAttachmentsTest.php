<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\text\TextProcessed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that TextProcessed::getAttachments() bubbles filter attachments.
 */
#[CoversClass(TextProcessed::class)]
#[Group('text')]
#[RunTestsInSeparateProcesses]
class TextProcessedAttachmentsTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_assets_format',
      'name' => 'Test assets format',
      'filters' => [
        'filter_test_assets' => [
          'status' => TRUE,
        ],
      ],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'formatted_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'formatted_text',
      'label' => 'Formatted text',
    ])->save();
  }

  /**
   * Tests that filter library attachments are available via getAttachments().
   */
  public function testGetAttachmentsContainsFilterLibrary(): void {
    $entity = $this->entityTypeManager
      ->getStorage('entity_test')
      ->create(['name' => $this->randomMachineName()]);
    $entity->formatted_text = [
      'value' => 'Hello, world!',
      'format' => 'test_assets_format',
    ];

    /** @var \Drupal\text\TextProcessed $processed */
    $processed = $entity->get('formatted_text')->get(0)->get('processed');

    $attachments = $processed->getAttachments();
    $this->assertContains('filter/caption', $attachments['library']);
  }

}
