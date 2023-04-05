<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\serialization\Normalizer\DateTimeNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Unit test coverage for @DataTypes implementing DateTimeInterface.
 *
 * @group serialization
 * @coversDefaultClass \Drupal\serialization\Normalizer\DateTimeNormalizer
 * @see \Drupal\Core\TypedData\Type\DateTimeInterface
 */
class DateTimeNormalizerTest extends UnitTestCase {

  /**
   * The tested data type's normalizer.
   *
   * @var \Drupal\serialization\Normalizer\DateTimeNormalizer
   */
  protected $normalizer;

  /**
   * The tested data type.
   *
   * @var \Drupal\Core\TypedData\Type\DateTimeInterface
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $system_date_config = $this->prophesize(ImmutableConfig::class);
    $system_date_config->get('timezone.default')
      ->willReturn('Australia/Sydney');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('system.date')
      ->willReturn($system_date_config->reveal());

    $this->normalizer = new DateTimeNormalizer($config_factory->reveal());
    $this->data = $this->prophesize(DateTimeInterface::class);
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization($this->data->reveal()));

    $datetimeiso8601 = $this->prophesize(DateTimeIso8601::class);
    $this->assertTrue($this->normalizer->supportsNormalization($datetimeiso8601->reveal()));

    $integer = $this->prophesize(IntegerData::class);
    $this->assertFalse($this->normalizer->supportsNormalization($integer->reveal()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization() {
    $this->assertTrue($this->normalizer->supportsDenormalization($this->data->reveal(), DateTimeInterface::class));
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $random_rfc_3339_string = $this->randomMachineName();

    $drupal_date_time = $this->prophesize(DateTimeNormalizerTestDrupalDateTime::class);
    $drupal_date_time->setTimezone(new \DateTimeZone('Australia/Sydney'))
      ->willReturn($drupal_date_time->reveal());
    $drupal_date_time->format(\DateTime::RFC3339)
      ->willReturn($random_rfc_3339_string);

    $this->data->getDateTime()
      ->willReturn($drupal_date_time->reveal());

    $normalized = $this->normalizer->normalize($this->data->reveal());
    $this->assertSame($random_rfc_3339_string, $normalized);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeWhenNull() {
    $this->data->getDateTime()
      ->willReturn(NULL);

    $normalized = $this->normalizer->normalize($this->data->reveal());
    $this->assertNull($normalized);
  }

  /**
   * Tests the denormalize function with good data.
   *
   * @covers ::denormalize
   * @dataProvider providerTestDenormalizeValidFormats
   */
  public function testDenormalizeValidFormats($normalized, $expected) {
    $denormalized = $this->normalizer->denormalize($normalized, DateTimeInterface::class, NULL, []);
    $this->assertSame(0, $denormalized->getTimestamp() - $expected->getTimestamp());
    $this->assertEquals($expected, $denormalized);
  }

  /**
   * Data provider for testDenormalizeValidFormats.
   *
   * @return array
   */
  public function providerTestDenormalizeValidFormats() {
    $data = [];

    $data['RFC3339'] = ['2016-11-06T09:02:00+00:00', new \DateTimeImmutable('2016-11-06T09:02:00+00:00')];
    $data['RFC3339 +0100'] = ['2016-11-06T09:02:00+01:00', new \DateTimeImmutable('2016-11-06T09:02:00+01:00')];
    $data['RFC3339 -0600'] = ['2016-11-06T09:02:00-06:00', new \DateTimeImmutable('2016-11-06T09:02:00-06:00')];

    $data['ISO8601'] = ['2016-11-06T09:02:00+0000', new \DateTimeImmutable('2016-11-06T09:02:00+00:00')];
    $data['ISO8601 +0100'] = ['2016-11-06T09:02:00+0100', new \DateTimeImmutable('2016-11-06T09:02:00+01:00')];
    $data['ISO8601 -0600'] = ['2016-11-06T09:02:00-0600', new \DateTimeImmutable('2016-11-06T09:02:00-06:00')];

    return $data;
  }

  /**
   * Tests the denormalize function with a user supplied format.
   *
   * @covers ::denormalize
   * @dataProvider providerTestDenormalizeUserFormats
   */
  public function testDenormalizeUserFormats($normalized, $format, $expected) {
    $denormalized = $this->normalizer->denormalize($normalized, DateTimeInterface::class, NULL, ['datetime_allowed_formats' => [$format]]);
    $this->assertSame(0, $denormalized->getTimestamp() - $expected->getTimestamp());
    $this->assertEquals($expected, $denormalized);
  }

  /**
   * Data provider for testDenormalizeUserFormats.
   *
   * @return array
   */
  public function providerTestDenormalizeUserFormats() {
    $data = [];

    $data['Y/m/d H:i:s P'] = ['2016/11/06 09:02:00 +00:00', 'Y/m/d H:i:s P', new \DateTimeImmutable('2016-11-06T09:02:00+00:00')];
    $data['H:i:s Y/m/d P'] = ['09:02:00 2016/11/06  +01:00', 'H:i:s Y/m/d P', new \DateTimeImmutable('2016-11-06T09:02:00+01:00')];
    $data['Y/m/d H:i:s'] = ['09:02:00 2016/11/06', 'H:i:s Y/m/d', new \DateTimeImmutable('2016-11-06T09:02:00+11:00')];

    return $data;
  }

  /**
   * Tests the denormalize function with bad data.
   *
   * @covers ::denormalize
   */
  public function testDenormalizeException() {
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('The specified date "2016/11/06 09:02am GMT" is not in an accepted format: "Y-m-d\TH:i:sP" (RFC 3339), "Y-m-d\TH:i:sO" (ISO 8601).');

    $normalized = '2016/11/06 09:02am GMT';

    $this->normalizer->denormalize($normalized, DateTimeInterface::class, NULL, []);
  }

}


/**
 * Note: Prophecy does not support magic methods. By subclassing and specifying
 * an explicit method, Prophecy works.
 * @see https://github.com/phpspec/prophecy/issues/338
 * @see https://github.com/phpspec/prophecy/issues/34
 * @see https://github.com/phpspec/prophecy/issues/80
 */
class DateTimeNormalizerTestDrupalDateTime extends DrupalDateTime {

  public function setTimezone(\DateTimeZone $timezone) {
    parent::setTimezone($timezone);
  }

}
