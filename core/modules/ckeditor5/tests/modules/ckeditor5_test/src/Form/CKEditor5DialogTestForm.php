<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_test\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form with CKEditor 5 and a link that opens a modal dialog.
 *
 * The editor on this page is initialized before the dialog opens. This places
 * the CKEditor 5 body wrapper before the jQuery UI dialog in the DOM.
 */
class CKEditor5DialogTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ckeditor5_test_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['text'] = [
      '#type' => 'text_format',
      '#title' => 'Text',
      '#format' => 'test_format',
      '#allowed_formats' => ['test_format'],
    ];
    $form['link'] = [
      '#type' => 'link',
      '#title' => 'Add Node',
      '#url' => Url::fromRoute('entity.node.add_form', ['node_type' => 'page']),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-options' => Json::encode([
          'width' => 700,
          'modal' => TRUE,
          'autoResize' => TRUE,
        ]),
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
