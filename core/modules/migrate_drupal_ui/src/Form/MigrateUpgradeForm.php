<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal_ui\MigrateUpgradeRunBatch;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a multi-step form for performing direct site upgrades.
 */
class MigrateUpgradeForm extends ConfirmFormBase {

  use MigrationConfigurationTrait;

  /**
   * Mapping of known migrations and their source and destination modules.
   *
   * @todo https://www.drupal.org/node/2569805 Hardcoding this information is
   *   not robust - the migrations themselves should hold the necessary
   *   information.
   *
   * @var array[]
   */
  protected $moduleUpgradePaths = [
    'd6_action_settings' => [
      'source_module' => 'system',
      'destination_module' => 'action',
    ],
    'd6_aggregator_feed' => [
      'source_module' => 'aggregator',
      'destination_module' => 'aggregator',
    ],
    'd6_aggregator_item' => [
      'source_module' => 'aggregator',
      'destination_module' => 'aggregator',
    ],
    'd6_aggregator_settings' => [
      'source_module' => 'aggregator',
      'destination_module' => 'aggregator',
    ],
    'd7_aggregator_feed' => [
      'source_module' => 'aggregator',
      'destination_module' => 'aggregator',
    ],
    'd7_aggregator_item' => [
      'source_module' => 'aggregator',
      'destination_module' => 'aggregator',
    ],
    'd7_aggregator_settings' => [
      'source_module' => 'aggregator',
      'destination_module' => 'aggregator',
    ],
    'd7_blocked_ips' => [
      'source_module' => 'system',
      'destination_module' => 'ban',
    ],
    'd6_block' => [
      'source_module' => 'block',
      'destination_module' => 'block',
    ],
    'd7_block' => [
      'source_module' => 'block',
      'destination_module' => 'block',
    ],
    'block_content_body_field' => [
      'source_module' => 'block',
      'destination_module' => 'block_content',
    ],
    'block_content_type' => [
      'source_module' => 'block',
      'destination_module' => 'block_content',
    ],
    'd6_custom_block' => [
      'source_module' => 'block',
      'destination_module' => 'block_content',
    ],
    'd7_custom_block' => [
      'source_module' => 'block',
      'destination_module' => 'block_content',
    ],
    'd6_book' => [
      'source_module' => 'book',
      'destination_module' => 'book',
    ],
    'd6_book_settings' => [
      'source_module' => 'book',
      'destination_module' => 'book',
    ],
    'd6_comment' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd6_comment_entity_display' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd6_comment_entity_form_display' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd6_comment_entity_form_display_subject' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd6_comment_field' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd6_comment_field_instance' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd6_comment_type' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment_entity_display' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment_entity_form_display' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment_entity_form_display_subject' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment_field' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment_field_instance' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'd7_comment_type' => [
      'source_module' => 'comment',
      'destination_module' => 'comment',
    ],
    'contact_category' => [
      'source_module' => 'contact',
      'destination_module' => 'contact',
    ],
    'd6_contact_settings' => [
      'source_module' => 'contact',
      'destination_module' => 'contact',
    ],
    'd7_contact_settings' => [
      'source_module' => 'contact',
      'destination_module' => 'contact',
    ],
    'd6_dblog_settings' => [
      'source_module' => 'dblog',
      'destination_module' => 'dblog',
    ],
    'd7_dblog_settings' => [
      'source_module' => 'dblog',
      'destination_module' => 'dblog',
    ],
    'd6_field' => [
      'source_module' => 'content',
      'destination_module' => 'field',
    ],
    'd6_field_formatter_settings' => [
      'source_module' => 'content',
      'destination_module' => 'field',
    ],
    'd6_field_instance' => [
      'source_module' => 'content',
      'destination_module' => 'field',
    ],
    'd6_field_instance_widget_settings' => [
      'source_module' => 'content',
      'destination_module' => 'field',
    ],
    'd7_field' => [
      'source_module' => 'field',
      'destination_module' => 'field',
    ],
    'd7_field_formatter_settings' => [
      'source_module' => 'field',
      'destination_module' => 'field',
    ],
    'd7_field_instance' => [
      'source_module' => 'field',
      'destination_module' => 'field',
    ],
    'd7_field_instance_widget_settings' => [
      'source_module' => 'field',
      'destination_module' => 'field',
    ],
    'd7_view_modes' => [
      'source_module' => 'field',
      'destination_module' => 'field',
    ],
    'd6_file' => [
      'source_module' => 'system',
      'destination_module' => 'file',
    ],
    'd6_file_settings' => [
      'source_module' => 'system',
      'destination_module' => 'file',
    ],
    'd6_upload' => [
      'source_module' => 'upload',
      'destination_module' => 'file',
    ],
    'd6_upload_entity_display' => [
      'source_module' => 'upload',
      'destination_module' => 'file',
    ],
    'd6_upload_entity_form_display' => [
      'source_module' => 'upload',
      'destination_module' => 'file',
    ],
    'd6_upload_field' => [
      'source_module' => 'upload',
      'destination_module' => 'file',
    ],
    'd6_upload_field_instance' => [
      'source_module' => 'upload',
      'destination_module' => 'file',
    ],
    'd7_file' => [
      'source_module' => 'file',
      'destination_module' => 'file',
    ],
    'd6_filter_format' => [
      'source_module' => 'filter',
      'destination_module' => 'filter',
    ],
    'd7_filter_format' => [
      'source_module' => 'filter',
      'destination_module' => 'filter',
    ],
    'd6_forum_settings' => [
      'source_module' => 'forum',
      'destination_module' => 'forum',
    ],
    'd7_forum_settings' => [
      'source_module' => 'forum',
      'destination_module' => 'forum',
    ],
    'd6_imagecache_presets' => [
      'source_module' => 'imagecache',
      'destination_module' => 'image',
    ],
    'd7_image_settings' => [
      'source_module' => 'image',
      'destination_module' => 'image',
    ],
    'd7_image_styles' => [
      'source_module' => 'image',
      'destination_module' => 'image',
    ],
    'd6_language_content_settings' => [
      'source_module' => 'locale',
      'destination_module' => 'language',
    ],
    'd7_language_content_settings' => [
      'source_module' => 'locale',
      'destination_module' => 'language',
    ],
    'd7_language_negotiation_settings' => [
      'source_module' => 'locale',
      'destination_module' => 'language',
    ],
    'language' => [
      'source_module' => 'locale',
      'destination_module' => 'language',
    ],
    'locale_settings' => [
      'source_module' => 'locale',
      'destination_module' => 'locale',
    ],
    'menu_links' => [
      'source_module' => 'menu',
      'destination_module' => 'menu_link_content',
    ],
    'menu_settings' => [
      'source_module' => 'menu',
      'destination_module' => 'menu_ui',
    ],
    'd6_node' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_translation' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_revision' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_setting_promote' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_setting_status' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_setting_sticky' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_settings' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_node_type' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_view_modes' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd7_node' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd7_node_revision' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd7_node_settings' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd7_node_title_label' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd7_node_type' => [
      'source_module' => 'node',
      'destination_module' => 'node',
    ],
    'd6_url_alias' => [
      'source_module' => 'path',
      'destination_module' => 'path',
    ],
    'd7_url_alias' => [
      'source_module' => 'path',
      'destination_module' => 'path',
    ],
    'search_page' => [
      'source_module' => 'search',
      'destination_module' => 'search',
    ],
    'd6_search_settings' => [
      'source_module' => 'search',
      'destination_module' => 'search',
    ],
    'd7_search_settings' => [
      'source_module' => 'search',
      'destination_module' => 'search',
    ],
    'd7_shortcut' => [
      'source_module' => 'shortcut',
      'destination_module' => 'shortcut',
    ],
    'd7_shortcut_set' => [
      'source_module' => 'shortcut',
      'destination_module' => 'shortcut',
    ],
    'd7_shortcut_set_users' => [
      'source_module' => 'shortcut',
      'destination_module' => 'shortcut',
    ],
    'd6_simpletest_settings' => [
      'source_module' => 'simpletest',
      'destination_module' => 'simpletest',
    ],
    'd7_simpletest_settings' => [
      'source_module' => 'simpletest',
      'destination_module' => 'simpletest',
    ],
    'd6_statistics_settings' => [
      'source_module' => 'statistics',
      'destination_module' => 'statistics',
    ],
    'd6_syslog_settings' => [
      'source_module' => 'syslog',
      'destination_module' => 'syslog',
    ],
    'd7_syslog_settings' => [
      'source_module' => 'syslog',
      'destination_module' => 'syslog',
    ],
    'd6_date_formats' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_cron' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_date' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_file' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_image' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_image_gd' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_logging' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_maintenance' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_performance' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_rss' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'd6_system_site' => [
      'source_module' => 'system',
      'destination_module' => 'system',
    ],
    'menu' => [
      'source_module' => 'menu',
      'destination_module' => 'system',
    ],
    'taxonomy_settings' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_taxonomy_term' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_taxonomy_vocabulary' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_term_node' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_term_node_revision' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_vocabulary_entity_display' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_vocabulary_entity_form_display' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_vocabulary_field' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd6_vocabulary_field_instance' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd7_taxonomy_term' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'd7_taxonomy_vocabulary' => [
      'source_module' => 'taxonomy',
      'destination_module' => 'taxonomy',
    ],
    'text_settings' => [
      'source_module' => 'text',
      'destination_module' => 'text',
    ],
    'd7_tracker_node' => [
      'source_module' => 'tracker',
      'destination_module' => 'tracker',
    ],
    'd7_tracker_settings' => [
      'source_module' => 'tracker',
      'destination_module' => 'tracker',
    ],
    'd7_tracker_user' => [
      'source_module' => 'tracker',
      'destination_module' => 'tracker',
    ],
    'update_settings' => [
      'source_module' => 'update',
      'destination_module' => 'update',
    ],
    'd6_profile_values' => [
      'source_module' => 'profile',
      'destination_module' => 'user',
    ],
    'd6_user' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd6_user_contact_settings' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd6_user_mail' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd6_user_picture_file' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd6_user_role' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd6_user_settings' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd7_user' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd7_user_flood' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd7_user_mail' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'd7_user_role' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'user_picture_entity_display' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'user_picture_entity_form_display' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'user_picture_field' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'user_picture_field_instance' => [
      'source_module' => 'user',
      'destination_module' => 'user',
    ],
    'user_profile_entity_display' => [
      'source_module' => 'profile',
      'destination_module' => 'user',
    ],
    'user_profile_entity_form_display' => [
      'source_module' => 'profile',
      'destination_module' => 'user',
    ],
    'user_profile_field' => [
      'source_module' => 'profile',
      'destination_module' => 'user',
    ],
    'user_profile_field_instance' => [
      'source_module' => 'profile',
      'destination_module' => 'user',
    ],
  ];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs the MigrateUpgradeForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(StateInterface $state, DateFormatterInterface $date_formatter, RendererInterface $renderer, MigrationPluginManagerInterface $plugin_manager) {
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_drupal_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->getValue('step', 'overview');
    switch ($step) {
      case 'overview':
        return $this->buildOverviewForm($form, $form_state);

      case 'credentials':
        return $this->buildCredentialForm($form, $form_state);

      case 'confirm':
        return $this->buildConfirmForm($form, $form_state);

      default:
        drupal_set_message($this->t('Unrecognized form step @step', ['@step' => $step]), 'error');
        return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is intentionally empty, see the specific submit methods for
    // each form step.
  }

  /**
   * Builds the form presenting an overview of the migration process.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildOverviewForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Drupal Upgrade');

    if ($date_performed = $this->state->get('migrate_drupal_ui.performed')) {
      // @todo Add back support for rollbacks and incremental migrations.
      //   https://www.drupal.org/node/2687843
      //   https://www.drupal.org/node/2687849
      $form['upgrade_option_item'] = [
        '#type' => 'item',
        '#prefix' => $this->t('An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal 8. Rollbacks and incremental migrations are not yet supported through the user interface. For more information, see the <a href=":url">upgrading handbook</a>.', [':url' => 'https://www.drupal.org/upgrade/migrate']),
        '#description' => $this->t('<p>Last upgrade: @date</p>', ['@date' => $this->dateFormatter->format($date_performed)]),
      ];
      return $form;
    }
    else {
      $form['info_header'] = [
        '#markup' => '<p>' . $this->t('Upgrade a Drupal site by importing it into a clean and empty new install of Drupal 8. You will lose any existing configuration once you import your site into it. See the <a href=":url">upgrading handbook</a> for more detailed information.', [
          ':url' => 'https://www.drupal.org/upgrade/migrate',
        ]),
      ];

      $info[] = $this->t('<strong>Back up the database for this site</strong>. Upgrade will change the database for this site.');
      $info[] = $this->t('Make sure that the host this site is on has access to the database for your previous site.');
      $info[] = $this->t('If your previous site has private files to be migrated, a copy of your files directory must be accessible on the host this site is on.');
      $info[] = $this->t('In general, enable all modules on this site that are enabled on the previous site. For example, if you have used the book module on the previous site then you must enable the book module on this site for that data to be available on this site.');
      $info[] = $this->t('Put this site into <a href=":url">maintenance mode</a>.', [
        ':url' => Url::fromRoute('system.site_maintenance_mode')->toString(TRUE)->getGeneratedUrl(),
      ]);

      $form['info'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#items' => $info,
      ];

      $form['info_footer'] = [
        '#markup' => '<p>' . $this->t('This upgrade can take a long time. It is better to import a local copy of your site instead of directly importing from your live site.'),
      ];

      $validate = [];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
      '#validate' => $validate,
      '#submit' => ['::submitOverviewForm'],
    ];
    return $form;
  }

  /**
   * Form submission handler for the overview form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitOverviewForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('step', 'credentials');
    $form_state->setRebuild();
  }

  /**
   * Builds the database credential form and adds file location information.
   *
   * This is largely borrowed from \Drupal\Core\Installer\Form\SiteSettingsForm.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @todo Private files directory not yet implemented, depends on
   *   https://www.drupal.org/node/2547125.
   */
  public function buildCredentialForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Drupal Upgrade');

    $drivers = $this->getDatabaseTypes();
    $drivers_keys = array_keys($drivers);
    // @todo https://www.drupal.org/node/2678510 Because this is a multi-step
    //   form, the form is not rebuilt during submission. Ideally we would get
    //   the chosen driver from form input, if available, in order to use
    //   #limit_validation_errors in the same way
    //   \Drupal\Core\Installer\Form\SiteSettingsForm does.
    $default_driver = current($drivers_keys);

    $default_options = [];

    $form['database'] = [
      '#type' => 'details',
      '#title' => $this->t('Source database'),
      '#description' => $this->t('Provide credentials for the database of the Drupal site you want to upgrade.'),
      '#open' => TRUE,
    ];

    $form['database']['driver'] = [
      '#type' => 'radios',
      '#title' => $this->t('Database type'),
      '#required' => TRUE,
      '#default_value' => $default_driver,
    ];
    if (count($drivers) == 1) {
      $form['database']['driver']['#disabled'] = TRUE;
    }

    // Add driver-specific configuration options.
    foreach ($drivers as $key => $driver) {
      $form['database']['driver']['#options'][$key] = $driver->name();

      $form['database']['settings'][$key] = $driver->getFormOptions($default_options);
      // @todo https://www.drupal.org/node/2678510 Using
      //   #limit_validation_errors in the submit does not work so it is not
      //   possible to require the database and username for mysql and pgsql.
      //   This is because this is a multi-step form.
      $form['database']['settings'][$key]['database']['#required'] = FALSE;
      $form['database']['settings'][$key]['username']['#required'] = FALSE;
      $form['database']['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . $this->t('@driver_name settings', ['@driver_name' => $driver->name()]) . '</h2>';
      $form['database']['settings'][$key]['#type'] = 'container';
      $form['database']['settings'][$key]['#tree'] = TRUE;
      $form['database']['settings'][$key]['advanced_options']['#parents'] = [$key];
      $form['database']['settings'][$key]['#states'] = [
        'visible' => [
          ':input[name=driver]' => ['value' => $key],
        ],
      ];

      // Move the host fields out of advanced settings.
      if (isset($form['database']['settings'][$key]['advanced_options']['host'])) {
        $form['database']['settings'][$key]['host'] = $form['database']['settings'][$key]['advanced_options']['host'];
        $form['database']['settings'][$key]['host']['#title'] = 'Database host';
        $form['database']['settings'][$key]['host']['#weight'] = -1;
        unset($form['database']['settings'][$key]['database']['#default_value']);
        unset($form['database']['settings'][$key]['advanced_options']['host']);
      }
    }

    $form['source'] = [
      '#type' => 'details',
      '#title' => $this->t('Source files'),
      '#open' => TRUE,
    ];
    $form['source']['source_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Files directory'),
      '#description' => $this->t('To import files from your current Drupal site, enter a local file directory containing your site (e.g. /var/www/docroot), or your site address (e.g. http://example.com).'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Review upgrade'),
      '#button_type' => 'primary',
      '#validate' => ['::validateCredentialForm'],
      '#submit' => ['::submitCredentialForm'],
    ];
    return $form;
  }

  /**
   * Validation handler for the credentials form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCredentialForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the database driver from the form, use reflection to get the
    // namespace, and then construct a valid database array the same as in
    // settings.php.
    $driver = $form_state->getValue('driver');
    $drivers = $this->getDatabaseTypes();
    $reflection = new \ReflectionClass($drivers[$driver]);
    $install_namespace = $reflection->getNamespaceName();

    $database = $form_state->getValue($driver);
    // Cut the trailing \Install from namespace.
    $database['namespace'] = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
    $database['driver'] = $driver;

    // Validate the driver settings and just end here if we have any issues.
    if ($errors = $drivers[$driver]->validateDatabaseSettings($database)) {
      foreach ($errors as $name => $message) {
        $form_state->setErrorByName($name, $message);
      }
      return;
    }

    try {
      $connection = $this->getConnection($database);
      $version = $this->getLegacyDrupalVersion($connection);
      if (!$version) {
        $form_state->setErrorByName($database['driver'] . '][0', $this->t('Source database does not contain a recognizable Drupal version.'));
      }
      else {
        $this->createDatabaseStateSettings($database, $version);
        $migrations = $this->getMigrations('migrate_drupal_' . $version, $version);

        // Get the system data from source database.
        $system_data = $this->getSystemData($connection);

        // Convert the migration object into array
        // so that it can be stored in form storage.
        $migration_array = [];
        foreach ($migrations as $migration) {
          $migration_array[$migration->id()] = $migration->label();
        }

        // Store the retrieved migration IDs in form storage.
        $form_state->set('migrations', $migration_array);
        $form_state->set('source_base_path', $form_state->getValue('source_base_path'));

        // Store the retrived system data in form storage.
        $form_state->set('system_data', $system_data);
      }
    }
    catch (\Exception $e) {
      $error_message = [
        '#type' => 'inline_template',
        '#template' => '{% trans %}Resolve the issue below to continue the upgrade.{% endtrans%}{{ errors }}',
        '#context' => [
          'errors' => [
            '#theme' => 'item_list',
            '#items' => [$e->getMessage()],
          ],
        ],
      ];

      $form_state->setErrorByName($database['driver'] . '][0', $this->renderer->renderPlain($error_message));
    }
  }

  /**
   * Submission handler for the credentials form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCredentialForm(array &$form, FormStateInterface $form_state) {
    // Indicate the next step is confirmation.
    $form_state->setValue('step', 'confirm');
    $form_state->setRebuild();
  }

  /**
   * Confirmation form for missing migrations, etc.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfirmForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#submit'] = ['::submitConfirmForm'];

    $form['actions']['submit']['#value'] = $this->t('Perform upgrade');

    $table_data = [];
    $system_data = [];
    foreach ($form_state->get('migrations') as $migration_id => $migration_label) {
      // Fetch the system data at the first opportunity.
      if (empty($system_data)) {
        $system_data = $form_state->get('system_data');
      }

      // Handle derivatives.
      list($migration_id,) = explode(':', $migration_id, 2);
      $source_module = $this->moduleUpgradePaths[$migration_id]['source_module'];
      $destination_module = $this->moduleUpgradePaths[$migration_id]['destination_module'];
      $table_data[$source_module][$destination_module][$migration_id] = $migration_label;
    }
    // Sort the table by source module names and within that destination
    // module names.
    ksort($table_data);
    foreach ($table_data as $source_module => $destination_module_info) {
      ksort($table_data[$source_module]);
    }
    $unmigrated_source_modules = array_diff_key($system_data['module'], $table_data);

    // Missing migrations.
    $form['missing_module_list_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Missing upgrade paths'),
      '#description' => $this->t('The following items will not be upgraded. For more information see <a href=":migrate">Upgrading from Drupal 6 or 7 to Drupal 8</a>.', array(':migrate' => 'https://www.drupal.org/upgrade/migrate')),
    ];
    $form['missing_module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Destination'),
      ],
    ];
    $missing_count = 0;
    ksort($unmigrated_source_modules);
    foreach ($unmigrated_source_modules as $source_module => $module_data) {
      if ($module_data['status']) {
        $missing_count++;
        $form['missing_module_list'][$source_module] = [
          'source_module' => ['#plain_text' => $source_module],
          'destination_module' => ['#plain_text' => 'Missing'],
        ];
      }
    }
    // Available migrations.
    $form['available_module_list'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Available upgrade paths'),
    ];

    $form['available_module_list']['module_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Destination'),
      ],
    ];

    $available_count = 0;
    foreach ($table_data as $source_module => $destination_module_info) {
      $available_count++;
      $destination_details = [];
      foreach ($destination_module_info as $destination_module => $migration_ids) {
        $destination_details[$destination_module] = [
          '#type' => 'item',
          '#plain_text' => $destination_module,
        ];
      }
      $form['available_module_list']['module_list'][$source_module] = [
        'source_module' => ['#plain_text' => $source_module],
        'destination_module' => $destination_details,
      ];
    }
    $form['counts'] = [
      '#type' => 'item',
      '#title' => '<ul><li>' . $this->t('@count available upgrade paths', ['@count' => $available_count]) . '</li><li>' . $this->t('@count missing upgrade paths', ['@count' => $missing_count]) . '</li></ul>',
      '#weight' => -15,
    ];

    return $form;
  }

  /**
   * Submission handler for the confirmation form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConfirmForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();

    $migrations = $storage['migrations'];
    $config['source_base_path'] = $storage['source_base_path'];
    $batch = [
      'title' => $this->t('Running upgrade'),
      'progress_message' => '',
      'operations' => [
        [
          [MigrateUpgradeRunBatch::class, 'run'],
          [array_keys($migrations), 'import', $config],
        ],
      ],
      'finished' => [
        MigrateUpgradeRunBatch::class,
        'finished',
      ],
    ];
    batch_set($batch);
    $form_state->setRedirect('<front>');
    $this->state->set('migrate_drupal_ui.performed', REQUEST_TIME);
  }

  /**
   * Returns all supported database driver installer objects.
   *
   * @return \Drupal\Core\Database\Install\Tasks[]
   *   An array of available database driver installer objects.
   */
  protected function getDatabaseTypes() {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    return drupal_get_database_types();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('migrate_drupal_ui.upgrade');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('<p><strong>Upgrade analysis report</strong></p>');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Perform upgrade');
  }

}
