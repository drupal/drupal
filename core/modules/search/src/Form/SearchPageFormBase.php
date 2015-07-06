<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchPageFormBase.
 */

namespace Drupal\search\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for search pages.
 */
abstract class SearchPageFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\search\SearchPageInterface
   */
  protected $entity;

  /**
   * The search plugin being configured.
   *
   * @var \Drupal\search\Plugin\SearchInterface
   */
  protected $plugin;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * Constructs a new search form.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   */
  public function __construct(QueryFactory $entity_query, SearchPageRepositoryInterface $search_page_repository) {
    $this->entityQuery = $entity_query;
    $this->searchPageRepository = $search_page_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('search.search_page_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'search_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->plugin = $this->entity->getPlugin();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this search page.'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );
    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#field_prefix' => 'search/',
      '#default_value' => $this->entity->getPath(),
      '#maxlength' => '255',
    );
    $form['plugin'] = array(
      '#type' => 'value',
      '#value' => $this->entity->get('plugin'),
    );

    if ($this->plugin instanceof PluginFormInterface) {
      $form += $this->plugin->buildConfigurationForm($form, $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the search page entity already exists.
   *
   * @param string $id
   *   The search configuration ID.
   *
   * @return bool
   *   TRUE if the search configuration exists, FALSE otherwise.
   */
  public function exists($id) {
    $entity = $this->entityQuery->get('search_page')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Ensure each path is unique.
    $path = $this->entityQuery->get('search_page')
      ->condition('path', $form_state->getValue('path'))
      ->condition('id', $form_state->getValue('id'), '<>')
      ->execute();
    if ($path) {
      $form_state->setErrorByName('path', $this->t('The search page path must be unique.'));
    }

    if ($this->plugin instanceof PluginFormInterface) {
      $this->plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($this->plugin instanceof PluginFormInterface) {
      $this->plugin->submitConfigurationForm($form, $form_state);
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();

    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

}
