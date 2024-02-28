<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime_range\Functional\EntityResource\EntityTest;

use Drupal\Core\Url;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use GuzzleHttp\RequestOptions;

/**
 * Tests the 'daterange' field's normalization.
 *
 * @group datetime_range
 */
class EntityTestDateRangeTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * The ISO date string to use throughout the test.
   *
   * @var string
   */
  protected static $dateString = '2017-03-01T20:02:00';

  /**
   * Datetime Range test field name.
   *
   * @var string
   */
  protected static $fieldName = 'field_daterange';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['datetime_range', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add datetime_range field.
    FieldStorageConfig::create([
      'field_name' => static::$fieldName,
      'type' => 'daterange',
      'entity_type' => static::$entityTypeId,
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_ALLDAY],
    ])->save();

    FieldConfig::create([
      'field_name' => static::$fieldName,
      'entity_type' => static::$entityTypeId,
      'bundle' => $this->entity->bundle(),
    ])->save();

    // Reload entity so that it has the new field.
    $this->entity = $this->entityStorage->load($this->entity->id());
    $this->entity->set(static::$fieldName, [
      'value' => static::$dateString,
      'end_value' => static::$dateString,
    ]);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity_test = EntityTest::create([
      'name' => 'Llama',
      'type' => static::$entityTypeId,
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
          'end_value' => '2017-03-02T07:02:00+11:00',
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
          'value' => '2017-03-01T20:02:00+00:00',
          'end_value' => '2017-03-01T20:02:00+00:00',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options): void {
    parent::assertNormalizationEdgeCases($method, $url, $request_options);

    if ($this->entity->getEntityType()->hasKey('bundle')) {
      $fieldName = static::$fieldName;

      // DX: 422 when 'value' data type is incorrect.
      $normalization = $this->getNormalizedPostEntity();
      $normalization[static::$fieldName][0]['value'] = [
        '2017', '03', '01', '21', '53', '00',
      ];
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "Unprocessable Entity: validation failed.\n{$fieldName}.0.value: This value should be of the correct primitive type.\n";
      $this->assertResourceErrorResponse(422, $message, $response);

      // DX: 422 when 'end_value' is not specified.
      $normalization = $this->getNormalizedPostEntity();
      unset($normalization[static::$fieldName][0]['end_value']);
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "Unprocessable Entity: validation failed.\n{$fieldName}.0.end_value: This value should not be null.\n";
      $this->assertResourceErrorResponse(422, $message, $response);

      // DX: 422 when 'end_value' data type is incorrect.
      $normalization = $this->getNormalizedPostEntity();
      $normalization[static::$fieldName][0]['end_value'] = [
        '2017', '03', '01', '21', '53', '00',
      ];
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "Unprocessable Entity: validation failed.\n{$fieldName}.0.end_value: This value should be of the correct primitive type.\n";
      $this->assertResourceErrorResponse(422, $message, $response);

      // DX: 422 when end date value is invalid.
      $normalization = $this->getNormalizedPostEntity();
      $value = '2017-13-55T20:02:00+00:00';
      $normalization[static::$fieldName][0]['end_value'] = $value;

      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);
      $response = $this->request($method, $url, $request_options);
      $message = "The specified date \"$value\" is not in an accepted format: \"Y-m-d\\TH:i:sP\" (RFC 3339), \"Y-m-d\\TH:i:sO\" (ISO 8601).";
      $this->assertResourceErrorResponse(422, $message, $response);

      // @todo Expand in https://www.drupal.org/project/drupal/issues/2847041.
    }
  }

}
