<?php

namespace Drupal\Tests\link\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Field Formatter for the link field type.
 *
 * @group link
 */
class LinkFormatterTest extends UnitTestCase {

  /**
   * Tests when LinkItem::getUrl with malformed URL renders empty link.
   *
   * LinkItem::getUrl will throw \InvalidArgumentException.
   */
  public function testFormatterLinkItemUrlMalformed() {
    $entity = $this->createMock(EntityInterface::class);

    $linkItem = $this->createMock(LinkItemInterface::class);
    $exception = new \InvalidArgumentException();
    $linkItem->expects($this->any())
      ->method('getParent')
      ->willReturn($entity);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willThrowException($exception);
    $linkItem->expects($this->any())
      ->method('__get')
      ->with('options')
      ->willReturn([]);
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldList = new FieldItemList($fieldDefinition, '', $linkItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->will($this->returnValue($linkItem));
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

    $pathValidator = $this->createMock(PathValidatorInterface::class);
    $linkFormatter = new LinkFormatter('', [], $fieldDefinition, [], '', '', [], $pathValidator);
    $elements = $linkFormatter->viewElements($fieldList, 'es');
    $this->assertEquals('link', $elements[0]['#type']);
  }

  /**
   * Tests when LinkItem::getUrl throws an unexpected exception.
   */
  public function testFormatterLinkItemUrlUnexpectedException() {
    $exception = new \Exception('Unexpected!!!');

    $linkItem = $this->createMock(LinkItemInterface::class);
    $entity = $this->createMock(EntityInterface::class);
    $linkItem->expects($this->any())
      ->method('getParent')
      ->willReturn($entity);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willThrowException($exception);
    $linkItem->expects($this->any())
      ->method('__get')
      ->with('options')
      ->willReturn([]);
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldList = new FieldItemList($fieldDefinition, '', $linkItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->will($this->returnValue($linkItem));
    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager);
    \Drupal::setContainer($container);
    $fieldList->setValue([$linkItem]);

    $pathValidator = $this->createMock(PathValidatorInterface::class);
    $linkFormatter = new LinkFormatter('', [], $fieldDefinition, [], '', '', [], $pathValidator);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unexpected!!!');
    $linkFormatter->viewElements($fieldList, 'fr');
  }

  /**
   * Tests when LinkItem::getUrl returns a functional URL.
   */
  public function testFormatterLinkItem() {
    $expectedUrl = Url::fromUri('route:<front>');

    $linkItem = $this->createMock(LinkItemInterface::class);
    $entity = $this->createMock(EntityInterface::class);
    $linkItem->expects($this->any())
      ->method('getParent')
      ->willReturn($entity);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willReturn($expectedUrl);
    $linkItem->expects($this->any())
      ->method('__get')
      ->with('options')
      ->willReturn([]);
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldList = new FieldItemList($fieldDefinition, '', $linkItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->will($this->returnValue($linkItem));
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

    $pathValidator = $this->createMock(PathValidatorInterface::class);
    $linkFormatter = new LinkFormatter('', [], $fieldDefinition, [], '', '', [], $pathValidator);
    $elements = $linkFormatter->viewElements($fieldList, 'zh');
    $this->assertEquals([
      [
        '#type' => 'link',
        '#title' => 'http://example.com',
        '#options' => [],
        '#url' => $expectedUrl,
      ],
    ], $elements);
  }

}
