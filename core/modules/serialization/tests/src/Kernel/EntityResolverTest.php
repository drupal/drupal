<?php

namespace Drupal\Tests\serialization\Kernel;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests that entities references can be resolved.
 *
 * @group serialization
 */
class EntityResolverTest extends NormalizerTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['hal', 'rest'];

  /**
   * The format being tested.
   *
   * @var string
   */
  protected $format = 'hal_json';

  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();

    // Create the test field storage.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_entity_reference',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test_mulrev',
      ],
    ])->save();

    // Create the test field.
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_entity_reference',
      'bundle' => 'entity_test_mulrev',
    ])->save();
  }

  /**
   * Test that fields referencing UUIDs can be denormalized.
   */
  public function testUuidEntityResolver() {
    // Create an entity to get the UUID from.
    $entity = EntityTestMulRev::create(['type' => 'entity_test_mulrev']);
    $entity->set('name', 'foobar');
    $entity->set('field_test_entity_reference', [['target_id' => 1]]);
    $entity->save();

    $field_uri = Url::fromUri('base:rest/relation/entity_test_mulrev/entity_test_mulrev/field_test_entity_reference', ['absolute' => TRUE])->toString();

    $data = [
      '_links' => [
        'type' => [
          'href' => Url::fromUri('base:rest/type/entity_test_mulrev/entity_test_mulrev', ['absolute' => TRUE])->toString(),
        ],
        $field_uri => [
          [
            'href' => $entity->toUrl()->toString(),
          ],
        ],
      ],
      '_embedded' => [
        $field_uri => [
          [
            '_links' => [
              'self' => $entity->toUrl()->toString(),
            ],
            'uuid' => [
              [
                'value' => $entity->uuid(),
              ],
            ],
          ],
        ],
      ],
    ];

    $denormalized = $this->container->get('serializer')->denormalize($data, 'Drupal\entity_test\Entity\EntityTestMulRev', $this->format);
    $field_value = $denormalized->get('field_test_entity_reference')->getValue();
    $this->assertEqual($field_value[0]['target_id'], 1, 'Entity reference resolved using UUID.');
  }

}
