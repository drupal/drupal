<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\BasicSettingsForm.
 */

namespace Drupal\views_ui\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Form builder for the admin display defaults page.
 */
class BasicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_admin_settings_basic';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('views.settings');
    $options = array();
    foreach (list_themes() as $name => $theme) {
      if ($theme->status) {
        $options[$name] = $theme->info['name'];
      }
    }

    // This is not currently a fieldset but we may want it to be later,
    // so this will make it easier to change if we do.
    $form['basic'] = array();

    $form['basic']['ui_show_master_display'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Always show the master (default) display'),
      '#default_value' => $config->get('ui.show.master_display'),
    );

    $form['basic']['ui_show_advanced_column'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Always show advanced display settings'),
      '#default_value' => $config->get('ui.show.advanced_column'),
    );

    $form['basic']['ui_show_display_embed'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow embedded displays'),
      '#description' => t('Embedded displays can be used in code via views_embed_view().'),
      '#default_value' => $config->get('ui.show.display_embed'),
    );

    $form['basic']['ui_exposed_filter_any_label'] = array(
      '#type' => 'select',
      '#title' => $this->t('Label for "Any" value on non-required single-select exposed filters'),
      '#options' => array('old_any' => '<Any>', 'new_any' => $this->t('- Any -')),
      '#default_value' => $config->get('ui.exposed_filter_any_label'),
    );

    $form['live_preview'] = array(
      '#type' => 'details',
      '#title' => $this->t('Live preview settings'),
      '#open' => TRUE,
    );

    $form['live_preview']['ui_always_live_preview'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically update preview on changes'),
      '#default_value' => $config->get('ui.always_live_preview'),
    );

    $form['live_preview']['ui_show_preview_information'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show information and statistics about the view during live preview'),
      '#default_value' => $config->get('ui.show.preview_information'),
    );

    $form['live_preview']['options'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(
          ':input[name="ui_show_preview_information"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['live_preview']['options']['ui_show_sql_query_where'] = array(
      '#type' => 'radios',
      '#title' => t('Show SQL query'),
      '#options' => array(
        'above' => $this->t('Above the preview'),
        'below' => $this->t('Below the preview'),
      ),
      '#default_value' => $config->get('ui.show.sql_query.where'),
    );

    $form['live_preview']['options']['ui_show_sql_query_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the SQL query'),
      '#default_value' => $config->get('ui.show.sql_query.enabled'),
    );
    $form['live_preview']['options']['ui_show_performance_statistics'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show performance statistics'),
      '#default_value' => $config->get('ui.show.performance_statistics'),
    );

    $form['live_preview']['options']['ui_show_additional_queries'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show other queries run during render during live preview'),
      '#description' => $this->t("Drupal has the potential to run many queries while a view is being rendered. Checking this box will display every query run during view render as part of the live preview."),
      '#default_value' => $config->get('ui.show.additional_queries'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('views.settings')
      ->set('ui.show.master_display', $form_state['values']['ui_show_master_display'])
      ->set('ui.show.advanced_column', $form_state['values']['ui_show_advanced_column'])
      ->set('ui.show.display_embed', $form_state['values']['ui_show_display_embed'])
      ->set('ui.exposed_filter_any_label', $form_state['values']['ui_exposed_filter_any_label'])
      ->set('ui.always_live_preview', $form_state['values']['ui_always_live_preview'])
      ->set('ui.show.preview_information', $form_state['values']['ui_show_preview_information'])
      ->set('ui.show.sql_query.where', $form_state['values']['ui_show_sql_query_where'])
      ->set('ui.show.sql_query.enabled', $form_state['values']['ui_show_sql_query_enabled'])
      ->set('ui.show.performance_statistics', $form_state['values']['ui_show_performance_statistics'])
      ->set('ui.show.additional_queries', $form_state['values']['ui_show_additional_queries'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
