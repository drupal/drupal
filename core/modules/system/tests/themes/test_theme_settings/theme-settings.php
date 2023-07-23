<?php

/**
 * @file
 * Test to ensure theme compatibility with managed files.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function test_theme_settings_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  $form['custom_logo'] = [
    '#type' => 'managed_file',
    '#title' => t('Secondary logo.'),
    '#default_value' => theme_get_setting('custom_logo'),
    '#progress_indicator' => 'bar',
    '#progress_message'   => t('Processing...'),
    '#upload_location' => 'public://test',
    '#upload_validators'  => [
      'FileExtension' => ['extensions' => 'gif png jpg jpeg'],
    ],
  ];

  $form['multi_file'] = [
    '#type' => 'managed_file',
    '#title' => t('Multiple file field with all file extensions'),
    '#multiple' => TRUE,
    '#default_value' => theme_get_setting('multi_file'),
    '#upload_location' => 'public://test',
    '#upload_validators'  => [
      'FileExtension' => [],
    ],
  ];

  $form['#submit'][] = 'test_theme_settings_form_system_theme_settings_submit';
}

/**
 * Test theme form settings submission handler.
 */
function test_theme_settings_form_system_theme_settings_submit(&$form, FormStateInterface $form_state) {
  if ($file_id = $form_state->getValue(['custom_logo', '0'])) {
    $file = File::load($file_id);
    $file->setPermanent();
    $file->save();
  }
}
