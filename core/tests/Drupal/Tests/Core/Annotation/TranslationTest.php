<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;

/**
 * @coversDefaultClass \Drupal\Core\Annotation\Translation
 * @group Annotation
 */
class TranslationTest extends UnitTestCase {

  /**
   * The translation manager used for testing.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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

    $annotation = new Translation($values);

    $this->assertSame($expected, (string) $annotation->get());
  }

  /**
   * Provides data to self::testGet().
   */
  public static function providerTestGet() {
    $data = [];
    $data[] = [
      [
        'value' => 'Foo',
      ],
      'Foo',
    ];
    $random = Random::machineName();
    $random_html_entity = '&' . $random;
    $data[] = [
      [
        'value' => 'Foo @bar @baz %qux',
        'arguments' => [
          '@bar' => $random,
          '@baz' => $random_html_entity,
          '%qux' => $random_html_entity,
        ],
        'context' => Random::machineName(),
      ],
      'Foo ' . $random . ' &amp;' . $random . ' <em class="placeholder">&amp;' . $random . '</em>',
    ];

    return $data;
  }

}
