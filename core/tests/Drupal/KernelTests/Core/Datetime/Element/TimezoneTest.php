<?php

namespace Drupal\KernelTests\Core\Datetime\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the timezone handling of datetime and datelist element types.
 *
 * A range of different permutations of #default_value and #date_timezone
 * for an element are setup in a single form by the buildForm() method, and
 * tested in various ways for both element types.
 *
 * @group Form
 */
class TimezoneTest extends EntityKernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * The date used in tests.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $date;

  /**
   * An array of timezones with labels denoting their use in the tests.
   *
   * @var array
   */
  protected $timezones = [
    // UTC-12, no DST.
    'zone A' => 'Pacific/Kwajalein',
    // UTC-7, no DST.
    'zone B' => 'America/Phoenix',
    // UTC+5:30, no DST.
    'user' => 'Asia/Kolkata',
    'UTC' => 'UTC',
  ];

  /**
   * The test date formatted in various formats and timezones.
   *
   * @var array
   */
  protected $formattedDates = [];

  /**
   * HTML date format pattern.
   *
   * @var string
   */
  protected $dateFormat;

  /**
   * HTML time format pattern.
   *
   * @var string
   */
  protected $timeFormat;

  /**
   * The element type that is being tested ('datetime' or 'datelist').
   *
   * @var string
   */
  protected $elementType;

  /**
   * The number of test elements on the form.
   *
   * @var int
   */
  protected $testConditions;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['test1'] = [
      '#title' => 'No default date, #date_timezone present',
      '#type' => $this->elementType,
      '#default_value' => '',
      '#date_timezone' => $this->timezones['zone A'],
      '#test_expect_timezone' => 'zone A',
    ];

    $form['test2'] = [
      '#title' => 'No default date, no #date_timezone',
      '#type' => $this->elementType,
      '#default_value' => '',
      '#test_expect_timezone' => 'user',
    ];

    $form['test3'] = [
      '#title' => 'Default date present with default timezone, #date_timezone same',
      '#type' => $this->elementType,
      '#default_value' => $this->date,
      '#date_timezone' => $this->timezones['user'],
      '#test_expect_timezone' => 'user',
    ];

    $form['test4'] = [
      '#title' => 'Default date present with default timezone, #date_timezone different',
      '#type' => $this->elementType,
      '#default_value' => $this->date,
      '#date_timezone' => $this->timezones['zone A'],
      '#test_expect_timezone' => 'zone A',
    ];

    $form['test5'] = [
      '#title' => 'Default date present with default timezone, no #date_timezone',
      '#type' => $this->elementType,
      '#default_value' => $this->date,
      '#test_expect_timezone' => 'user',
    ];

    $dateWithTimeZoneA = clone $this->date;
    $dateWithTimeZoneA->setTimezone(new \DateTimeZone($this->timezones['zone A']));
    $form['test6'] = [
      '#title' => 'Default date present with unusual timezone, #date_timezone same',
      '#type' => $this->elementType,
      '#default_value' => $dateWithTimeZoneA,
      '#date_timezone' => $this->timezones['zone A'],
      '#test_expect_timezone' => 'zone A',
    ];

    $form['test7'] = [
      '#title' => 'Default date present with unusual timezone, #date_timezone different',
      '#type' => $this->elementType,
      '#default_value' => $dateWithTimeZoneA,
      '#date_timezone' => $this->timezones['zone B'],
      '#test_expect_timezone' => 'zone B',
    ];

    $form['test8'] = [
      '#title' => 'Default date present with unusual timezone, no #date_timezone',
      '#type' => $this->elementType,
      '#default_value' => $dateWithTimeZoneA,
      '#test_expect_timezone' => 'user',
    ];

    $this->testConditions = 8;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);

    // Setup the background time zones.
    $this->timezones['php initial'] = date_default_timezone_get();
    $user = $this->createUser();
    $user->set('timezone', $this->timezones['user'])->save();
    // This also sets PHP's assumed time.
    \Drupal::currentUser()->setAccount($user);

    // Set a reference date to use in tests.
    $this->date = new DrupalDatetime('2000-01-01 12:00', NULL);

    // Create arrays listing the dates and times of $this->date formatted
    // according to the various timezones of $this->timezones.
    $this->dateFormat = DateFormat::load('html_date')->getPattern();
    $this->timeFormat = DateFormat::load('html_time')->getPattern();
    $date = clone $this->date;
    foreach ($this->timezones as $label => $timezone) {
      $date->setTimezone(new \DateTimeZone($timezone));
      $this->formattedDates['date'][$label] = $date->format($this->dateFormat);
      $this->formattedDates['time'][$label] = $date->format($this->timeFormat);
      $this->formattedDates['day'][$label] = $date->format('j');
      $this->formattedDates['month'][$label] = $date->format('n');
      $this->formattedDates['year'][$label] = $date->format('Y');
      $this->formattedDates['hour'][$label] = $date->format('G');
      $this->formattedDates['minute'][$label] = $date->format('i');
      $this->formattedDates['second'][$label] = $date->format('s');
    }

    // Validate the timezone setup.
    $this->assertEquals($this->timezones['user'], drupal_get_user_timezone(), 'Subsequent tests assume specific value for drupal_get_user_timezone().');
    $this->assertEquals(drupal_get_user_timezone(), date_default_timezone_get(), "Subsequent tests may assume PHP's time is set to Drupal user's time zone.");
    $this->assertEquals(drupal_get_user_timezone(), $this->date->getTimezone()->getName(), 'Subsequent tests assume DrupalDateTime objects default to Drupal user time zone if none specified');
  }

  /**
   * Tests datetime elements interpret their times correctly when saving.
   *
   * Initial times are inevitably presented to the user using a timezone, and so
   * the time must be interpreted using the same timezone when it is time to
   * save the form, otherwise stored times may be changed without the user
   * changing the element's values.
   */
  public function testDatetimeElementTimesUnderstoodCorrectly() {
    $this->assertTimesUnderstoodCorrectly('datetime', ['date', 'time']);
  }

  /**
   * Tests datelist elements interpret their times correctly when saving.
   *
   * See testDatetimeElementTimesUnderstoodCorrectly() for more explanation.
   */
  public function testDatelistElementTimesUnderstoodCorrectly() {
    $this->assertTimesUnderstoodCorrectly('datelist', [
      'day',
      'month',
      'year',
      'hour',
      'minute',
      'second',
    ]);
  }

  /**
   * On datetime elements test #date_timezone after ::processDatetime.
   *
   * The element's render array has a #date_timezone value that should
   * accurately reflect the timezone that will be used to interpret times
   * entered through the element.
   */
  public function testDatetimeTimezonePropertyProcessed() {
    $this->assertDateTimezonePropertyProcessed('datetime');
  }

  /**
   * On datelist elements test #date_timezone after ::processDatetime.
   *
   * See testDatetimeTimezonePropertyProcessed() for more explanation.
   */
  public function testDatelistTimezonePropertyProcessed() {
    $this->assertDateTimezonePropertyProcessed('datelist');
  }

  /**
   * Asserts that elements interpret dates using the expected time zones.
   *
   * @param string $elementType
   *   The element type to test.
   * @param array $inputs
   *   The names of the default input elements used by this element type.
   *
   * @throws \Exception
   */
  protected function assertTimesUnderstoodCorrectly($elementType, array $inputs) {
    $this->elementType = $elementType;

    // Simulate the form being saved, with the user adding the date for any
    // initially empty elements, but not changing other elements.
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form = $this->setupForm($form_state, $form_builder);
    foreach ($form as $elementName => $element) {
      if (
        isset($element['#type']) &&
        $element['#type'] === $this->elementType &&
        $element['#default_value'] === ''
      ) {
        $newValues = [];
        // Build an array of new values for the initially empty elements,
        // depending on the inputs required by the element type, and using
        // the timezone that will be expected for that test element.
        foreach ($inputs as $input) {
          $newValues[$input] = $this->formattedDates[$input][$element['#test_expect_timezone']];
        }
        $form_state->setValue([$elementName], $newValues);
      }
    }
    $form_builder->submitForm($this, $form_state);

    // Examine the output of each test element.
    $utc = new \DateTimeZone('UTC');
    $expectedDateUTC = clone $this->date;
    $expectedDateUTC->setTimezone($utc)->format('Y-m-d H:i:s');
    $wrongDates = [];
    $wrongTimezones = [];
    $rightDates = 0;
    foreach ($form_state->getCompleteForm() as $elementName => $element) {
      if (isset($element['#type']) && $element['#type'] === $this->elementType) {
        $actualDate = $form_state->getValue($elementName);
        $actualTimezone = array_search($actualDate->getTimezone()->getName(), $this->timezones);
        $actualDateUTC = $actualDate->setTimezone($utc)->format('Y-m-d H:i:s');

        // Check that $this->date has not anywhere been accidentally changed
        // from its default timezone, invalidating the test logic.
        $this->assertEquals(drupal_get_user_timezone(), $this->date->getTimezone()->getName(), "Test date still set to user timezone.");

        // Build a list of cases where the result is not as expected.
        // Check the time has been understood correctly.
        if ($actualDate != $this->date) {
          $wrongDates[$element['#title']] = $actualDateUTC;
        }
        else {
          // Explicitly counting test passes prevents the test from seeming to
          // pass just because the whole loop is being skipped.
          $rightDates++;
        }
        // Check the correct timezone is set on the value object.
        if ($element['#test_expect_timezone'] !== $actualTimezone) {
          $wrongTimezones[$element['#title']] = [$element['#test_expect_timezone'], $actualTimezone];
        }
      }
    }

    $message = "On all elements the time should be understood correctly as $expectedDateUTC: \n" . print_r($wrongDates, TRUE);
    $this->assertEquals($this->testConditions, $rightDates, $message);
    $message = "On all elements the correct timezone should be set on the value object: (expected, actual) \n" . print_r($wrongTimezones, TRUE);
    $this->assertCount(0, $wrongTimezones, $message);
  }

  /**
   * Asserts that elements set #date_timezone correctly.
   *
   * @param string $elementType
   *   The element type to test.
   *
   * @throws \Exception
   */
  public function assertDateTimezonePropertyProcessed($elementType) {
    $this->elementType = $elementType;
    // Simulate form being loaded and default values displayed to user.
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $this->setupForm($form_state, $form_builder);

    // Check the #date_timezone property on each processed test element.
    $wrongTimezones = [];
    foreach ($form_state->getCompleteForm() as $elementName => $element) {
      if (isset($element['#type']) && $element['#type'] === $this->elementType) {
        // Check the correct timezone is set on the value object.
        $actualTimezone = array_search($element['#date_timezone'], $this->timezones, TRUE);
        if ($element['#test_expect_timezone'] !== $actualTimezone) {
          $wrongTimezones[$element['#title']] = [
            $element['#test_expect_timezone'],
            $actualTimezone,
          ];
        }
      }
      $this->assertEquals($this->timezones['user'], drupal_get_user_timezone(), 'Subsequent tests assume specific value for drupal_get_user_timezone().');
      $message = "The correct timezone should be set on the processed {$this->elementType}  elements: (expected, actual) \n" . print_r($wrongTimezones, TRUE);
      $this->assertCount(0, $wrongTimezones, $message);
    }
  }

  /**
   * Simulate form being loaded and default values displayed to user.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form_state object.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   A form_builder object.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The modified form state.
   */
  protected function setupForm(FormStateInterface $form_state, FormBuilderInterface $form_builder) {
    $form_id = $form_builder->getFormId($this, $form_state);
    $form = $form_builder->retrieveForm($form_id, $form_state);
    $form_state->setValidationEnforced();
    $form_state->clearErrors();
    $form_builder->prepareForm($form_id, $form, $form_state);
    $form_builder->processForm($form_id, $form, $form_state);
    return $form_builder->retrieveForm($form_id, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_datetime_elements';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

}
