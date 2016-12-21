<?php

namespace Drupal\ckeditor_test\Form;

use Drupal\ckeditor\Ajax\AddStyleSheetCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form for testing delivery of CSS to CKEditor via AJAX.
 */
class AjaxCssForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_test_ajax_css_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create an inline and iframe CKEditor instance so we can test against
    // both.
    $form['inline'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'ckeditor_test/ajax_css',
        ],
      ],
      '#children' => $this->t('Here be dragons.'),
    ];
    $form['iframe'] = [
      '#type' => 'text_format',
      '#default_value' => $this->t('Here be llamas.'),
    ];

    // A pair of buttons to trigger the AJAX events.
    $form['actions'] = [
      'css_inline' => [
        '#type' => 'submit',
        '#value' => $this->t('Add CSS to inline CKEditor instance'),
        '#ajax' => [
          'callback' => [$this, 'addCssInline'],
        ],
      ],
      'css_frame' => [
        '#type' => 'submit',
        '#value' => $this->t('Add CSS to iframe CKEditor instance'),
        '#ajax' => [
          'callback' => [$this, 'addCssIframe'],
        ],
      ],
      '#type' => 'actions',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here.
  }

  /**
   * Generates an AJAX response to add CSS to a CKEditor Text Editor instance.
   *
   * @param string $editor_id
   *   The Text Editor instance ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  protected function generateResponse($editor_id) {
    // Build a URL to the style sheet that will be added.
    $url = drupal_get_path('module', 'ckeditor_test') . '/css/test.css';
    $url = file_create_url($url);
    $url = file_url_transform_relative($url);

    $response = new AjaxResponse();
    return $response
      ->addCommand(new AddStyleSheetCommand($editor_id, [$url]));
  }

  /**
   * Handles the AJAX request to add CSS to the inline editor.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function addCssInline() {
    return $this->generateResponse('edit-inline');
  }

  /**
   * Handles the AJAX request to add CSS to the iframe editor.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function addCssIframe() {
    return $this->generateResponse('edit-iframe-value');
  }

}
