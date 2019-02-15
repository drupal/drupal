<?php

namespace Drupal\Tests\datetime\Functional\EntityResource\EntityTest;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use GuzzleHttp\RequestOptions;

/**
 * Tests the datetime field constraint with 'datetime' items.
 *
 * @group datetime
 */
class EntityTestDatetimeTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * The ISO date string to use throughout the test.
   *
   * @var string
   */
  protected static $dateString = '2017-03-01T20:02:00';

  /**
   * Datetime test field name.
   *
   * @var string
   */
  protected static $fieldName = 'field_datetime';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['datetime', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add datetime field.
    FieldStorageConfig::create([
      'field_name' => static::$fieldName,
      'type' => 'datetime',
      'entity_type' => static::$entityTypeId,
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ])
      ->save();

    FieldConfig::create([
      'field_name' => static::$fieldName,
      'entity_type' => static::$entityTypeId,
      'bundle' => $this->entity->bundle(),
      'settings' => ['default_value' => static::$dateString],
    ])
      ->save();

    // Reload entity so that it has the new field.
    $this->entity = $this->entityStorage->load($this->entity->id());
    $this->entity->set(static::$fieldName, ['value' => static::$dateString]);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity_test = EntityTest::create([
      'name' => 'Llama',
      'type' => static::$entityTypeId,
      static::$fieldName => static::$dateString,
    ]);
    $entity_test->setOwnerId(0);
    $entity_test->save();

    return $entity_test;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return parent::getExpectedNormalizedEntity() + [
      static::$fieldName => [
        [
          'value' => '2017-03-02T07:02:00+11:00',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return parent::getNormalizedPostEntity() + [
      static::$fieldName => [
        [
          'value' => static::$dateString . '+00:00',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPatchEntity() {
    return parent::getNormalizedPostEntity() + [
      static::$fieldName => [
        [
          // Omitting the timezone is allowed, this should result in the site's
          // timezone being used automatically. This does not make sense, but
          // it's how it functioned in the past, so we explicitly test this to
          // guarantee backward compatibility. ::getNormalizedPostEntity() tests
          // the recommended case, this tests backward compatibility.
          'value' => static::$dateString,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {
    parent::assertNormalizationEdgeCases($method, $url, $request_options);

    if ($this->entity->getEntityType()->hasKey('bundle')) {
      $fieldName = static::$fieldName;

      // DX: 422 when date type is incorrect.
      $normalization = $this->getNormalizedPostEntity();
      $normalization[static::$fieldName][0]['value'] = [
        '2017', '03', '01', '21', '53', '00',
      ];

      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "Unprocessable Entity: validation failed.\n{$fieldName}.0: The datetime value must be a string.\n{$fieldName}.0.value: This value should be of the correct primitive type.\n";
      $this->assertResourceErrorResponse(422, $message, $response);

      // DX: 422 when date format is incorrect.
      $normalization = $this->getNormalizedPostEntity();
      $value = '2017-03-01';
      $normalization[static::$fieldName][0]['value'] = $value;

      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "The specified date \"$value\" is not in an accepted format: \"Y-m-d\\TH:i:sP\" (RFC 3339), \"Y-m-d\\TH:i:sO\" (ISO 8601), \"Y-m-d\\TH:i:s\" (backward compatibility — deprecated).";
      $this->assertResourceErrorResponse(422, $message, $response);

      // DX: 422 when date format is incorrect.
      $normalization = $this->getNormalizedPostEntity();
      $value = '2017-13-55T20:02:00';
      $normalization[static::$fieldName][0]['value'] = $value;

      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "The specified date \"$value\" is not in an accepted format: \"Y-m-d\\TH:i:sP\" (RFC 3339), \"Y-m-d\\TH:i:sO\" (ISO 8601), \"Y-m-d\\TH:i:s\" (backward compatibility — deprecated).";
      $this->assertResourceErrorResponse(422, $message, $response);

      // DX: 422 when date value is invalid.
      $normalization = $this->getNormalizedPostEntity();
      $value = '2017-13-55T20:02:00+00:00';
      $normalization[static::$fieldName][0]['value'] = $value;

      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "The specified date \"$value\" is not in an accepted format: \"Y-m-d\\TH:i:sP\" (RFC 3339), \"Y-m-d\\TH:i:sO\" (ISO 8601), \"Y-m-d\\TH:i:s\" (backward compatibility — deprecated).";
      $this->assertResourceErrorResponse(422, $message, $response);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @group legacy
   * @expectedDeprecation The provided datetime string format (Y-m-d\TH:i:s) is deprecated and will be removed before Drupal 9.0.0. Use the RFC3339 format instead (Y-m-d\TH:i:sP).
   */
  public function testPatch() {
    return parent::testPatch();
  }

}
