<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\serialization\Normalizer\TimestampNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Unit test coverage for the "Timestamp" @DataType.
 *
 * @group serialization
 * @coversDefaultClass \Drupal\serialization\Normalizer\TimestampNormalizer
 * @see \Drupal\Core\TypedData\Plugin\DataType\Timestamp
 */
class TimestampNormalizerTest extends UnitTestCase {

  /**
   * The tested data type's normalizer.
   *
   * @var \Drupal\serialization\Normalizer\TimestampNormalizer
   */
  protected $normalizer;

  /**
   * The tested data type.
   *
   * @var \Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalizer = new TimestampNormalizer($this->prophesize(ConfigFactoryInterface::class)->reveal());
    $this->data = $this->prophesize(Timestamp::class);
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization($this->data->reveal()));

    $integer = $this->prophesize(IntegerData::class);
    $this->assertFalse($this->normalizer->supportsNormalization($integer->reveal()));

    $datetime = $this->prophesize(DateTimeInterface::class);
    $this->assertFalse($this->normalizer->supportsNormalization($datetime->reveal()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization() {
    $this->assertTrue($this->normalizer->supportsDenormalization($this->data->reveal(), Timestamp::class));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $random_rfc_3339_string = $this->randomMachineName();

    $drupal_date_time = $this->prophesize(TimestampNormalizerTestDrupalDateTime::class);
    $drupal_date_time->setTimezone(new \DateTimeZone('UTC'))
      ->willReturn($drupal_date_time->reveal());
    $drupal_date_time->format(\DateTime::RFC3339)
      ->willReturn($random_rfc_3339_string);

    $this->data->getDateTime()
      ->willReturn($drupal_date_time->reveal());

    $normalized = $this->normalizer->normalize($this->data->reveal());
    $this->assertSame($random_rfc_3339_string, $normalized);
  }

  /**
   * Tests the denormalize function with good data.
   *
   * @covers ::denormalize
   * @dataProvider providerTestDenormalizeValidFormats
   */
  public function testDenormalizeValidFormats($normalized, $expected) {
    $denormalized = $this->normalizer->denormalize($normalized, Timestamp::class, NULL, []);
    $this->assertSame($expected, $denormalized);
  }

  /**
   * Data provider for testDenormalizeValidFormats.
   *
   * @return array
   */
  public function providerTestDenormalizeValidFormats() {
    $expected_stamp = 1478422920;

    $data = [];

    $data['U'] = [$expected_stamp, $expected_stamp];
    $data['RFC3339'] = ['2016-11-06T09:02:00+00:00', $expected_stamp];
    $data['RFC3339 +0100'] = ['2016-11-06T09:02:00+01:00', $expected_stamp - 1 * 3600];
    $data['RFC3339 -0600'] = ['2016-11-06T09:02:00-06:00', $expected_stamp + 6 * 3600];

    $data['ISO8601'] = ['2016-11-06T09:02:00+0000', $expected_stamp];
    $data['ISO8601 +0100'] = ['2016-11-06T09:02:00+0100', $expected_stamp - 1 * 3600];
    $data['ISO8601 -0600'] = ['2016-11-06T09:02:00-0600', $expected_stamp + 6 * 3600];

    return $data;
  }

  /**
   * Tests the denormalize function with bad data.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeException() {
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('The specified date "2016/11/06 09:02am GMT" is not in an accepted format: "U" (UNIX timestamp), "Y-m-d\TH:i:sO" (ISO 8601), "Y-m-d\TH:i:sP" (RFC 3339).');

    $normalized = '2016/11/06 09:02am GMT';

    $this->normalizer->denormalize($normalized, Timestamp::class, NULL, []);
  }

}

/**
 * Note: Prophecy does not support magic methods. By subclassing and specifying
 * an explicit method, Prophecy works.
 * @see https://github.com/phpspec/prophecy/issues/338
 * @see https://github.com/phpspec/prophecy/issues/34
 * @see https://github.com/phpspec/prophecy/issues/80
 */
class TimestampNormalizerTestDrupalDateTime extends DrupalDateTime {

  public function setTimezone(\DateTimeZone $timezone) {
    parent::setTimezone($timezone);
  }

}
