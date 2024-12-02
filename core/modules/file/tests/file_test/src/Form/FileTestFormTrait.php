<?php

declare(strict_types=1);

namespace Drupal\file_test\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * This trait provides common code common for Forms.
 */
trait FileTestFormTrait {

  /**
   * Adds common form elements to the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function baseForm(array $form, FormStateInterface $form_state): array {

    $form['file_test_replace'] = [
      '#type' => 'select',
      '#title' => t('Replace existing image'),
      '#options' => [
        FileExists::Rename->name => new TranslatableMarkup('Appends number until name is unique'),
        FileExists::Replace->name => new TranslatableMarkup('Replace the existing file'),
        FileExists::Error->name => new TranslatableMarkup('Fail with an error'),
      ],
      '#default_value' => FileExists::Rename->name,
    ];
    $form['file_subdir'] = [
      '#type' => 'textfield',
      '#title' => t('Subdirectory for test file'),
      '#default_value' => '',
    ];

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions.'),
      '#default_value' => '',
    ];

    $form['allow_all_extensions'] = [
      '#title' => t('Allow all extensions?'),
      '#type' => 'radios',
      '#options' => [
        'false' => 'No',
        'empty_array' => 'Empty array',
        'empty_string' => 'Empty string',
      ],
      '#default_value' => 'false',
    ];

    $form['is_image_file'] = [
      '#type' => 'checkbox',
      '#title' => t('Is this an image file?'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

}
