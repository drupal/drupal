<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\jsonapi\Normalizer\FieldItemNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\FieldItemNormalizer
 * @group jsonapi
 *
 * @internal
 */
class FieldItemNormalizerTest extends JsonapiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'link',
    'entity_test',
    'serialization',
  ];

  /**
   * The normalizer.
   *
   * @var \Drupal\jsonapi\Normalizer\FieldItemNormalizer
   */
  private $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $etm = $this->container->get('entity_type.manager');
    $this->normalizer = new FieldItemNormalizer($etm);
    $this->normalizer->setSerializer($this->container->get('jsonapi.serializer'));

    $definitions = [];
    $definitions['links'] = BaseFieldDefinition::create('link')->setLabel('Links');
    $definitions['internal_property_value'] = BaseFieldDefinition::create('single_internal_property_test')->setLabel('Internal property');
    $definitions['no_main_property_value'] = BaseFieldDefinition::create('map')->setLabel('No main property');
    $this->container->get('state')->set('entity_test.additional_base_field_definitions', $definitions);
    $etm->clearCachedDefinitions();
  }

  /**
   * Tests a field item that has no properties.
   *
   * @covers ::normalize
   */
  public function testNormalizeFieldItemWithoutProperties(): void {
    $item = $this->prophesize(FieldItemInterface::class);
    $item->getProperties(TRUE)->willReturn([]);
    $item->getValue()->willReturn('Direct call to getValue');

    $result = $this->normalizer->normalize($item->reveal(), 'api_json');
    assert($result instanceof CacheableNormalization);
    $this->assertSame('Direct call to getValue', $result->getNormalization());
  }

  /**
   * Tests normalizing field item.
   */
  public function testNormalizeFieldItem(): void {
    $entity = EntityTest::create([
      'name' => 'Test entity',
      'links' => [
        [
          'uri' => 'https://www.drupal.org',
          'title' => 'Drupal.org',
          'options' => [
            'query' => 'foo=bar',
          ],
        ],
      ],
      'internal_property_value' => [
        [
          'value' => 'Internal property testing!',
        ],
      ],
      'no_main_property_value' => [
        [
          'value' => 'No main property testing!',
        ],
      ],
    ]);

    // Verify a field with one property is flattened.
    $result = $this->normalizer->normalize($entity->get('name')->first());
    assert($result instanceof CacheableNormalization);
    $this->assertEquals('Test entity', $result->getNormalization());

    // Verify a field with multiple public properties has all of them returned.
    $result = $this->normalizer->normalize($entity->get('links')->first());
    assert($result instanceof CacheableNormalization);
    $this->assertEquals([
      'uri' => 'https://www.drupal.org',
      'title' => 'Drupal.org',
      'options' => [
        'query' => 'foo=bar',
      ],
    ], $result->getNormalization());

    // Verify a field with one public property and one internal only returns the
    // public property, and is flattened.
    $result = $this->normalizer->normalize($entity->get('internal_property_value')->first());
    assert($result instanceof CacheableNormalization);
    // Property `internal_value` will not exist.
    $this->assertEquals('Internal property testing!', $result->getNormalization());

    // Verify a field with one public property but no main property is not
    // flattened.
    $result = $this->normalizer->normalize($entity->get('no_main_property_value')->first());
    assert($result instanceof CacheableNormalization);
    $this->assertEquals([
      'value' => 'No main property testing!',
    ], $result->getNormalization());
  }

}
