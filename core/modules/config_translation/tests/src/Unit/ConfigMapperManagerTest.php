<?php

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigMapperManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\TypedData\DataDefinition;

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
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  protected function setUp() {
    $language = new Language(['id' => 'en']);
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->once())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_INTERFACE)
      ->will($this->returnValue($language));

    $this->typedConfigManager = $this->getMockBuilder('Drupal\Core\Config\TypedConfigManagerInterface')
      ->getMock();

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $theme_handler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');

    $this->configMapperManager = new ConfigMapperManager(
      $this->getMock('Drupal\Core\Cache\CacheBackendInterface'),
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
      ->will($this->returnValue($element));

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
  public function providerTestHasTranslatable() {
    return [
      [$this->getElement([]), FALSE],
      [$this->getElement(['aaa' => 'bbb']), FALSE],
      [$this->getElement(['translatable' => FALSE]), FALSE],
      [$this->getElement(['translatable' => TRUE]), TRUE],
      [$this->getNestedElement([
        $this->getElement([]),
      ]), FALSE],
      [$this->getNestedElement([
        $this->getElement(['translatable' => TRUE]),
      ]), TRUE],
      [$this->getNestedElement([
        $this->getElement(['aaa' => 'bbb']),
        $this->getElement(['ccc' => 'ddd']),
        $this->getElement(['eee' => 'fff']),
      ]), FALSE],
      [$this->getNestedElement([
        $this->getElement(['aaa' => 'bbb']),
        $this->getElement(['ccc' => 'ddd']),
        $this->getElement(['translatable' => TRUE]),
      ]), TRUE],
      [$this->getNestedElement([
        $this->getElement(['aaa' => 'bbb']),
        $this->getNestedElement([
          $this->getElement(['ccc' => 'ddd']),
          $this->getElement(['eee' => 'fff']),
        ]),
        $this->getNestedElement([
          $this->getElement(['ggg' => 'hhh']),
          $this->getElement(['iii' => 'jjj']),
        ]),
      ]), FALSE],
      [$this->getNestedElement([
        $this->getElement(['aaa' => 'bbb']),
        $this->getNestedElement([
          $this->getElement(['ccc' => 'ddd']),
          $this->getElement(['eee' => 'fff']),
        ]),
        $this->getNestedElement([
          $this->getElement(['ggg' => 'hhh']),
          $this->getElement(['translatable' => TRUE]),
        ]),
      ]), TRUE],
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
  protected function getElement(array $definition) {
    $data_definition = new DataDefinition($definition);
    $element = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');
    $element->expects($this->any())
      ->method('getDataDefinition')
      ->will($this->returnValue($data_definition));
    return $element;
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
  protected function getNestedElement(array $elements) {
    // ConfigMapperManager::findTranslatable() checks for
    // \Drupal\Core\TypedData\TraversableTypedDataInterface, but mocking that
    // directly does not work, because we need to implement \IteratorAggregate
    // in order for getIterator() to be called. Therefore we need to mock
    // \Drupal\Core\Config\Schema\ArrayElement, but that is abstract, so we
    // need to mock one of the subclasses of it.
    $nested_element = $this->getMockBuilder('Drupal\Core\Config\Schema\Mapping')
      ->disableOriginalConstructor()
      ->getMock();
    $nested_element->expects($this->once())
      ->method('getIterator')
      ->will($this->returnValue(new \ArrayIterator($elements)));
    return $nested_element;
  }

}
