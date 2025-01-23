<?php

namespace Drupal\views_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;

/**
 * Form builder for the admin display defaults page.
 *
 * @internal
 */
class BasicSettingsForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_admin_settings_basic';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // This is not currently a fieldset but we may want it to be later,
    // so this will make it easier to change if we do.
    $form['basic'] = [];

    $form['basic']['ui_show_default_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show the default display'),
      '#config_target' => 'views.settings:ui.show.default_display',
    ];

    $form['basic']['ui_show_advanced_column'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show advanced display settings'),
      '#config_target' => 'views.settings:ui.show.advanced_column',
    ];

    $form['basic']['ui_show_display_embed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow embedded displays'),
      '#description' => $this->t('Embedded displays can be used in code via views_embed_view().'),
      '#config_target' => 'views.settings:ui.show.display_embed',
    ];

    $form['basic']['ui_exposed_filter_any_label'] = [
      '#type' => 'select',
      '#title' => $this->t('Label for "Any" value on non-required single-select exposed filters'),
      '#options' => ['old_any' => '<Any>', 'new_any' => $this->t('- Any -')],
      '#config_target' => 'views.settings:ui.exposed_filter_any_label',
    ];

    $form['live_preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Live preview settings'),
      '#open' => TRUE,
    ];

    $form['live_preview']['ui_always_live_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically update preview on changes'),
      '#config_target' => 'views.settings:ui.always_live_preview',
    ];

    $form['live_preview']['ui_show_preview_information'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show information and statistics about the view during live preview'),
      '#config_target' => 'views.settings:ui.show.preview_information',
    ];

    $form['live_preview']['options'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="ui_show_preview_information"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['live_preview']['options']['ui_show_sql_query_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the SQL query'),
      '#config_target' => 'views.settings:ui.show.sql_query.enabled',
    ];

    $form['live_preview']['options']['ui_show_sql_query_where'] = [
      '#type' => 'radios',
      '#states' => [
        'visible' => [
          ':input[name="ui_show_sql_query_enabled"]' => ['checked' => TRUE],
        ],
      ],
      '#title' => $this->t('Show SQL query'),
      '#options' => [
        'above' => $this->t('Above the preview'),
        'below' => $this->t('Below the preview'),
      ],
      '#config_target' => 'views.settings:ui.show.sql_query.where',
    ];

    $form['live_preview']['options']['ui_show_performance_statistics'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show performance statistics'),
      '#config_target' => 'views.settings:ui.show.performance_statistics',
    ];

    $form['live_preview']['options']['ui_show_additional_queries'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show other queries run during render during live preview'),
      '#description' => $this->t("Drupal has the potential to run many queries while a view is being rendered. Checking this box will display every query run during view render as part of the live preview."),
      '#config_target' => 'views.settings:ui.show.additional_queries',
    ];

    return $form;
  }

}
