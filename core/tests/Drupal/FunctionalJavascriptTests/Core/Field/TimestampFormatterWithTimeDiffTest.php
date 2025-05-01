<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Core\Field;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the 'timestamp' formatter when is used with time difference setting.
 *
 * @group Field
 */
class TimestampFormatterWithTimeDiffTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Testing entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if ($this->name() === 'testNoRefreshInterval') {
      $this->markTestSkipped("Skipped due to frequent random test failures. See https://www.drupal.org/project/drupal/issues/3400150");
    }

    parent::setUp();

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'time_field',
      'type' => 'timestamp',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'time_field',
      'label' => $this->randomString(),
    ])->save();
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $display->setComponent('time_field', [
      'type' => 'timestamp',
      'settings' => [
        'time_diff' => [
          'enabled' => TRUE,
          'future_format' => '@interval hence',
          'past_format' => '@interval ago',
          'granularity' => 2,
          'refresh' => 1,
        ],
      ],
    ])->setStatus(TRUE)->save();

    $account = $this->createUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($account);

    $this->entity = EntityTest::create([
      'type' => 'entity_test',
      'name' => $this->randomString(),
      'time_field' => $this->container->get('datetime.time')->getRequestTime(),
    ]);
    $this->entity->save();
  }

  /**
   * Tests the 'timestamp' formatter when is used with time difference setting.
   */
  public function testTimestampFormatterWithTimeDiff(): void {
    $this->drupalGet($this->entity->toUrl());

    // Unit testing Drupal.timeDiff.format(). Not using @dataProvider mechanism
    // here in order to avoid installing the site for each case.
    foreach ($this->getFormatDiffTestCases() as $case) {
      $from = \DateTime::createFromFormat(\DateTimeInterface::RFC3339, $case['from'])->getTimestamp();
      $to = \DateTime::createFromFormat(\DateTimeInterface::RFC3339, $case['to'])->getTimestamp();
      $diff = $to - $from;
      $options = json_encode($case['options']);
      $expected_value = json_encode($case['expected_value']);
      $expected_formatted_value = $case['expected_formatted_value'];

      // Test the returned value.
      $this->assertJsCondition("JSON.stringify(Drupal.timeDiff.format($diff, $options).value) === '$expected_value'");
      // Test the returned formatted value.
      $this->assertJsCondition("Drupal.timeDiff.format($diff, $options).formatted === '$expected_formatted_value'");
    }

    // Unit testing Drupal.timeDiff.refreshInterval(). Not using @dataProvider
    // mechanism here in order to avoid reinstalling the site for each case.
    foreach ($this->getRefreshIntervalTestCases() as $case) {
      $interval = json_encode($case['time_diff']);
      $this->assertJsCondition("Drupal.timeDiff.refreshInterval($interval, {$case['configured_refresh_interval']}, {$case['granularity']}) === {$case['computed_refresh_interval']}");
    }

    // Test the UI.
    $time_element = $this->getSession()->getPage()->find('css', 'time');

    $time_diff = $time_element->getText();
    [$seconds_value] = explode(' ', $time_diff, 2);

    // Wait up to 2 seconds to make sure that the last time difference value
    // has been refreshed.
    $this->assertJsCondition("document.getElementsByTagName('time')[0].textContent != '$time_diff'", 2000);
    $time_diff = $time_element->getText();
    [$new_seconds_value] = explode(' ', $time_diff, 2);
    $this->assertGreaterThan($seconds_value, $new_seconds_value);

    // Once again.
    $this->assertJsCondition("document.getElementsByTagName('time')[0].textContent != '$time_diff'", 2000);
    $time_diff = $time_element->getText();
    $seconds_value = $new_seconds_value;
    [$new_seconds_value] = explode(' ', $time_diff, 2);
    $this->assertGreaterThan($seconds_value, $new_seconds_value);
  }

  /**
   * Tests the 'timestamp' formatter without refresh interval.
   */
  public function testNoRefreshInterval(): void {
    // Set the refresh interval to zero, meaning "no refresh".
    $display = EntityViewDisplay::load('entity_test.entity_test.default');
    $component = $display->getComponent('time_field');
    $component['settings']['time_diff']['refresh'] = 0;
    $display->setComponent('time_field', $component)->save();
    $this->drupalGet($this->entity->toUrl());

    $time_element = $this->getSession()->getPage()->find('css', 'time');
    $time_diff_text = $time_element->getText();
    $time_diff_settings = Json::decode($time_element->getAttribute('data-drupal-time-diff'));

    // Check that the timestamp is represented as a time difference.
    $this->assertMatchesRegularExpression('/^\d+ seconds? ago$/', $time_diff_text);
    // Check that the refresh is zero (no refresh).
    $this->assertSame(0, $time_diff_settings['refresh']);
  }

  /**
   * Provides test cases for unit testing Drupal.timeDiff.format().
   *
   * @return array[]
   *   A list of test cases, each representing parameters to be passed to the
   *   JavaScript function.
   */
  protected function getFormatDiffTestCases(): array {
    return [
      'normal, granularity: 2' => [
        'from' => '2010-02-11T10:00:00+00:00',
        'to' => '2010-02-16T14:00:00+00:00',
        'options' => [
          'granularity' => 2,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'day' => 5,
          'hour' => 4,
        ],
        'expected_formatted_value' => '5 days 4 hours',
      ],
      'inverted, strict' => [
        'from' => '2010-02-16T14:00:00+00:00',
        'to' => '2010-02-11T10:00:00+00:00',
        'options' => [
          'granularity' => 2,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'second' => 0,
        ],
        'expected_formatted_value' => '0 seconds',
      ],
      'inverted, strict (strict passed explicitly)' => [
        'from' => '2010-02-16T14:00:00+00:00',
        'to' => '2010-02-11T10:00:00+00:00',
        'options' => [
          'granularity' => 2,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'second' => 0,
        ],
        'expected_formatted_value' => '0 seconds',
      ],
      'inverted, non-strict' => [
        'from' => '2010-02-16T14:00:00+00:00',
        'to' => '2010-02-11T10:00:00+00:00',
        'options' => [
          'granularity' => 2,
        ],
        'expected_value' => [
          'day' => 5,
          'hour' => 4,
        ],
        'expected_formatted_value' => '5 days 4 hours',
      ],
      'normal, max granularity' => [
        'from' => '2010-02-02T10:30:45+00:00',
        'to' => '2011-06-24T11:37:02+00:00',
        'options' => [
          'granularity' => 7,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'year' => 1,
          'month' => 4,
          'week' => 3,
          'day' => 1,
          'hour' => 1,
          'minute' => 6,
          'second' => 17,
        ],
        'expected_formatted_value' => '1 year 4 months 3 weeks 1 day 1 hour 6 minutes 17 seconds',
      ],
      "'1 hour 0 minutes 1 second' is '1 hour'" => [
        'from' => '2010-02-02T10:30:45+00:00',
        'to' => '2010-02-02T11:30:46+00:00',
        'options' => [
          'granularity' => 3,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'hour' => 1,
        ],
        'expected_formatted_value' => '1 hour',
      ],
      "'1 hour 0 minutes' is '1 hour'" => [
        'from' => '2010-02-02T10:30:45+00:00',
        'to' => '2010-02-02T11:30:45+00:00',
        'options' => [
          'granularity' => 2,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'hour' => 1,
        ],
        'expected_formatted_value' => '1 hour',
      ],
    ];
  }

  /**
   * Provides test cases for unit testing Drupal.timeDiff.refreshInterval().
   *
   * @return array[]
   *   A list of test cases, each representing parameters to be passed to the
   *   javascript function.
   */
  protected function getRefreshIntervalTestCases(): array {
    return [
      'passed timeout is not altered' => [
        'time_diff' => [
          'hour' => 11,
          'minute' => 10,
          'second' => 30,
        ],
        'configured_refresh_interval'  => 10,
        'granularity' => 3,
        'computed_refresh_interval' => 10,
      ],
      'timeout lower than the lowest interval part' => [
        'time_diff' => [
          'hour' => 11,
          'minute' => 10,
        ],
        'configured_refresh_interval'  => 59,
        'granularity' => 2,
        'computed_refresh_interval' => 60,
      ],
      'timeout with number of parts lower than the granularity' => [
        'time_diff' => [
          'hour' => 1,
          'minute' => 0,
        ],
        'configured_refresh_interval'  => 10,
        'granularity' => 2,
        'computed_refresh_interval' => 60,
      ],
      'big refresh interval' => [
        'time_diff' => [
          'minute' => 3,
          'second' => 30,
        ],
        'configured_refresh_interval' => 1000,
        'granularity' => 1,
        'computed_refresh_interval' => 1000,
      ],
    ];
  }

}
