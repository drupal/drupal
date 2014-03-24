<?php

/**
 * @file Contains \Drupal\Tests\Core\Annotation\TranslationTest.
 */

namespace Drupal\Tests\Core\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Translation annotation.
 *
 * @covers \Drupal\Core\Annotation\Translation
 */
class TranslationTest extends UnitTestCase {

  /**
   * The translation manager used for testing.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Annotation\Translation unit test',
      'group' => 'System',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->translationManager = $this->getStringTranslationStub();
  }

  /**
   * @covers \Drupal\Core\Annotation\Translation::get()
   *
   * @dataProvider providerTestGet
   */
  public function testGet(array $values, $expected) {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translationManager);
    \Drupal::setContainer($container);

    $arguments = isset($values['arguments']) ? $values['arguments'] : array();
    $options = isset($values['context']) ? array(
      'context' => $values['context'],
    ) : array();
    $this->translationManager->expects($this->once())
      ->method('translate')
      ->with($values['value'], $arguments, $options);

    $annotation = new Translation($values);

    $this->assertSame($expected, $annotation->get());
  }

  /**
   * Provides data to self::testGet().
   */
  public function providerTestGet() {
    $data = array();
    $data[] = array(
      array(
        'value' => 'Foo',
      ),
      'Foo'
    );
    $random = $this->randomName();
    $random_html_entity = '&' . $random;
    $data[] = array(
      array(
        'value' => 'Foo !bar @baz %qux',
        'arguments' => array(
          '!bar' => $random,
          '@baz' => $random_html_entity,
          '%qux' => $random_html_entity,
        ),
        'context' => $this->randomName(),
      ),
      'Foo ' . $random . ' &amp;' . $random . ' <em class="placeholder">&amp;' . $random . '</em>',
    );

    return $data;
  }

}
