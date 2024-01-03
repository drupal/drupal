<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigMapperManager;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\TypedData\DataDefinition;
use Prophecy\Prophet;

/**
 * Tests the functionality provided by configuration translation mapper manager.
 *
 * @group config_translation
 */
class ConfigMapperManagerTest extends UnitTestCase {

  /**
   * The configuration mapper manager to test.
   *
   * @var \Drupal\config_translation\ConfigMapperManager
   */
  protected $configMapperManager;

  /**
   * The typed configuration manager used for testing.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $language = new Language(['id' => 'en']);
    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->once())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_INTERFACE)
      ->willReturn($language);

    $this->typedConfigManager = $this->getMockBuilder('Drupal\Core\Config\TypedConfigManagerInterface')
      ->getMock();

    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $theme_handler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');

    $this->configMapperManager = new ConfigMapperManager(
      $this->createMock('Drupal\Core\Cache\CacheBackendInterface'),
      $language_manager,
      $module_handler,
      $this->typedConfigManager,
      $theme_handler
    );
  }

  /**
   * Tests ConfigMapperManager::hasTranslatable().
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   The schema element to test.
   * @param bool $expected
   *   The expected return value of ConfigMapperManager::hasTranslatable().
   *
   * @dataProvider providerTestHasTranslatable
   */
  public function testHasTranslatable(TypedDataInterface $element, $expected) {
    $this->typedConfigManager
      ->expects($this->once())
      ->method('get')
      ->with('test')
      ->willReturn($element);

    $result = $this->configMapperManager->hasTranslatable('test');
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for ConfigMapperManager::testHasTranslatable()
   *
   * @return array
   *   An array of arrays, where each inner array contains the schema element
   *   to test as the first key and the expected result of
   *   ConfigMapperManager::hasTranslatable() as the second key.
   */
  public static function providerTestHasTranslatable() {
    return [
      [static::getElement([]), FALSE],
      [static::getElement(['aaa' => 'bbb']), FALSE],
      [static::getElement(['translatable' => FALSE]), FALSE],
      [static::getElement(['translatable' => TRUE]), TRUE],
      [static::getNestedElement([static::getElement([])]), FALSE],
      [static::getNestedElement([static::getElement(['translatable' => TRUE])]), TRUE],
      [
        static::getNestedElement([
          static::getElement(['aaa' => 'bbb']),
          static::getElement(['ccc' => 'ddd']),
          static::getElement(['eee' => 'fff']),
        ]),
        FALSE,
      ],
      [
        static::getNestedElement([
          static::getElement(['aaa' => 'bbb']),
          static::getElement(['ccc' => 'ddd']),
          static::getElement(['translatable' => TRUE]),
        ]),
        TRUE,
      ],
      [
        static::getNestedElement([
          static::getElement(['aaa' => 'bbb']),
          static::getNestedElement([
            static::getElement(['ccc' => 'ddd']),
            static::getElement(['eee' => 'fff']),
          ]),
          static::getNestedElement([
            static::getElement(['ggg' => 'hhh']),
            static::getElement(['iii' => 'jjj']),
          ]),
        ]),
        FALSE,
      ],
      [
        static::getNestedElement([
          static::getElement(['aaa' => 'bbb']),
          static::getNestedElement([
            static::getElement(['ccc' => 'ddd']),
            static::getElement(['eee' => 'fff']),
          ]),
          static::getNestedElement([
            static::getElement(['ggg' => 'hhh']),
            static::getElement(['translatable' => TRUE]),
          ]),
        ]),
        TRUE,
      ],
    ];
  }

  /**
   * Returns a mocked schema element.
   *
   * @param array $definition
   *   The definition of the schema element.
   *
   * @return \Drupal\Core\Config\Schema\Element
   *   The mocked schema element.
   */
  protected static function getElement(array $definition) {
    $data_definition = new DataDefinition($definition);
    $element = (new Prophet())->prophesize(TypedDataInterface::class);
    $element->getDataDefinition()->willReturn($data_definition);
    return $element->reveal();
  }

  /**
   * Returns a mocked nested schema element.
   *
   * @param array $elements
   *   An array of simple schema elements.
   *
   * @return \Drupal\Core\Config\Schema\Mapping
   *   A nested schema element, containing the passed-in elements.
   */
  protected static function getNestedElement(array $elements) {
    // ConfigMapperManager::findTranslatable() checks for
    // \Drupal\Core\TypedData\TraversableTypedDataInterface, but mocking that
    // directly does not work, because we need to implement \IteratorAggregate
    // in order for getIterator() to be called. Therefore we need to mock
    // \Drupal\Core\Config\Schema\ArrayElement, but that is abstract, so we
    // need to mock one of the subclasses of it.
    $nested_element = (new Prophet())->prophesize(Mapping::class);
    $nested_element->getIterator()->shouldBeCalledTimes(1)->willReturn(new \ArrayIterator($elements));
    return $nested_element->reveal();
  }

}
