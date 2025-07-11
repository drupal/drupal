<?php

declare(strict_types=1);

namespace Drupal\package_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures Package Manager settings.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'package_manager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['package_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['executables'] = [
      '#type' => 'details',
      '#title' => $this->t('Executable paths'),
      '#open' => TRUE,
      '#description' => $this->t('Configure the paths to required executables.'),
    ];
    $trim = fn (string $value): string => trim($value);

    $form['executables']['composer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Composer executable path'),
      '#config_target' => new ConfigTarget(
        'package_manager.settings',
        'executables.composer',
        toConfig: $trim,
      ),
      '#description' => $this->t('The full path to the <code>composer</code> executable (e.g., <code>/usr/local/bin/composer</code>).'),
      '#required' => TRUE,
    ];

    $form['executables']['rsync'] = [
      '#type' => 'textfield',
      '#title' => $this->t('rsync executable path'),
      '#config_target' => new ConfigTarget(
        'package_manager.settings',
        'executables.rsync',
        toConfig: $trim,
      ),
      '#description' => $this->t('The full path to the <code>rsync</code> executable (e.g., <code>/usr/bin/rsync</code>).'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

}
