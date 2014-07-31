<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Form\SelectProfileForm.
 */

namespace Drupal\Core\Installer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the profile selection form.
 */
class SelectProfileForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'install_select_profile_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $install_state = NULL) {
    $form['#title'] = $this->t('Select an installation profile');

    $profiles = array();
    $names = array();
    foreach ($install_state['profiles'] as $profile) {
      /** @var $profile \Drupal\Core\Extension\Extension */
      $details = install_profile_info($profile->getName());
      // Don't show hidden profiles. This is used by to hide the testing profile,
      // which only exists to speed up test runs.
      if ($details['hidden'] === TRUE && !drupal_valid_test_ua()) {
        continue;
      }
      $profiles[$profile->getName()] = $details;

      // Determine the name of the profile; default to file name if defined name
      // is unspecified.
      $name = isset($details['name']) ? $details['name'] : $profile->getName();
      $names[$profile->getName()] = $name;
    }

    // Display radio buttons alphabetically by human-readable name, but always
    // put the core profiles first (if they are present in the filesystem).
    natcasesort($names);
    if (isset($names['minimal'])) {
      // If the expert ("Minimal") core profile is present, put it in front of
      // any non-core profiles rather than including it with them alphabetically,
      // since the other profiles might be intended to group together in a
      // particular way.
      $names = array('minimal' => $names['minimal']) + $names;
    }
    if (isset($names['standard'])) {
      // If the default ("Standard") core profile is present, put it at the very
      // top of the list. This profile will have its radio button pre-selected,
      // so we want it to always appear at the top.
      $names = array('standard' => $names['standard']) + $names;
    }

    // The profile name and description are extracted for translation from the
    // .info file, so we can use $this->t() on them even though they are dynamic
    // data at this point.
    $form['profile'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Select an installation profile'),
      '#title_display' => 'invisible',
      '#options' => array_map(array($this, 't'), $names),
      '#default_value' => 'standard',
    );
    foreach (array_keys($names) as $profile_name) {
      $form['profile'][$profile_name]['#description'] = isset($profiles[$profile_name]['description']) ? $this->t($profiles[$profile_name]['description']) : '';
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] =  array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $install_state;
    $install_state['parameters']['profile'] = $form_state['values']['profile'];
  }

}
