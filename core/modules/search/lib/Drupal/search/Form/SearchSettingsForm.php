<?php
/**
 * @file
 * Contains \Drupal\search\Form\SearchSettingsForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\search\SearchPluginManager;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure search settings for this site.
 */
class SearchSettingsForm extends ConfigFormBase {

  /**
   * A configuration object with the current search settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * A search plugin manager object.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchPluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\search\Form\SearchSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory object that manages search settings.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The context interface
   * @param \Drupal\search\SearchPluginManager $manager
   *   The manager for search plugins.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The state key/value store interface, gives access to state based config settings.
   */
  public function __construct(ConfigFactory $config_factory, ContextInterface $context, SearchPluginManager $manager, ModuleHandlerInterface $module_handler, KeyValueStoreInterface $state) {
    parent::__construct($config_factory, $context);
    $this->searchSettings = $config_factory->get('search.settings');
    $this->searchPluginManager = $manager;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free'),
      $container->get('plugin.manager.search'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_admin_settings';
  }

  /**
   * Returns names of available search plugins.
   *
   * @return array
   *   An array of the names of available search plugins.
   */
  protected function getOptions() {
    $options = array();
    foreach ($this->searchPluginManager->getDefinitions() as $plugin_id => $search_info) {
      $options[$plugin_id] = $search_info['title'] . ' (' . $plugin_id . ')';
    }
    asort($options, SORT_STRING);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    // Collect some stats.
    $remaining = 0;
    $total = 0;

    foreach ($this->searchPluginManager->getActiveIndexingPlugins() as $plugin) {
      if ($status = $plugin->indexStatus()) {
        $remaining += $status['remaining'];
        $total += $status['total'];
      }
    }
    $active_plugins = $this->searchPluginManager->getActivePlugins();
    $this->moduleHandler->loadAllIncludes('admin.inc');
    $count = format_plural($remaining, 'There is 1 item left to index.', 'There are @count items left to index.');
    $percentage = ((int) min(100, 100 * ($total - $remaining) / max(1, $total))) . '%';
    $status = '<p><strong>' . $this->t('%percentage of the site has been indexed.', array('%percentage' => $percentage)) . ' ' . $count . '</strong></p>';
    $form['status'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing status'),
    );
    $form['status']['status'] = array('#markup' => $status);
    $form['status']['wipe'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Re-index site'),
      '#submit' => array(array($this, 'searchAdminReindexSubmit')),
    );

    $items = drupal_map_assoc(array(10, 20, 50, 100, 200, 500));

    // Indexing throttle:
    $form['indexing_throttle'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing throttle')
    );
    $form['indexing_throttle']['cron_limit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of items to index per cron run'),
      '#default_value' => $this->searchSettings->get('index.cron_limit'),
      '#options' => $items,
      '#description' => $this->t('The maximum number of items indexed in each pass of a <a href="@cron">cron maintenance task</a>. If necessary, reduce the number of items to prevent timeouts and memory errors while indexing.', array('@cron' => $this->url('system.status')))
    );
    // Indexing settings:
    $form['indexing_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing settings')
    );
    $form['indexing_settings']['info'] = array(
      '#markup' => $this->t('<p><em>Changing the settings below will cause the site index to be rebuilt. The search index is not cleared but systematically updated to reflect the new settings. Searching will continue to work but new content won\'t be indexed until all existing content has been re-indexed.</em></p><p><em>The default settings should be appropriate for the majority of sites.</em></p>')
    );
    $form['indexing_settings']['minimum_word_size'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum word length to index'),
      '#default_value' => $this->searchSettings->get('index.minimum_word_size'),
      '#min' => 1,
      '#max' => 1000,
      '#description' => $this->t('The number of characters a word has to be to be indexed. A lower setting means better search result ranking, but also a larger database. Each search query must contain at least one keyword that is this size (or longer).')
    );
    $form['indexing_settings']['overlap_cjk'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Simple CJK handling'),
      '#default_value' => $this->searchSettings->get('index.overlap_cjk'),
      '#description' => $this->t('Whether to apply a simple Chinese/Japanese/Korean tokenizer based on overlapping sequences. Turn this off if you want to use an external preprocessor for this instead. Does not affect other languages.')
    );

    $form['active'] = array(
      '#type' => 'details',
      '#title' => $this->t('Active search plugins')
    );
    $options = $this->getOptions();
    $form['active']['active_plugins'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Active plugins'),
      '#title_display' => 'invisible',
      '#default_value' => $this->searchSettings->get('active_plugins'),
      '#options' => $options,
      '#description' => $this->t('Choose which search plugins are active from the available plugins.')
    );
    $form['active']['default_plugin'] = array(
      '#title' => $this->t('Default search plugin'),
      '#type' => 'radios',
      '#default_value' => $this->searchSettings->get('default_plugin'),
      '#options' => $options,
      '#description' => $this->t('Choose which search plugin is the default.')
    );

    // Per plugin settings.
    foreach ($active_plugins as $plugin) {
      $plugin->addToAdminForm($form, $form_state);
    }
    // Set #submit so we are sure it's invoked even if one of
    // the active search plugins added its own #submit.
    $form['#submit'][] = array($this, 'submitForm');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    // Check whether we selected a valid default.
    if ($form_state['triggering_element']['#value'] != $this->t('Reset to defaults')) {
      $new_plugins = array_filter($form_state['values']['active_plugins']);
      $default = $form_state['values']['default_plugin'];
      if (!in_array($default, $new_plugins, TRUE)) {
        form_set_error('default_plugin', $this->t('Your default search plugin is not selected as an active plugin.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    // If these settings change, the index needs to be rebuilt.
    if (($this->searchSettings->get('index.minimum_word_size') != $form_state['values']['minimum_word_size']) || ($this->searchSettings->get('index.overlap_cjk') != $form_state['values']['overlap_cjk'])) {
      $this->searchSettings->set('index.minimum_word_size', $form_state['values']['minimum_word_size']);
      $this->searchSettings->set('index.overlap_cjk', $form_state['values']['overlap_cjk']);
      drupal_set_message($this->t('The index will be rebuilt.'));
      search_reindex();
    }
    $this->searchSettings->set('index.cron_limit', $form_state['values']['cron_limit']);
    $this->searchSettings->set('default_plugin', $form_state['values']['default_plugin']);

    // Handle per-plugin submission logic.
    foreach ($this->searchPluginManager->getActivePlugins() as $plugin) {
      $plugin->submitAdminForm($form, $form_state);
    }

    // Check whether we are resetting the values.
    if ($form_state['triggering_element']['#value'] == $this->t('Reset to defaults')) {
      $new_plugins = array('node_search', 'user_search');
    }
    else {
      $new_plugins = array_filter($form_state['values']['active_plugins']);
    }
    if ($this->searchSettings->get('active_plugins') != $new_plugins) {
      $this->searchSettings->set('active_plugins', $new_plugins);
      drupal_set_message($this->t('The active search plugins have been changed.'));
      $this->state->set('menu_rebuild_needed', TRUE);
    }
    $this->searchSettings->save();
  }

  /**
   * Form submission handler for the reindex button on the search admin settings
   * form.
   */
  public function searchAdminReindexSubmit(array $form, array &$form_state) {
    // send the user to the confirmation page
    $form_state['redirect'] = 'admin/config/search/settings/reindex';
  }

}
