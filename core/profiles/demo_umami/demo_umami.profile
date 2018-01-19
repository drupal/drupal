<?php

/**
 * @file
 * Enables modules and site configuration for a demo_umami site installation.
 */

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function demo_umami_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $form['site_information']['site_name']['#default_value'] = 'Umami Food Magazine';
  $form['#submit'][] = 'demo_umami_form_install_configure_submit';
}

/**
 * Submission handler to sync the contact.form.feedback recipient.
 */
function demo_umami_form_install_configure_submit($form, FormStateInterface $form_state) {
  $site_mail = $form_state->getValue('site_mail');
  ContactForm::load('feedback')->setRecipients([$site_mail])->trustData()->save();
}

/**
 * Implements hook_toolbar().
 */
function demo_umami_toolbar() {
  // Add a warning about using an experimental profile.
  // @todo: This can be removed once a generic warning for experimental profiles has been introduced.
  // @see https://www.drupal.org/project/drupal/issues/2934374
  $items['experimental-profile-warning'] = [
    '#type' => 'toolbar_item',
    'tab' => [
      '#type' => 'inline_template',
      '#template' => '<a class="toolbar-warning" href="{{ more_info_link }}">This installation is for demonstration purposes only.</a>',
      '#context' => [
        'more_info_link' => 'https://www.drupal.org/project/drupal/issues/2829101',
      ],
      '#attached' => [
        'library' => ['demo_umami/toolbar-warning'],
      ],
    ],
    '#weight' => 999,
  ];
  return $items;
}
