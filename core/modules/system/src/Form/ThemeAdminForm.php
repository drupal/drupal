<?php
/**
 * @file
 * Contains \Drupal\system\Form\ThemeAdminForm
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to select the administration theme.
 */
class ThemeAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_themes_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $theme_options = NULL) {
    // Administration theme settings.
    $form['admin_theme'] = array(
      '#type' => 'details',
      '#title' => $this->t('Administration theme'),
      '#open' => TRUE,
    );
    $form['admin_theme']['admin_theme'] = array(
      '#type' => 'select',
      '#options' => array(0 => $this->t('Default theme')) + $theme_options,
      '#title' => $this->t('Administration theme'),
      '#description' => $this->t('Choose "Default theme" to always use the same theme as the rest of the site.'),
      '#default_value' => $this->config('system.theme')->get('admin'),
    );
    $form['admin_theme']['actions'] = array('#type' => 'actions');
    $form['admin_theme']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The configuration options have been saved.'));
    $this->config('system.theme')->set('admin', $form_state->getValue('admin_theme'))->save();
  }

}
