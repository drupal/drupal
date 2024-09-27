<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the string field formatter.
 *
 * @group field
 *
 * @coversDefaultClass \Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter
 */
final class StringFormatterTest extends UnitTestCase {

  /**
   * Checks link visibility depending on link templates and access.
   *
   * @param bool $hasUrl
   *   Whether the entity type has a canonical link template.
   * @param string|null $accessClass
   *   The access result for the current user.
   * @param bool $expectIsLinkElement
   *   Whether to expect the text to be wrapped in a link element.
   *
   * @phpstan-param class-string<\Drupal\Core\Access\AccessResultInterface>|null $accessClass
   *
   * @dataProvider providerAccessLinkToEntity
   */
  public function testLinkToEntity(bool $hasUrl, ?string $accessClass, bool $expectIsLinkElement): void {
    $fieldDefinition = $this->prophesize(FieldDefinitionInterface::class);
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $fieldFormatter = new StringFormatter('foobar', [], $fieldDefinition->reveal(), [], 'TestLabel', 'default', [], $entityTypeManager->reveal());
    $fieldFormatter->setSetting('link_to_entity', TRUE);

    $entityType = $this->prophesize(EntityTypeInterface::class);
    $entityType->hasLinkTemplate('canonical')->willReturn($hasUrl)->shouldBeCalledTimes(1);
    $entityType->hasLinkTemplate('revision')->willReturn(FALSE)->shouldBeCalledTimes($hasUrl ? 1 : 0);

    $entity = $this->prophesize(EntityInterface::class);
    $entity->isNew()->willReturn(FALSE);
    $entity->getEntityType()->willReturn($entityType->reveal());
    if ($hasUrl) {
      $url = $this->prophesize(Url::class);
      $url->access(NULL, TRUE)->willReturn(new $accessClass());
      $entity->toUrl('canonical')->willReturn($url);
    }

    $item = $this->getMockBuilder(StringItem::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
    $item->setValue(['value' => 'FooText']);

    $items = $this->prophesize(FieldItemListInterface::class);
    $items->getEntity()->willReturn($entity->reveal());
    $items->valid()->willReturn(TRUE, FALSE);
    $items->next();
    $items->rewind();
    $items->current()->willReturn($item);
    $items->key()->willReturn(0);

    $elements = $fieldFormatter->viewElements($items->reveal(), 'en');
    if ($expectIsLinkElement) {
      $this->assertEquals('link', $elements[0]['#type']);
      $this->assertEquals('FooText', $elements[0]['#title']['#context']['value']);
    }
    else {
      $this->assertEquals('inline_template', $elements[0]['#type']);
      $this->assertEquals('FooText', $elements[0]['#context']['value']);
    }
  }

  /**
   * Data provider.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public static function providerAccessLinkToEntity(): \Generator {
    yield 'entity with no URL' => [
      FALSE,
      NULL,
      FALSE,
    ];
    yield 'entity with url, with access' => [
      TRUE,
      AccessResultAllowed::class,
      TRUE,
    ];
    yield 'entity with url, no access' => [
      TRUE,
      AccessResultForbidden::class,
      FALSE,
    ];
  }

}
