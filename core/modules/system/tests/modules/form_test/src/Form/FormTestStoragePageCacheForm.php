<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to test page cache storage.
 *
 * @internal
 */
class FormTestStoragePageCacheForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_storage_page_cache';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'Title',
      '#required' => TRUE,
    ];

    $form['test_build_id_old'] = [
      '#type' => 'item',
      '#title' => 'Old build id',
      '#markup' => 'No old build id',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    $form['rebuild'] = [
      '#type' => 'submit',
      '#value' => 'Rebuild',
      '#submit' => [[$this, 'form_test_storage_page_cache_rebuild']],
    ];

    $form['#after_build'] = [[$this, 'formTestStoragePageCacheOldBuildId']];

    return $form;
  }

  /**
   * Implements #after_build callback for ::buildForm().
   */
  public static function formTestStoragePageCacheOldBuildId(array &$form, FormStateInterface $formState) {
    if (isset($form['#build_id_old'])) {
      $form['test_build_id_old']['#plain_text'] = $form['#build_id_old'];
    }
    return $form;
  }

  /**
   * Form submit callback: Rebuild the form and continue.
   */
  public function form_test_storage_page_cache_rebuild($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Test using form cache when re-displaying a form due to validation
    // errors.
    if ($form_state->hasAnyErrors()) {
      $form_state->setCached();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing must happen.
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    $callbacks = parent::trustedCallbacks();
    $callbacks[] = 'formTestStoragePageCacheOldBuildId';
    return $callbacks;
  }

}
