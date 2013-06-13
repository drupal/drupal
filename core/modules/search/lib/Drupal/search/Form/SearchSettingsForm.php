<?php
/**
 * @file
 * Contains \Drupal\search\Form\SearchSettingsForm.
 */

namespace Drupal\search\Form;

use Drupal\system\SystemConfigFormBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure search settings for this site.
 */
class SearchSettingsForm extends SystemConfigFormBase {
  /**
   * A configuration object with the current search settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\user\SearchSettingsForm object.
   *
   * @param \Drupal\Core\Config\Config $search_settings
   *   The configuration object that manages search settings.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler
   */
  public function __construct(Config $search_settings, ModuleHandler $module_handler, KeyValueStoreInterface $state) {
    $this->searchSettings = $search_settings;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('search.settings'),
      $container->get('module_handler'),
      $container->get('keyvalue')->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_admin_settings';
  }

  /**
   * Returns names of available search modules.
   *
   * @return array
   *   An array of the names of enabled modules that call hook_search_info
   *   sorted into alphabetical order.
   */
  protected function getModuleOptions() {
    $search_info = search_get_info(TRUE);
    $names = system_get_module_info('name');
    $names = array_intersect_key($names, $search_info);
    asort($names, SORT_STRING);
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Collect some stats
    $remaining = 0;
    $total = 0;
    foreach ($this->searchSettings->get('active_modules') as $module) {
      if ($status = $this->moduleHandler->invoke($module, 'search_status')) {
        $remaining += $status['remaining'];
        $total += $status['total'];
      }
    }

    $this->moduleHandler->loadAllIncludes('admin.inc');
    $count = format_plural($remaining, 'There is 1 item left to index.', 'There are @count items left to index.');
    $percentage = ((int)min(100, 100 * ($total - $remaining) / max(1, $total))) . '%';
    $status = '<p><strong>' . t('%percentage of the site has been indexed.', array('%percentage' => $percentage)) . ' ' . $count . '</strong></p>';
    $form['status'] = array(
      '#type' => 'details',
      '#title' => t('Indexing status'),
    );
    $form['status']['status'] = array('#markup' => $status);
    $form['status']['wipe'] = array(
      '#type' => 'submit',
      '#value' => t('Re-index site'),
      '#submit' => array(array($this, 'searchAdminReindexSubmit')),
    );

    $items = drupal_map_assoc(array(10, 20, 50, 100, 200, 500));

    // Indexing throttle:
    $form['indexing_throttle'] = array(
      '#type' => 'details',
      '#title' => t('Indexing throttle')
    );
    $form['indexing_throttle']['cron_limit'] = array(
      '#type' => 'select',
      '#title' => t('Number of items to index per cron run'),
      '#default_value' => $this->searchSettings->get('index.cron_limit'),
      '#options' => $items,
      '#description' => t('The maximum number of items indexed in each pass of a <a href="@cron">cron maintenance task</a>. If necessary, reduce the number of items to prevent timeouts and memory errors while indexing.', array('@cron' => url('admin/reports/status')))
    );
    // Indexing settings:
    $form['indexing_settings'] = array(
      '#type' => 'details',
      '#title' => t('Indexing settings')
    );
    $form['indexing_settings']['info'] = array(
      '#markup' => t('<p><em>Changing the settings below will cause the site index to be rebuilt. The search index is not cleared but systematically updated to reflect the new settings. Searching will continue to work but new content won\'t be indexed until all existing content has been re-indexed.</em></p><p><em>The default settings should be appropriate for the majority of sites.</em></p>')
    );
    $form['indexing_settings']['minimum_word_size'] = array(
      '#type' => 'number',
      '#title' => t('Minimum word length to index'),
      '#default_value' => $this->searchSettings->get('index.minimum_word_size'),
      '#min' => 1,
      '#max' => 1000,
      '#description' => t('The number of characters a word has to be to be indexed. A lower setting means better search result ranking, but also a larger database. Each search query must contain at least one keyword that is this size (or longer).')
    );
    $form['indexing_settings']['overlap_cjk'] = array(
      '#type' => 'checkbox',
      '#title' => t('Simple CJK handling'),
      '#default_value' => $this->searchSettings->get('index.overlap_cjk'),
      '#description' => t('Whether to apply a simple Chinese/Japanese/Korean tokenizer based on overlapping sequences. Turn this off if you want to use an external preprocessor for this instead. Does not affect other languages.')
    );

    $form['active'] = array(
      '#type' => 'details',
      '#title' => t('Active search modules')
    );
    $module_options = $this->getModuleOptions();
    $form['active']['active_modules'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Active modules'),
      '#title_display' => 'invisible',
      '#default_value' => $this->searchSettings->get('active_modules'),
      '#options' => $module_options,
      '#description' => t('Choose which search modules are active from the available modules.')
    );
    $form['active']['default_module'] = array(
      '#title' => t('Default search module'),
      '#type' => 'radios',
      '#default_value' => $this->searchSettings->get('default_module'),
      '#options' => $module_options,
      '#description' => t('Choose which search module is the default.')
    );

    // Per module settings
    foreach ($this->searchSettings->get('active_modules') as $module) {
      $added_form = $this->moduleHandler->invoke($module, 'search_admin');
      if (is_array($added_form)) {
        $form = NestedArray::mergeDeep($form, $added_form);
      }
    }
    // Set #submit so we are sure it's invoked even if one of
    // the active search modules added its own #submit.
    $form['#submit'][] = array($this, 'submitForm');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    // Check whether we selected a valid default.
    if ($form_state['triggering_element']['#value'] != t('Reset to defaults')) {
      $new_modules = array_filter($form_state['values']['active_modules']);
      $default = $form_state['values']['default_module'];
      if (!in_array($default, $new_modules, TRUE)) {
        form_set_error('default_module', t('Your default search module is not selected as an active module.'));
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
      drupal_set_message(t('The index will be rebuilt.'));
      search_reindex();
    }
    $this->searchSettings->set('index.cron_limit', $form_state['values']['cron_limit']);
    $this->searchSettings->set('default_module', $form_state['values']['default_module']);

    // Check whether we are resetting the values.
    if ($form_state['triggering_element']['#value'] == t('Reset to defaults')) {
      $new_modules = array('node', 'user');
    }
    else {
      $new_modules = array_filter($form_state['values']['active_modules']);
    }
    if ($this->searchSettings->get('active_modules') != $new_modules) {
      $this->searchSettings->set('active_modules', $new_modules);
      drupal_set_message(t('The active search modules have been changed.'));
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
