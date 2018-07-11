<?php

namespace Drupal\Core\Installer\Form;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the profile selection form.
 *
 * @internal
 */
class SelectProfileForm extends FormBase {

  /**
   * The key used in the profile list for the install from config option.
   *
   * This key must not be a valid profile extension name.
   */
  const CONFIG_INSTALL_PROFILE_KEY = '::existing_config::';

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
    global $config_directories;
    $form['#title'] = $this->t('Select an installation profile');

    $profiles = [];
    $names = [];
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
      $names = ['minimal' => $names['minimal']] + $names;
    }
    if (isset($names['standard'])) {
      // If the default ("Standard") core profile is present, put it at the very
      // top of the list. This profile will have its radio button pre-selected,
      // so we want it to always appear at the top.
      $names = ['standard' => $names['standard']] + $names;
    }

    // The profile name and description are extracted for translation from the
    // .info file, so we can use $this->t() on them even though they are dynamic
    // data at this point.
    $form['profile'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an installation profile'),
      '#title_display' => 'invisible',
      '#options' => array_map([$this, 't'], $names),
      '#default_value' => 'standard',
    ];
    foreach (array_keys($names) as $profile_name) {
      $form['profile'][$profile_name]['#description'] = isset($profiles[$profile_name]['description']) ? $this->t($profiles[$profile_name]['description']) : '';
      // @todo Remove hardcoding of 'demo_umami' profile for a generic warning
      // system in https://www.drupal.org/project/drupal/issues/2822414.
      if ($profile_name === 'demo_umami') {
        $this->addUmamiWarning($form);
      }
    }

    if (!empty($config_directories[CONFIG_SYNC_DIRECTORY])) {
      $sync = new FileStorage($config_directories[CONFIG_SYNC_DIRECTORY]);
      $extensions = $sync->read('core.extension');
      $site = $sync->read('system.site');
      if (isset($site['name']) && isset($extensions['profile']) && in_array($extensions['profile'], array_keys($names), TRUE)) {
        // Ensure the the profile can be installed from configuration. Install
        // profile's which implement hook_INSTALL() are not supported.
        // @todo https://www.drupal.org/project/drupal/issues/2982052 Remove
        //   this restriction.
        module_load_install($extensions['profile']);
        if (!function_exists($extensions['profile'] . '_install')) {
          $form['profile']['#options'][static::CONFIG_INSTALL_PROFILE_KEY] = $this->t('Use existing configuration');
          $form['profile'][static::CONFIG_INSTALL_PROFILE_KEY]['#description'] = [
            'description' => [
              '#markup' => $this->t('Install %name using existing configuration.', ['%name' => $site['name']]),
            ],
            'info' => [
              '#type' => 'item',
              '#markup' => $this->t('The configuration from the directory %sync_directory will be used.', ['%sync_directory' => $config_directories[CONFIG_SYNC_DIRECTORY]]),
              '#wrapper_attributes' => [
                'class' => ['messages', 'messages--status'],
              ],
              '#states' => [
                'visible' => [
                  ':input[name="profile"]' => ['value' => static::CONFIG_INSTALL_PROFILE_KEY],
                ],
              ],
            ],
          ];
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $install_state, $config_directories;
    $profile = $form_state->getValue('profile');
    if ($profile === static::CONFIG_INSTALL_PROFILE_KEY) {
      $sync = new FileStorage($config_directories[CONFIG_SYNC_DIRECTORY]);
      $profile = $sync->read('core.extension')['profile'];
      $install_state['parameters']['existing_config'] = TRUE;
    }
    $install_state['parameters']['profile'] = $profile;
  }

  /**
   * Show profile warning if 'demo_umami' profile is selected.
   */
  protected function addUmamiWarning(array &$form) {
    // Warning to show when this profile is selected.
    $description = $form['profile']['demo_umami']['#description'];
    // Re-defines radio #description to show warning when selected.
    $form['profile']['demo_umami']['#description'] = [
      'warning' => [
        '#type' => 'item',
        '#markup' => $this->t('This profile is intended for demonstration purposes only.'),
        '#wrapper_attributes' => [
          'class' => ['messages', 'messages--warning'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="profile"]' => ['value' => 'demo_umami'],
          ],
        ],
      ],
      'description' => ['#markup' => $description],
    ];
  }

}
