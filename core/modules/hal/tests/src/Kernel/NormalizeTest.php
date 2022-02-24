<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests HAL normalization edge cases for EntityResource.
 *
 * @group hal
 * @group legacy
 */
class NormalizeTest extends NormalizerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'my_text_format',
      'name' => 'My Text Format',
      'filters' => [
        'filter_html' => [
          'module' => 'filter',
          'status' => TRUE,
          'weight' => 10,
          'settings' => [
            'allowed_html' => '<p>',
          ],
        ],
        'filter_autop' => [
          'module' => 'filter',
          'status' => TRUE,
          'weight' => 10,
          'settings' => [],
        ],
      ],
    ])->save();
  }

  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
    $target_entity_de = EntityTest::create((['langcode' => 'de', 'field_test_entity_reference' => NULL]));
    $target_entity_de->save();
    $target_entity_en = EntityTest::create((['langcode' => 'en', 'field_test_entity_reference' => NULL]));
    $target_entity_en->save();

    // Create a German entity.
    $values = [
      'langcode' => 'de',
      'name' => $this->randomMachineName(),
      'field_test_text' => [
        'value' => $this->randomMachineName(),
        'format' => 'my_text_format',
      ],
      'field_test_entity_reference' => [
        'target_id' => $target_entity_de->id(),
      ],
    ];
    // Array of translated values.
    $translation_values = [
      'name' => $this->randomMachineName(),
      'field_test_entity_reference' => [
        'target_id' => $target_entity_en->id(),
      ],
    ];

    $entity = EntityTest::create($values);
    $entity->save();
    // Add an English value for name and entity reference properties.
    $entity->addTranslation('en')->set('name', [0 => ['value' => $translation_values['name']]]);
    $entity->getTranslation('en')->set('field_test_entity_reference', [0 => $translation_values['field_test_entity_reference']]);
    $entity->save();

    $type_uri = Url::fromUri('base:rest/type/entity_test/entity_test', ['absolute' => TRUE])->toString();
    $relation_uri = Url::fromUri('base:rest/relation/entity_test/entity_test/field_test_entity_reference', ['absolute' => TRUE])->toString();

    $expected_array = [
      '_links' => [
        'curies' => [
          [
            'href' => '/relations',
            'name' => 'site',
            'templated' => TRUE,
          ],
        ],
        'self' => [
          'href' => $this->getEntityUri($entity),
        ],
        'type' => [
          'href' => $type_uri,
        ],
        $relation_uri => [
          [
            'href' => $this->getEntityUri($target_entity_de),
            'lang' => 'de',
          ],
          [
            'href' => $this->getEntityUri($target_entity_en),
            'lang' => 'en',
          ],
        ],
      ],
      '_embedded' => [
        $relation_uri => [
          [
            '_links' => [
              'self' => [
                'href' => $this->getEntityUri($target_entity_de),
              ],
              'type' => [
                'href' => $type_uri,
              ],
            ],
            'uuid' => [
              [
                'value' => $target_entity_de->uuid(),
              ],
            ],
            'lang' => 'de',
          ],
          [
            '_links' => [
              'self' => [
                'href' => $this->getEntityUri($target_entity_en),
              ],
              'type' => [
                'href' => $type_uri,
              ],
            ],
            'uuid' => [
              [
                'value' => $target_entity_en->uuid(),
              ],
            ],
            'lang' => 'en',
          ],
        ],
      ],
      'id' => [
        [
          'value' => $entity->id(),
        ],
      ],
      'uuid' => [
        [
          'value' => $entity->uuid(),
        ],
      ],
      'langcode' => [
        [
          'value' => 'de',
        ],
      ],
      'name' => [
        [
          'value' => $values['name'],
          'lang' => 'de',
        ],
        [
          'value' => $translation_values['name'],
          'lang' => 'en',
        ],
      ],
      'field_test_text' => [
        [
          'value' => $values['field_test_text']['value'],
          'format' => $values['field_test_text']['format'],
          'processed' => "<p>{$values['field_test_text']['value']}</p>",
        ],
      ],
    ];

    $normalized = $this->serializer->normalize($entity, $this->format);
    $this->assertEquals($expected_array['_links']['self'], $normalized['_links']['self'], 'self link placed correctly.');
    // @todo Test curies.
    // @todo Test type.
    $this->assertEquals($expected_array['id'], $normalized['id'], 'Internal id is exposed.');
    $this->assertEquals($expected_array['uuid'], $normalized['uuid'], 'Non-translatable fields is normalized.');
    $this->assertEquals($expected_array['name'], $normalized['name'], 'Translatable field with multiple language values is normalized.');
    $this->assertEquals($expected_array['field_test_text'], $normalized['field_test_text'], 'Field with properties is normalized.');
    $this->assertEquals($expected_array['_embedded'][$relation_uri], $normalized['_embedded'][$relation_uri], 'Entity reference field is normalized.');
    $this->assertEquals($expected_array['_links'][$relation_uri], $normalized['_links'][$relation_uri], 'Links are added for entity reference field.');
  }

  /**
   * Constructs the entity URI.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The entity URI.
   */
  protected function getEntityUri(EntityInterface $entity) {
    $url = $entity->toUrl('canonical', ['absolute' => TRUE]);
    return $url->setRouteParameter('_format', 'hal_json')->toString();
  }

}
