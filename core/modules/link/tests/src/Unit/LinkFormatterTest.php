<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Field Formatter for the link field type.
 */
#[Group('link')]
class LinkFormatterTest extends UnitTestCase {

  /**
   * Tests when LinkItem::getUrl with malformed URL renders empty link.
   *
   * LinkItem::getUrl will throw \InvalidArgumentException.
   */
  public function testFormatterLinkItemUrlMalformed(): void {
    $linkItem = $this->createMock(LinkItemInterface::class);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willThrowException(new \InvalidArgumentException());
    $fieldDefinition = $this->createStub(FieldDefinitionInterface::class);
    $fieldList = new FieldItemList($fieldDefinition, '', $linkItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->willReturn($linkItem);
    $urlGenerator = $this->createMock(UrlGenerator::class);
    $urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('<none>', [], [], FALSE)
      ->willReturn('http://example.com');
    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager);
    $container->set('url_generator', $urlGenerator);
    \Drupal::setContainer($container);
    $fieldList->setValue([$linkItem]);

    $pathValidator = $this->createStub(PathValidatorInterface::class);
    $linkFormatter = new LinkFormatter('', [], $fieldDefinition, [], '', '', [], $pathValidator);
    $elements = $linkFormatter->viewElements($fieldList, 'es');
    $this->assertEquals('link', $elements[0]['#type']);
  }

  /**
   * Tests when LinkItem::getUrl throws an unexpected exception.
   */
  public function testFormatterLinkItemUrlUnexpectedException(): void {
    $linkItem = $this->createMock(LinkItemInterface::class);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willThrowException(new \Exception('Unexpected!!!'));
    $fieldDefinition = $this->createStub(FieldDefinitionInterface::class);
    $fieldList = new FieldItemList($fieldDefinition, '', $linkItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->willReturn($linkItem);
    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager);
    \Drupal::setContainer($container);
    $fieldList->setValue([$linkItem]);

    $pathValidator = $this->createStub(PathValidatorInterface::class);
    $linkFormatter = new LinkFormatter('', [], $fieldDefinition, [], '', '', [], $pathValidator);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessageIs('Unexpected!!!');
    $linkFormatter->viewElements($fieldList, 'fr');
  }

  /**
   * Tests when LinkItem::getUrl returns a functional URL.
   */
  public function testFormatterLinkItem(): void {
    $expectedUrl = Url::fromUri('route:<front>');

    $linkItem = $this->createMock(LinkItemInterface::class);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willReturn($expectedUrl);
    $fieldDefinition = $this->createStub(FieldDefinitionInterface::class);
    $fieldList = new FieldItemList($fieldDefinition, '', $linkItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->willReturn($linkItem);
    $urlGenerator = $this->createMock(UrlGenerator::class);
    $urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('<front>', [], [], FALSE)
      ->willReturn('http://example.com');
    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager);
    $container->set('url_generator', $urlGenerator);
    \Drupal::setContainer($container);
    $fieldList->setValue([$linkItem]);

    $pathValidator = $this->createStub(PathValidatorInterface::class);
    $linkFormatter = new LinkFormatter('', [], $fieldDefinition, [], '', '', [], $pathValidator);
    $elements = $linkFormatter->viewElements($fieldList, 'zh');
    $this->assertEquals([
      [
        '#type' => 'link',
        '#title' => 'http://example.com',
        '#url' => $expectedUrl,
      ],
    ], $elements);
  }

  /**
   * Tests settings summary messages.
   */
  #[DataProvider('providerSettingsSummary')]
  public function testSettingsSummary(array $settings, array $expected_summaries): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $mock = $this->getMockBuilder(LinkFormatter::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getPluginId', 'getSettings'])
      ->getMock();
    $mock->expects($this->once())
      ->method('getPluginId')
      ->willReturn('link');
    $mock->expects($this->once())
      ->method('getSettings')
      ->willReturn($settings);

    $summaries = $mock->settingsSummary();
    // Summaries are translated so cast them to strings before comparison.
    foreach ($summaries as $key => $summary) {
      $summaries[$key] = (string) $summary;
    }
    $this->assertEqualsCanonicalizing($expected_summaries, $summaries);
  }

  /**
   * Provides test cases for ::testSettingsSummary().
   */
  public static function providerSettingsSummary(): array {
    return [
      [
        [],
        [
          'Link text not trimmed',
        ],
      ],
      [
        [
          'trim_length' => 80,
        ],
        [
          'Link text trimmed to 80 characters',
        ],
      ],
      [
        [
          'trim_length' => 20,
        ],
        [
          'Link text trimmed to 20 characters',
        ],
      ],
      [
        [
          'trim_length' => 20,
          'url_only' => TRUE,
          'url_plain' => TRUE,
        ],
        [
          'Link text not trimmed',
          'Show URL only as plain-text',
        ],
      ],
      [
        [
          'trim_length' => 20,
          'url_only' => TRUE,
          'url_plain' => FALSE,
        ],
        [
          'Link text not trimmed',
          'Show URL only',
        ],
      ],
      [
        [
          'rel' => 'nofollow',
        ],
        [
          'Link text not trimmed',
          'Add rel="nofollow"',
        ],
      ],
      [
        [
          'target' => '_blank',
        ],
        [
          'Link text not trimmed',
          'Open link in new window',
        ],
      ],
    ];
  }

}
