<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\serialization\Normalizer\MarkupNormalizer;
use Drupal\Tests\serialization\Traits\JsonSchemaTestTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\MarkupNormalizer
 * @group serialization
 */
final class MarkupNormalizerTest extends UnitTestCase {

  use JsonSchemaTestTrait;

  /**
   * The TypedDataNormalizer instance.
   *
   * @var \Drupal\serialization\Normalizer\TypedDataNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalizer = new MarkupNormalizer();
  }

  /**
   * Test the normalizer properly delegates schema discovery to its subject.
   */
  public function testDelegatedSchemaDiscovery(): void {
    $schema = $this->normalizer->getNormalizationSchema(new Attribute(['data-test' => 'testing']));
    $this->assertEquals('Rendered HTML element attributes', $schema['description']);
  }

  /**
   * {@inheritdoc}
   */
  public static function jsonSchemaDataProvider(): array {
    return [
      'markup' => [Markup::create('Generic Markup')],
      'attribute' => [new Attribute(['data-test' => 'testing'])],
    ];
  }

}
