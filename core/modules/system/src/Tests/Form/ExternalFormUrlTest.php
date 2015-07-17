<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\ExternalFormUrlTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ensures that form actions can't be tricked into sending to external URLs.
 *
 * @group system
 */
class ExternalFormUrlTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_form_url_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['something'] = [
      '#type' => 'textfield',
      '#title' => 'What do you think?',
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installEntitySchema('user');

    $test_user = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $test_user->save();
    \Drupal::service('current_user')->setAccount($test_user);

  }

  /**
   * Tests form behaviour.
   */
  public function testActionUrlBehavior() {
    // Create a new request which has a request uri with multiple leading
    // slashes and make it the master request.
    $request_stack = \Drupal::service('request_stack');
    $original_request = $request_stack->pop();
    $request = Request::create($original_request->getSchemeAndHttpHost() . '//example.org');
    $request_stack->push($request);

    $form = \Drupal::formBuilder()->getForm($this);
    $markup = \Drupal::service('renderer')->renderRoot($form);

    $this->setRawContent($markup);
    $elements = $this->xpath('//form/@action');
    $action = (string) $elements[0];
    $this->assertEqual($original_request->getSchemeAndHttpHost() . '//example.org', $action);

    // Create a new request which has a request uri with a single leading slash
    // and make it the master request.
    $request_stack = \Drupal::service('request_stack');
    $original_request = $request_stack->pop();
    $request = Request::create($original_request->getSchemeAndHttpHost() . '/example.org');
    $request_stack->push($request);

    $form = \Drupal::formBuilder()->getForm($this);
    $markup = \Drupal::service('renderer')->renderRoot($form);

    $this->setRawContent($markup);
    $elements = $this->xpath('//form/@action');
    $action = (string) $elements[0];
    $this->assertEqual('/example.org', $action);
  }

}
