<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Queue;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests serializing a form with an injected DatabaseQueue instance.
 */
#[Group('Queue')]
#[RunTestsInSeparateProcesses]
class QueueSerializationTest extends KernelTestBase implements FormInterface {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\DatabaseQueue
   */
  protected DatabaseQueue $queue;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'queue_test_injection_form';
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // We only need a valid \Drupal\Core\Queue\DatabaseQueue object here, not
    // an actual valid queue.
    $this->queue = \Drupal::service('queue.database')->get('fake_a_queue');
    $test_user = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $test_user->save();
    \Drupal::service('current_user')->setAccount($test_user);
  }

  /**
   * Tests queue injection serialization.
   */
  public function testQueueSerialization(): void {
    $form_state = new FormState();
    $form_state->setRequestMethod('POST');
    $form_state->setCached();
    $form_builder = $this->container->get('form_builder');
    $form_id = $form_builder->getFormId($this, $form_state);
    $form = $form_builder->retrieveForm($form_id, $form_state);
    $form_builder->prepareForm($form_id, $form, $form_state);
    $form_builder->processForm($form_id, $form, $form_state);
  }

}
