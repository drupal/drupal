<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Unit;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\text\Plugin\Field\FieldType\TextItem;
use Drupal\text\TextProcessed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests TextProcessed with AttachmentsInterface.
 */
#[CoversClass(TextProcessed::class)]
#[Group('text')]
class TextProcessedTest extends UnitTestCase {

  /**
   * Tests addAttachments() merges attachments correctly.
   */
  public function testAttachments(): void {
    $definition = $this->createMock(DataDefinitionInterface::class);
    $definition->expects($this->atLeastOnce())
      ->method('getSetting')
      ->with('text source')
      ->willReturn('value');

    $parent = $this->createStub(TextItem::class);
    $parent->method('__get')
      ->willReturnMap([
        ['value', ''],
        ['format', 'basic_html'],
      ]);
    $parent->method('getLangcode')->willReturn('en');

    $processed = new TextProcessed($definition, 'formatted_text', $parent);

    // Set initial attachments.
    $processed->setAttachments([
      'library' => ['core/drupal'],
    ]);

    // Add more attachments.
    $result = $processed->addAttachments([
      'library' => ['core/jquery'],
      'drupalSettings' => ['foo' => 'bar'],
    ]);

    $this->assertSame($processed, $result);

    $attachments = $processed->getAttachments();
    $this->assertContains('core/drupal', $attachments['library']);
    $this->assertContains('core/jquery', $attachments['library']);
    $this->assertSame(['foo' => 'bar'], $attachments['drupalSettings']);
  }

}
