<?php

namespace Drupal\Tests\datetime\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests serializing a form with an injected datetime instance.
 *
 * @group datetime
 */
class DateTimeFormInjectionTest extends KernelTestBase implements FormInterface {

  use DependencySerializationTrait;

  /**
   * A Dblog logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'datetime'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['key_value_expire', 'sequences']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'datetime_test_injection_form';
  }

  /**
   * Process callback.
   *
   * @param array $element
   *   Form element.
   *
   * @return array
   *   Processed element.
   */
  public function process($element) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['datelist_element'] = [
      '#title' => 'datelist test',
      '#type' => 'datelist',
      '#default_value' => new DrupalDateTime('2000-01-01 00:00:00'),
      '#date_part_order' => [
        'month',
        'day',
        'year',
        'hour',
        'minute', 'ampm',
      ],
      '#date_text_parts' => ['year'],
      '#date_year_range' => '2010:2020',
      '#date_increment' => 15,
    ];
    $form['#process'][] = [$this, 'process'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->assertTrue(TRUE);
    $form_state->setRebuild();
  }

  /**
   * Tests custom string injection serialization.
   */
  public function testDatetimeSerialization() {
    $form_state = new FormState();
    $form_state->setRequestMethod('POST');
    $form_state->setCached();
    $form_builder = $this->container->get('form_builder');
    $form_id = $form_builder->getFormId($this, $form_state);
    $form = $form_builder->retrieveForm($form_id, $form_state);
    $form_builder->prepareForm($form_id, $form, $form_state);
    // Set up $form_state so that the form is properly submitted.
    $form_state->setUserInput(['form_id' => $form_id]);
    $form_state->setProgrammed();
    $form_state->setSubmitted();
    $form_builder->processForm($form_id, $form, $form_state);
  }

}
