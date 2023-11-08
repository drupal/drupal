<?php

namespace Drupal\views_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the admin display defaults page.
 *
 * @internal
 */
class BasicSettingsForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a \Drupal\views_ui\Form\BasicSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, ThemeHandlerInterface $theme_handler) {
    parent::__construct($config_factory, $typedConfigManager);

    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('theme_handler')
    );
  }

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

    $options = [];
    foreach ($this->themeHandler->listInfo() as $name => $theme) {
      if ($theme->status) {
        $options[$name] = $theme->info['name'];
      }
    }

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
