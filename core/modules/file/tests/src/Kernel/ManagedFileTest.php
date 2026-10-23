<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\TrustedCallbackInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Managed file element test.
 *
 * @see \Drupal\file\Element\ManagedFile
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class ManagedFileTest extends FileManagedUnitTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_test_managed_file';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $file_value_callback = 'managedFileValueCallbackTrusted'): array {
    $form['managed_file'] = [
      '#type' => 'managed_file',
      '#file_value_callbacks' => [[$this, $file_value_callback]],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * A #file_value_callbacks callback trusted via the TrustedCallback attribute.
   */
  #[TrustedCallback]
  public function managedFileValueCallbackTrusted(array &$element, $input, FormStateInterface $form_state): void {
    $form_state->set('managedFileValueCallbackExecuted', TRUE);
  }

  /**
   * An untrusted #file_value_callbacks callback.
   */
  public function managedFileValueCallback(array &$element, $input, FormStateInterface $form_state): void {
    $form_state->set('managedFileValueCallbackExecuted', TRUE);
  }

  /**
   * Submits the test form, using the given #file_value_callbacks callback.
   *
   * @param string $file_value_callback
   *   The name of the method on this class to use as the callback.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state after submission.
   */
  protected function submitManagedFileForm(string $file_value_callback = 'managedFileValueCallbackTrusted'): FormStateInterface {
    $form_state = new FormState();
    $form_state->setValues(['managed_file' => NULL]);
    \Drupal::formBuilder()->submitForm($this, $form_state, $file_value_callback);
    return $form_state;
  }

  /**
   * Tests that managed file elements can be programmatically submitted.
   */
  public function testManagedFileElement(): void {
    $form_state = $this->submitManagedFileForm();
    // Should submit without any errors.
    $this->assertCount(0, $form_state->getErrors());
    $this->assertTrue($form_state->get('managedFileValueCallbackExecuted'));
  }

  /**
   * Tests that deprecations are triggered if untrusted callbacks are used.
   */
  #[IgnoreDeprecations]
  public function testManagedFileElementUntrustedCallbacks(): void {
    $this->expectUserDeprecationMessage(sprintf(
      'Using ManagedFile element #file_value_callbacks callbacks that are not tagged with the %s attribute, not methods of a class that implements %s, and not anonymous functions is deprecated in drupal:12.1.0 and will throw an exception in drupal:13.0.0. The callback was %s. See https://www.drupal.org/node/3620771',
      TrustedCallback::class,
      TrustedCallbackInterface::class,
      Variable::callableToString([$this, 'managedFileValueCallback'])
    ));
    $form_state = $this->submitManagedFileForm('managedFileValueCallback');
    // Untrusted callbacks are still executed during the deprecation period.
    $this->assertTrue($form_state->get('managedFileValueCallbackExecuted'));
  }

}
