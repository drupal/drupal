<?php

/**
 * @file
 * Contains \Drupal\search\SearchPageListBuilder.
 */

namespace Drupal\search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of search page entities.
 *
 * @see \Drupal\search\Entity\SearchPage
 */
class SearchPageListBuilder extends DraggableListBuilder implements FormInterface {

  /**
   * The entities being listed.
   *
   * @var \Drupal\search\SearchPageInterface[]
   */
  protected $entities = array();

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The search manager.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * Constructs a new SearchPageListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\search\SearchPluginManager $search_manager
   *   The search plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, SearchPluginManager $search_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);
    $this->configFactory = $config_factory;
    $this->searchManager = $search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.search'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = array(
      'data' => $this->t('Label'),
    );
    $header['url'] = array(
      'data' => $this->t('URL'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['plugin'] = array(
      'data' => $this->t('Type'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['status'] = array(
      'data' => $this->t('Status'),
    );
    $header['progress'] = array(
      'data' => $this->t('Indexing progress'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity \Drupal\search\SearchPageInterface */
    $row['label'] = $this->getLabel($entity);
    $row['url']['#markup'] = 'search/' . $entity->getPath();
    // If the search page is active, link to it.
    if ($entity->status()) {
      $row['url'] = array(
        '#type' => 'link',
        '#title' => $row['url'],
        '#route_name' => 'search.view_' . $entity->id(),
      );
    }

    $definition = $entity->getPlugin()->getPluginDefinition();
    $row['plugin']['#markup'] = $definition['title'];

    if ($entity->isDefaultSearch()) {
      $status = $this->t('Default');
    }
    elseif ($entity->status()) {
      $status = $this->t('Enabled');
    }
    else {
      $status = $this->t('Disabled');
    }
    $row['status']['#markup'] = $status;

    if ($entity->isIndexable()) {
      $status = $entity->getPlugin()->indexStatus();
      $row['progress']['#markup'] = $this->t('%num_indexed of %num_total indexed', array(
        '%num_indexed' => $status['total'] - $status['remaining'],
        '%num_total' => $status['total']
      ));
    }
    else {
      $row['progress']['#markup'] = $this->t('Does not use index');
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $old_state = $this->configFactory->getOverrideState();
    $search_settings = $this->configFactory->setOverrideState(FALSE)->get('search.settings');
    $this->configFactory->setOverrideState($old_state);
    // Collect some stats.
    $remaining = 0;
    $total = 0;
    foreach ($this->entities as $entity) {
      if ($entity->isIndexable() && $status = $entity->getPlugin()->indexStatus()) {
        $remaining += $status['remaining'];
        $total += $status['total'];
      }
    }

    $this->moduleHandler->loadAllIncludes('admin.inc');
    $count = format_plural($remaining, 'There is 1 item left to index.', 'There are @count items left to index.');
    $done = $total - $remaining;
    // Use floor() to calculate the percentage, so if it is not quite 100%, it
    // will show as 99%, to indicate "almost done".
    $percentage = $total > 0 ? floor(100 * $done / $total) : 100;
    $percentage .= '%';
    $status = '<p><strong>' . $this->t('%percentage of the site has been indexed.', array('%percentage' => $percentage)) . ' ' . $count . '</strong></p>';
    $form['status'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing progress'),
      '#open' => TRUE,
    );
    $form['status']['status'] = array('#markup' => $status);
    $form['status']['wipe'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Re-index site'),
      '#submit' => array('::searchAdminReindexSubmit'),
    );

    $items = array(10, 20, 50, 100, 200, 500);
    $items = array_combine($items, $items);

    // Indexing throttle:
    $form['indexing_throttle'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing throttle'),
      '#open' => TRUE,
    );
    $form['indexing_throttle']['cron_limit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of items to index per cron run'),
      '#default_value' => $search_settings->get('index.cron_limit'),
      '#options' => $items,
      '#description' => $this->t('The maximum number of items indexed in each pass of a <a href="@cron">cron maintenance task</a>. If necessary, reduce the number of items to prevent timeouts and memory errors while indexing.', array('@cron' => \Drupal::url('system.status'))),
    );
    // Indexing settings:
    $form['indexing_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing settings'),
      '#open' => TRUE,
    );
    $form['indexing_settings']['info'] = array(
      '#markup' => $this->t('<p><em>Changing the settings below will cause the site index to be rebuilt. The search index is not cleared but systematically updated to reflect the new settings. Searching will continue to work but new content won\'t be indexed until all existing content has been re-indexed.</em></p><p><em>The default settings should be appropriate for the majority of sites.</em></p>')
    );
    $form['indexing_settings']['minimum_word_size'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum word length to index'),
      '#default_value' => $search_settings->get('index.minimum_word_size'),
      '#min' => 1,
      '#max' => 1000,
      '#description' => $this->t('The number of characters a word has to be to be indexed. A lower setting means better search result ranking, but also a larger database. Each search query must contain at least one keyword that is this size (or longer).')
    );
    $form['indexing_settings']['overlap_cjk'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Simple CJK handling'),
      '#default_value' => $search_settings->get('index.overlap_cjk'),
      '#description' => $this->t('Whether to apply a simple Chinese/Japanese/Korean tokenizer based on overlapping sequences. Turn this off if you want to use an external preprocessor for this instead. Does not affect other languages.')
    );

    // Indexing settings:
    $form['logging'] = array(
      '#type' => 'details',
      '#title' => $this->t('Logging'),
      '#open' => TRUE,
    );

    $form['logging']['logging'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log searches'),
      '#default_value' => $search_settings->get('logging'),
      '#description' => $this->t('If checked, all searches will be logged. Uncheck to skip logging. Logging may affect performance.'),
    );

    $form['search_pages'] = array(
      '#type' => 'details',
      '#title' => $this->t('Search pages'),
      '#open' => TRUE,
    );
    $form['search_pages']['add_page'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'search') . '/css/search.admin.css',
        ),
      ),
    );
    // In order to prevent validation errors for the parent form, this cannot be
    // required, see self::validateAddSearchPage().
    $form['search_pages']['add_page']['search_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Search page type'),
      '#empty_option' => $this->t('- Choose page type -'),
      '#options' => array_map(function ($definition) {
        return $definition['title'];
      }, $this->searchManager->getDefinitions()),
    );
    $form['search_pages']['add_page']['add_search_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add new page'),
      '#validate' => array('::validateAddSearchPage'),
      '#submit' => array('::submitAddSearchPage'),
      '#limit_validation_errors' => array(array('search_type')),
    );

    // Move the listing into the search_pages element.
    $form['search_pages'][$this->entitiesKey] = $form[$this->entitiesKey];
    $form['search_pages'][$this->entitiesKey]['#empty'] = $this->t('No search pages have been configured.');
    unset($form[$this->entitiesKey]);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var $entity \Drupal\search\SearchPageInterface */
    $operations = parent::getDefaultOperations($entity);

    // Prevent the default search from being disabled or deleted.
    if ($entity->isDefaultSearch()) {
      unset($operations['disable'], $operations['delete']);
    }
    else {
      $operations['default'] = array(
        'title' => $this->t('Set as default'),
        'route_name' => 'entity.search_page.set_default',
        'route_parameters' => array(
          'search_page' => $entity->id(),
        ),
        'weight' => 50,
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $search_settings = $this->configFactory->get('search.settings');
    // If these settings change, the index needs to be rebuilt.
    if (($search_settings->get('index.minimum_word_size') != $form_state->getValue('minimum_word_size')) || ($search_settings->get('index.overlap_cjk') != $form_state->getValue('overlap_cjk'))) {
      $search_settings->set('index.minimum_word_size', $form_state->getValue('minimum_word_size'));
      $search_settings->set('index.overlap_cjk', $form_state->getValue('overlap_cjk'));
      drupal_set_message($this->t('The index will be rebuilt.'));
      search_reindex();
    }

    $search_settings
      ->set('index.cron_limit', $form_state->getValue('cron_limit'))
      ->set('logging', $form_state->getValue('logging'))
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Form submission handler for the reindex button on the search admin settings
   * form.
   */
  public function searchAdminReindexSubmit(array &$form, FormStateInterface $form_state) {
    // Send the user to the confirmation page.
    $form_state->setRedirect('search.reindex_confirm');
  }

  /**
   * Form validation handler for adding a new search page.
   */
  public function validateAddSearchPage(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('search_type')) {
      $form_state->setErrorByName('search_type', $this->t('You must select the new search page type.'));
    }
  }

  /**
   * Form submission handler for adding a new search page.
   */
  public function submitAddSearchPage(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'search.add_type',
      array('search_plugin_id' => $form_state->getValue('search_type'))
    );
  }

}
