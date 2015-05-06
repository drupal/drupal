<?php
/**
 * @file
 * Contains \Drupal\dblog\Tests\DbLogFormInjectionTest
 */

namespace Drupal\dblog\Tests;


use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests serializing a form with an injected dblog logger instance.
 *
 * @group dblog
 */
class DbLogFormInjectionTest extends KernelTestBase implements FormInterface {

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
  public static $modules = array('system', 'dblog', 'user');

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dblog_test_injection_form';
  }

  /**
   * Process callback.
   *
   * @param array $element
   *   Form element
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
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('dblog', ['watchdog']);
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installEntitySchema('user');
    $this->logger = \Drupal::logger('test_logger');
    $test_user = User::create(array(
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ));
    $test_user->save();
    \Drupal::service('current_user')->setAccount($test_user);
  }

  /**
   * Tests db log injection serialization.
   */
  public function testLoggerSerialization() {
    $form_state = new FormState();
    $form_state->setCached();
    $form_builder = $this->container->get('form_builder');
    $form_id = $form_builder->getFormId($this, $form_state);
    $form = $form_builder->retrieveForm($form_id, $form_state);
    $form_builder->prepareForm($form_id, $form, $form_state);
    $form_builder->processForm($form_id, $form, $form_state);
  }

}
