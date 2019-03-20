<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;

/**
 * Asserts external normalizers are handled as expected by the JSON:API module.
 *
 * @see jsonapi.normalizers
 *
 * @group jsonapi
 */
class ExternalNormalizersTest extends BrowserTestBase {

  /**
   * The original value for the test field.
   *
   * @var string
   */
  const VALUE_ORIGINAL = 'Llamas are super awesome!';

  /**
   * The expected overridden value for the test field.
   *
   * @see \Drupal\jsonapi_test_field_type\Normalizer\StringNormalizer
   * @see \Drupal\jsonapi_test_data_type\Normalizer\StringNormalizer
   */
  const VALUE_OVERRIDDEN = 'Llamas are NOT awesome!';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'jsonapi',
    'entity_test',
  ];

  /**
   * The test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // This test is not about access control at all, so allow anonymous users to
    // view and create the test entities.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view test entity')
      ->grantPermission('create entity_test entity_test_with_bundle entities')
      ->save();

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'type' => 'string',
      'entity_type' => 'entity_test',
    ])
      ->save();
    FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])
      ->save();

    $this->entity = EntityTest::create([
      'name' => 'Llama',
      'type' => 'entity_test',
      'field_test' => static::VALUE_ORIGINAL,
    ]);
    $this->entity->save();
  }

  /**
   * Tests a format-agnostic normalizer.
   *
   * @param string $test_module
   *   The test module to install, which comes with a high-priority normalizer.
   * @param string $expected_value_jsonapi_normalization
   *   The expected JSON:API normalization of the tested field. Must be either
   *   - static::VALUE_ORIGINAL (normalizer IS NOT expected to override)
   *   - static::VALUE_OVERRIDDEN (normalizer IS expected to override)
   * @param string $expected_value_jsonapi_denormalization
   *   The expected JSON:API denormalization of the tested field. Must be either
   *   - static::VALUE_OVERRIDDEN (denormalizer IS NOT expected to override)
   *   - static::VALUE_ORIGINAL (denormalizer IS expected to override)
   *
   * @dataProvider providerTestFormatAgnosticNormalizers
   */
  public function testFormatAgnosticNormalizers($test_module, $expected_value_jsonapi_normalization, $expected_value_jsonapi_denormalization) {
    assert(in_array($expected_value_jsonapi_normalization, [static::VALUE_ORIGINAL, static::VALUE_OVERRIDDEN], TRUE));
    assert(in_array($expected_value_jsonapi_denormalization, [static::VALUE_ORIGINAL, static::VALUE_OVERRIDDEN], TRUE));

    // Asserts the entity contains the value we set.
    $this->assertSame(static::VALUE_ORIGINAL, $this->entity->field_test->value);

    // Asserts normalizing the entity using core's 'serializer' service DOES
    // yield the value we set.
    $core_normalization = $this->container->get('serializer')->normalize($this->entity);
    $this->assertSame(static::VALUE_ORIGINAL, $core_normalization['field_test'][0]['value']);

    // Asserts denormalizing the entity using core's 'serializer' service DOES
    // yield the value we set.
    $core_normalization['field_test'][0]['value'] = static::VALUE_OVERRIDDEN;
    $denormalized_entity = $this->container->get('serializer')->denormalize($core_normalization, EntityTest::class, 'json', []);
    $this->assertInstanceOf(EntityTest::class, $denormalized_entity);
    $this->assertSame(static::VALUE_OVERRIDDEN, $denormalized_entity->field_test->value);

    // Install test module that contains a high-priority alternative normalizer.
    $this->container->get('module_installer')->install([$test_module]);
    $this->rebuildContainer();

    // Asserts normalizing the entity using core's 'serializer' service DOES NOT
    // ANYMORE yield the value we set.
    $core_normalization = $this->container->get('serializer')->normalize($this->entity);
    $this->assertSame(static::VALUE_OVERRIDDEN, $core_normalization['field_test'][0]['value']);

    // Asserts denormalizing the entity using core's 'serializer' service DOES
    // NOT ANYMORE yield the value we set.
    $core_normalization = $this->container->get('serializer')->normalize($this->entity);
    $core_normalization['field_test'][0]['value'] = static::VALUE_OVERRIDDEN;
    $denormalized_entity = $this->container->get('serializer')->denormalize($core_normalization, EntityTest::class, 'json', []);
    $this->assertInstanceOf(EntityTest::class, $denormalized_entity);
    $this->assertSame(static::VALUE_ORIGINAL, $denormalized_entity->field_test->value);

    // Asserts the expected JSON:API normalization.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute('jsonapi.entity_test--entity_test.individual', ['entity' => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $client = $this->getSession()->getDriver()->getClient()->getClient();
    $response = $client->request('GET', $url->setAbsolute(TRUE)->toString());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame($expected_value_jsonapi_normalization, $document['data']['attributes']['field_test']);

    // Asserts the expected JSON:API denormalization.
    $request_options = [];
    $request_options[RequestOptions::BODY] = Json::encode([
      'data' => [
        'type' => 'entity_test--entity_test',
        'attributes' => [
          'field_test' => static::VALUE_OVERRIDDEN,
        ],
      ],
    ]);
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $response = $client->request('POST', Url::fromRoute('jsonapi.entity_test--entity_test.collection.post')->setAbsolute(TRUE)->toString(), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame(static::VALUE_OVERRIDDEN, $document['data']['attributes']['field_test']);
    $entity_type_manager = $this->container->get('entity_type.manager');
    $uuid_key = $entity_type_manager->getDefinition('entity_test')->getKey('uuid');
    $entities = $entity_type_manager
      ->getStorage('entity_test')
      ->loadByProperties([$uuid_key => $document['data']['id']]);
    $created_entity = reset($entities);
    $this->assertSame($expected_value_jsonapi_denormalization, $created_entity->field_test->value);
  }

  /**
   * Data provider.
   *
   * @return array
   *   Test cases.
   */
  public function providerTestFormatAgnosticNormalizers() {
    return [
      'Format-agnostic @FieldType-level normalizers SHOULD NOT be able to affect the JSON:API normalization' => [
        'jsonapi_test_field_type',
        // \Drupal\jsonapi_test_field_type\Normalizer\StringNormalizer::normalize()
        static::VALUE_ORIGINAL,
        // \Drupal\jsonapi_test_field_type\Normalizer\StringNormalizer::denormalize()
        static::VALUE_OVERRIDDEN,
      ],
      'Format-agnostic @DataType-level normalizers SHOULD be able to affect the JSON:API normalization' => [
        'jsonapi_test_data_type',
        // \Drupal\jsonapi_test_data_type\Normalizer\StringNormalizer::normalize()
        static::VALUE_OVERRIDDEN,
        // \Drupal\jsonapi_test_data_type\Normalizer\StringNormalizer::denormalize()
        static::VALUE_ORIGINAL,
      ],
    ];
  }

}
