<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Datetime;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Security\UntrustedCallbackException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests DatetimeElement functionality.
 *
 * @group Form
 */
class DatetimeElementFormTest extends KernelTestBase implements FormInterface, TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['datetime', 'system'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_datetime_element';
  }

  /**
   * {@inheritdoc}
   */
  public function datetimeDateCallbackTrusted(array &$element, FormStateInterface $form_state, ?DrupalDateTime $date = NULL): void {
    $element['datetimeDateCallbackExecuted'] = [
      '#value' => TRUE,
    ];
    $form_state->set('datetimeDateCallbackExecuted', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function datetimeDateCallback(array &$element, FormStateInterface $form_state, ?DrupalDateTime $date = NULL): void {
    $element['datetimeDateCallbackExecuted'] = [
      '#value' => TRUE,
    ];
    $form_state->set('datetimeDateCallbackExecuted', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function datetimeTimeCallbackTrusted(array &$element, FormStateInterface $form_state, ?DrupalDateTime $date = NULL): void {
    $element['timeCallbackExecuted'] = [
      '#value' => TRUE,
    ];
    $form_state->set('timeCallbackExecuted', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function datetimeTimeCallback(array &$element, FormStateInterface $form_state, ?DrupalDateTime $date = NULL): void {
    $element['timeCallbackExecuted'] = [
      '#value' => TRUE,
    ];
    $form_state->set('timeCallbackExecuted', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $date_callback = 'datetimeDateCallbackTrusted', string $time_callback = 'datetimeTimeCallbackTrusted') {

    $form['datetime_element'] = [
      '#title' => 'datelist test',
      '#type' => 'datetime',
      '#default_value' => new DrupalDateTime('2000-01-01 00:00:00'),
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_date_element' => 'HTML Date',
      '#date_time_element' => 'HTML Time',
      '#date_increment' => 1,
      '#date_date_callbacks' => [[$this, $date_callback]],
      '#date_time_callbacks' => [[$this, $time_callback]],
    ];

    // Element without specifying the default value.
    $form['simple_datetime_element'] = [
      '#type' => 'datetime',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_date_element' => 'HTML Date',
      '#date_time_element' => 'HTML Time',
    ];

    // Element with #required_error.
    $form['datetime_required_error'] = [
      '#type' => 'datetime',
      '#title' => 'Datetime with required error',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_date_element' => 'HTML Date',
      '#date_time_element' => 'HTML Time',
      '#required' => TRUE,
      '#required_error' => 'Custom required error message.',
    ];

    // Element without #required_error.
    $form['datetime_no_required_error'] = [
      '#type' => 'datetime',
      '#title' => 'Datetime without required error',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_date_element' => 'HTML Date',
      '#date_time_element' => 'HTML Time',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests that default handlers are added even if custom are specified.
   */
  public function testDatetimeElement(): void {
    $form_state = new FormState();
    $form = \Drupal::formBuilder()->buildForm($this, $form_state);
    $this->render($form);

    $this->assertTrue($form['datetime_element']['datetimeDateCallbackExecuted']['#value']);
    $this->assertTrue($form['datetime_element']['timeCallbackExecuted']['#value']);
    $this->assertTrue($form_state->get('datetimeDateCallbackExecuted'));
    $this->assertTrue($form_state->get('timeCallbackExecuted'));
  }

  /**
   * Tests that deprecations are raised if untrusted callbacks are used.
   *
   * @param string $date_callback
   *   Name of the callback to use for the date-time date callback.
   * @param string $time_callback
   *   Name of the callback to use for the date-time time callback.
   * @param string|null $expected_exception
   *   The expected exception message if an exception should be thrown, or
   *   NULL if otherwise.
   *
   * @dataProvider providerUntrusted
   * @group legacy
   */
  public function testDatetimeElementUntrustedCallbacks(string $date_callback = 'datetimeDateCallbackTrusted', string $time_callback = 'datetimeTimeCallbackTrusted', ?string $expected_exception = NULL) : void {
    if ($expected_exception) {
      $this->expectException(UntrustedCallbackException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    $form = \Drupal::formBuilder()->getForm($this, $date_callback, $time_callback);
    $this->render($form);

    $this->assertTrue($form['datetime_element']['datetimeDateCallbackExecuted']['#value']);
    $this->assertTrue($form['datetime_element']['timeCallbackExecuted']['#value']);
  }

  /**
   * Data provider for ::testDatetimeElementUntrustedCallbacks().
   *
   * @return string[][]
   *   Test cases.
   */
  public static function providerUntrusted() : array {
    return [
      'untrusted date' => [
        'datetimeDateCallback',
        'datetimeTimeCallbackTrusted',
        sprintf('DateTime element #date_date_callbacks callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was %s. See https://www.drupal.org/node/3217966', Variable::callableToString([static::class, 'datetimeDateCallback'])),
      ],
      'untrusted time' => [
        'datetimeDateCallbackTrusted',
        'datetimeTimeCallback',
        sprintf('DateTime element #date_time_callbacks callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was %s. See https://www.drupal.org/node/3217966', Variable::callableToString([static::class, 'datetimeTimeCallback'])),
      ],
    ];
  }

  /**
   * Tests proper timezone handling of the Datetime element.
   */
  public function testTimezoneHandling(): void {
    // Render the form once with the site's timezone.
    $form = \Drupal::formBuilder()->getForm($this);
    $this->render($form);
    $this->assertEquals('Australia/Sydney', $form['datetime_element']['#date_timezone']);

    // Mimic a user with a different timezone than Australia/Sydney.
    date_default_timezone_set('UTC');

    $form = \Drupal::formBuilder()->getForm($this);
    $this->render($form);
    $this->assertEquals('UTC', $form['datetime_element']['#date_timezone']);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'datetimeDateCallbackTrusted',
      'datetimeTimeCallbackTrusted',
    ];
  }

  /**
   * Tests the custom required error message for datetime elements.
   */
  public function testDatetimeElementRequiredError(): void {
    $form_builder = $this->container->get('form_builder');

    // Test datetime element with #required_error.
    $form_state = (new FormState())
      ->setValues([
        'datetime_required_error' => '',
      ]);
    $form_builder->submitForm($this, $form_state);
    // Check that the custom required error message is set correctly.
    $this->assertEquals('Custom required error message.', $form_state->getErrors()['datetime_required_error']);

    // Test datetime element without #required_error.
    $form_state = (new FormState())
      ->setValues([
        'datetime_no_required_error' => '',
      ]);
    $form_builder->submitForm($this, $form_state);
    // Check that the default required error message is set correctly.
    $this->assertEquals('The Datetime without required error date is required.', $form_state->getErrors()['datetime_no_required_error']);
  }

}
