<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Annotation\TranslationTest.
 */

namespace Drupal\Tests\Core\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Annotation\Translation
 * @group Annotation
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
  protected function setUp() {
    $this->translationManager = $this->getStringTranslationStub();
  }

  /**
   * @covers ::get
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

    $annotation = new Translation($values);

    $this->assertSame($expected, (string) $annotation->get());
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
    $random = $this->randomMachineName();
    $random_html_entity = '&' . $random;
    $data[] = array(
      array(
        'value' => 'Foo @bar @baz %qux',
        'arguments' => array(
          '@bar' => $random,
          '@baz' => $random_html_entity,
          '%qux' => $random_html_entity,
        ),
        'context' => $this->randomMachineName(),
      ),
      'Foo ' . $random . ' &amp;' . $random . ' <em class="placeholder">&amp;' . $random . '</em>',
    );

    return $data;
  }

}
