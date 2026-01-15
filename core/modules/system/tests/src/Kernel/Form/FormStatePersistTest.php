<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests that the form state persists across multiple requests.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class FormStatePersistTest extends KernelTestBase implements FormInterface {

  /**
   * Values retrieved from form state storage in the form submit handler.
   *
   * @var array{"build"?: bool, "process"?: bool, "rebuild"?: bool}
   */
  protected static array $submitStoragePersist = [];

  /**
   * Values retrieved from form state storage in the form post_render callback.
   *
   * @var array{"build"?: bool, "process"?: bool, "rebuild"?: bool}
   */
  protected static array $postRenderStoragePersist = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_test_state_persist';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'title',
      '#default_value' => 'DEFAULT',
      '#required' => TRUE,
    ];
    // Set a flag in form state storage during build, so this can be confirmed
    // in test assertions.
    $form_state->set('build', TRUE);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    $form['#process'][] = [static::class, 'setStateRebuildValue'];
    $form['#post_render'][] = [static::class, 'displayCachedState'];
    return $form;
  }

  /**
   * Form API #process callback.
   *
   * Set form state properties based on whether form is rebuilding.
   */
  public static function setStateRebuildValue(array $form, FormStateInterface $form_state): array {
    if (!$form_state->isRebuilding()) {
      $form_state->set('process', TRUE);
      return $form;
    }
    $form_state->set('rebuild', TRUE);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save the values retrieved from form state storage during form submit, so
    // the values can be confirmed in test assertions.
    static::$submitStoragePersist['build'] = (bool) $form_state->get('build');
    static::$submitStoragePersist['process'] = (bool) $form_state->get('process');
    static::$submitStoragePersist['rebuild'] = (bool) $form_state->get('rebuild');
    $form_state->setRebuild();
  }

  /**
   * Render API #post_render callback.
   *
   * After form is rendered, add status messages displaying form state
   * 'processed_value' and 'rebuilt_value'.
   */
  #[TrustedCallback]
  public static function displayCachedState(string $rendered_form, array $form): string {
    $form_state = new FormState();
    \Drupal::formBuilder()->getCache($form['#build_id'], $form_state);

    // Save the values retrieved from form state storage during post render, so
    // the values can be confirmed in test assertions.
    static::$postRenderStoragePersist['process'] = (bool) $form_state->get('process');
    static::$postRenderStoragePersist['rebuild'] = (bool) $form_state->get('rebuild');
    return $rendered_form;
  }

  /**
   * Test that form state persists correctly after being submitted and rebuilt.
   */
  public function testFormStatePersistence(): void {
    // Simulate the initial GET request without submitted values for a form.
    $form_state = new FormState();
    $form = \Drupal::formBuilder()->buildForm($this, $form_state);
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $renderer->renderRoot($form);
    // The form has a #post_render callback that displays whether form state
    // properties set during the #process callback are cached. On the first
    // request to the form, the values are not populated because there is no
    // form caching on a GET request.
    $this->assertFalse(static::$postRenderStoragePersist['process']);
    $this->assertFalse(static::$postRenderStoragePersist['rebuild']);

    // Simulate a form submission.
    $request = Request::create('/', 'POST', [
      'form_id' => $this->getFormId(),
      'form_build_id' => $form['#build_id'],
      'title' => 'DEFAULT',
    ]);
    $request->setSession(new Session(new MockArraySessionStorage()));
    \Drupal::requestStack()->push($request);
    $form_state = new FormState();
    static::$submitStoragePersist = [];
    static::$postRenderStoragePersist = [];
    $form = \Drupal::formBuilder()->buildForm($this, $form_state);
    // In the form submit handler, the form state 'build' property set in
    // buildForm should have persisted. The 'process' property set in the
    // #process callback should have persisted. The 'rebuild' property set
    // in the #process hook after form rebuild will not show as persisted,
    // because that value gets set after the submit handler has run.
    $this->assertTrue(static::$submitStoragePersist['build']);
    $this->assertTrue(static::$submitStoragePersist['process']);
    $this->assertFalse(static::$submitStoragePersist['rebuild']);

    // Values set in the#post_render callback should now show that 'process' and
    // 'rebuild' form state properties are now cached.
    static::$submitStoragePersist = [];
    static::$postRenderStoragePersist = [];
    $renderer->renderRoot($form);
    $this->assertTrue(static::$postRenderStoragePersist['process']);
    $this->assertTrue(static::$postRenderStoragePersist['rebuild']);

    // Submit the form again to show continued persistence.
    $form_state = new FormState();
    static::$submitStoragePersist = [];
    static::$postRenderStoragePersist = [];

    $request->request->set('form_build_id', $form['#build_id']);
    $form = \Drupal::formBuilder()->buildForm($this, $form_state);
    // After submitting the form a second time, the 'rebuild' property set
    // during the rebuild after the first submission should have persisted in
    // the cache.
    $this->assertTrue(static::$submitStoragePersist['build']);
    $this->assertTrue(static::$submitStoragePersist['process']);
    $this->assertTrue(static::$submitStoragePersist['rebuild']);

    $renderer->renderRoot($form);
    $this->assertTrue(static::$postRenderStoragePersist['process']);
    $this->assertTrue(static::$postRenderStoragePersist['rebuild']);
  }

}
