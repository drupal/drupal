<?php

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests \Drupal::formBuilder()->setCache() and
 * \Drupal::formBuilder()->getCache().
 *
 * @group Form
 */
class FormCacheTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user'];

  /**
   * @var string
   */
  protected $formBuildId;

  /**
   * @var array
   */
  protected $form;

  /**
   * @var array
   */
  protected $formState;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->formBuildId = $this->randomMachineName();
    $this->form = [
      '#property' => $this->randomMachineName(),
    ];
    $this->formState = new FormState();
    $this->formState->set('example', $this->randomMachineName());
  }

  /**
   * Tests the form cache with a logged-in user.
   */
  public function testCacheToken() {
    \Drupal::currentUser()->setAccount(new UserSession(['uid' => 1]));
    \Drupal::formBuilder()->setCache($this->formBuildId, $this->form, $this->formState);

    $cached_form_state = new FormState();
    $cached_form = \Drupal::formBuilder()->getCache($this->formBuildId, $cached_form_state);
    $this->assertEquals($this->form['#property'], $cached_form['#property']);
    $this->assertNotEmpty($cached_form['#cache_token'], 'Form has a cache token');
    $this->assertEquals($this->formState->get('example'), $cached_form_state->get('example'));

    // Test that the form cache isn't loaded when the session/token has changed.
    // Change the private key. (We cannot change the session ID because this
    // will break the parent site test runner batch.)
    \Drupal::state()->set('system.private_key', 'invalid');
    $cached_form_state = new FormState();
    $cached_form = \Drupal::formBuilder()->getCache($this->formBuildId, $cached_form_state);
    $this->assertNull($cached_form, 'No form returned from cache');
    $cached_form_state_example = $cached_form_state->get('example');
    $this->assertEmpty($cached_form_state_example);

    // Test that loading the cache with a different form_id fails.
    $wrong_form_build_id = $this->randomMachineName(9);
    $cached_form_state = new FormState();
    $this->assertNull(\Drupal::formBuilder()->getCache($wrong_form_build_id, $cached_form_state), 'No form returned from cache');
    $cached_form_state_example = $cached_form_state->get('example');
    $this->assertEmpty($cached_form_state_example, 'Cached form state was not loaded');
  }

  /**
   * Tests the form cache without a logged-in user.
   */
  public function testNoCacheToken() {
    // Switch to an anonymous user account.
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(new AnonymousUserSession());

    $this->formState->set('example', $this->randomMachineName());
    \Drupal::formBuilder()->setCache($this->formBuildId, $this->form, $this->formState);

    $cached_form_state = new FormState();
    $cached_form = \Drupal::formBuilder()->getCache($this->formBuildId, $cached_form_state);
    $this->assertEquals($this->form['#property'], $cached_form['#property']);
    $this->assertArrayNotHasKey('#cache_token', $cached_form, 'Form has no cache token');
    $this->assertEquals($this->formState->get('example'), $cached_form_state->get('example'));

    // Restore user account.
    $account_switcher->switchBack();
  }

  /**
   * Tests the form cache with an overridden cache expiration.
   */
  public function testCacheCustomExpiration() {
    // Override form cache expiration so that the cached form expired yesterday.
    new Settings(['form_cache_expiration' => -1 * (24 * 60 * 60), 'hash_salt' => $this->randomMachineName()]);
    \Drupal::formBuilder()->setCache($this->formBuildId, $this->form, $this->formState);

    $cached_form_state = new FormState();
    $this->assertNull(\Drupal::formBuilder()->getCache($this->formBuildId, $cached_form_state), 'Expired form not returned from cache');
  }

}
