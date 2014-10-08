<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\AdvancedSettingsForm.
 */

namespace Drupal\views_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Form builder for the advanced admin settings page.
 */
class AdvancedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_admin_settings_advanced';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('views.settings');
    $form['cache'] = array(
      '#type' => 'details',
      '#title' => $this->t('Caching'),
      '#open' => TRUE,
    );

    $form['cache']['skip_cache'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable views data caching'),
      '#description' => $this->t("Views caches data about tables, modules and views available, to increase performance. By checking this box, Views will skip this cache and always rebuild this data when needed. This can have a serious performance impact on your site."),
      '#default_value' => $config->get('skip_cache'),
    );

    $form['cache']['clear_cache'] = array(
      '#type' => 'submit',
      '#value' => $this->t("Clear Views' cache"),
      '#submit' => array('::cacheSubmit'),
    );

    $form['debug'] = array(
      '#type' => 'details',
      '#title' => $this->t('Debugging'),
      '#open' => TRUE,
    );

    $form['debug']['sql_signature'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add Views signature to all SQL queries'),
      '#description' => $this->t("All Views-generated queries will include the name of the views and display 'view-name:display-name' as a string at the end of the SELECT clause. This makes identifying Views queries in database server logs simpler, but should only be used when troubleshooting."),

      '#default_value' => $config->get('sql_signature'),
    );

    $options = Views::fetchPluginNames('display_extender');
    if (!empty($options)) {
      $form['extenders'] = array(
        '#type' => 'details',
        '#open' => TRUE,
      );
      $form['extenders']['display_extenders'] = array(
        '#title' => $this->t('Display extenders'),
        '#default_value' => array_filter($config->get('display_extenders')),
        '#options' => $options,
        '#type' => 'checkboxes',
        '#description' => $this->t('Select extensions of the views interface.')
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('views.settings')
      ->set('skip_cache', $form_state->getValue('skip_cache'))
      ->set('sql_signature', $form_state->getValue('sql_signature'))
      ->set('display_extenders', $form_state->getValue('display_extenders', array()))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Submission handler to clear the Views cache.
   */
  public function cacheSubmit() {
    views_invalidate_cache();
    drupal_set_message($this->t('The cache has been cleared.'));
  }

}
