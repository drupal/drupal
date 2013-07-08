<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\BasicSettingsForm.
 */

namespace Drupal\views_ui\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Form builder for the admin display defaults page.
 */
class BasicSettingsForm extends SystemConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_admin_settings_basic';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->configFactory->get('views.settings');
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
      '#title' => t('Always show the master display'),
      '#description' => t('Advanced users of views may choose to see the master (i.e. default) display.'),
      '#default_value' => $config->get('ui.show.master_display'),
    );

    $form['basic']['ui_show_advanced_column'] = array(
      '#type' => 'checkbox',
      '#title' => t('Always show advanced display settings'),
      '#description' => t('Default to showing advanced display settings, such as relationships and contextual filters.'),
      '#default_value' => $config->get('ui.show.advanced_column'),
    );

    $form['basic']['ui_show_display_embed'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the embed display in the ui.'),
      '#description' => t("Allow advanced user to use the embed view display. The plugin itself works if it's not visible in the ui"),
      '#default_value' => $config->get('ui.show.display_embed'),
    );

    $form['basic']['ui_exposed_filter_any_label'] = array(
      '#type' => 'select',
      '#title' => t('Label for "Any" value on non-required single-select exposed filters'),
      '#options' => array('old_any' => '<Any>', 'new_any' => t('- Any -')),
      '#default_value' => $config->get('ui.exposed_filter_any_label'),
    );

    $form['live_preview'] = array(
      '#type' => 'details',
      '#title' => t('Live preview settings'),
    );

    $form['live_preview']['ui_always_live_preview'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically update preview on changes'),
      '#default_value' => $config->get('ui.always_live_preview'),
    );

    $form['live_preview']['ui_show_preview_information'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show information and statistics about the view during live preview'),
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
      '#options' => array(
        'above' => t('Above the preview'),
        'below' => t('Below the preview'),
      ),
      '#default_value' => $config->get('ui.show.sql_query.where'),
    );

    $form['live_preview']['options']['ui_show_sql_query_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the SQL query'),
      '#default_value' => $config->get('ui.show.sql_query.enabled'),
    );
    $form['live_preview']['options']['ui_show_performance_statistics'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show performance statistics'),
      '#default_value' => $config->get('ui.show.performance_statistics'),
    );

    $form['live_preview']['options']['ui_show_additional_queries'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show other queries run during render during live preview'),
      '#description' => t("Drupal has the potential to run many queries while a view is being rendered. Checking this box will display every query run during view render as part of the live preview."),
      '#default_value' => $config->get('ui.show.additional_queries'),
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('views.settings')
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
  }

}
