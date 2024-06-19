<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

// cspell:ignore attribute\'close

/**
 * Ensures that a form's action attribute can't be exploited with XSS.
 *
 * @group system
 */
class FormActionXssTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $test_user = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $test_user->save();
    \Drupal::service('current_user')->setAccount($test_user);
  }

  /**
   * Tests form action attribute for XSS.
   */
  public function testFormActionXss(): void {
    // Create a new request with a uri which attempts XSS.
    $request_stack = \Drupal::service('request_stack');
    /** @var \Symfony\Component\HttpFoundation\RequestStack $original_request */
    $original_request = $request_stack->pop();
    // Just request some more so there is no request left.
    $request_stack->pop();
    $request_stack->pop();
    $request = Request::create($original_request->getSchemeAndHttpHost() . '/test/"injected=\'attribute\'close="');
    $request->setSession(new Session(new MockArraySessionStorage()));
    $request_stack->push($request);

    $form = \Drupal::formBuilder()->getForm($this);
    $markup = \Drupal::service('renderer')->renderRoot($form);
    $this->setRawContent($markup);

    $elements = $this->xpath('//form');
    $action = isset($elements[0]['action']) ? (string) $elements[0]['action'] : FALSE;
    $injected = isset($elements[0]['injected']) ? (string) $elements[0]['injected'] : FALSE;

    $this->assertSame('/test/"injected=\'attribute\'close="', $action);
    $this->assertRaw('action="/test/&quot;injected=&#039;attribute&#039;close=&quot;"');
    $this->assertNotSame('attribute', $injected);
  }

}
