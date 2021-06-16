<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an area handler which renders an entity in a certain view mode.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("entity")
 */
class Entity extends TokenizeAreaPluginBase {

  /**
   * Stores the entity type of the result entities.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new Entity instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->entityType = $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Per default we enable tokenize, as this is the most common use case for
    // this handler.
    $options['tokenize']['default'] = TRUE;

    // Contains the config target identifier for the entity.
    $options['target'] = ['default' => ''];
    $options['view_mode'] = ['default' => 'default'];
    $options['bypass_access'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->entityType),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->options['view_mode'],
    ];

    $label = $this->entityTypeManager->getDefinition($this->entityType)->getLabel();
    $target = $this->options['target'];

    // If the target does not contain tokens, try to load the entity and
    // display the entity ID to the admin form user.
    // @todo Use a method to check for tokens in
    //   https://www.drupal.org/node/2396607.
    if (strpos($this->options['target'], '{{') === FALSE) {
      // @todo If the entity does not exist, this will show the config target
      //   identifier. Decide if this is the correct behavior in
      //   https://www.drupal.org/node/2415391.
      if ($target_entity = $this->entityRepository->loadEntityByConfigTarget($this->entityType, $this->options['target'])) {
        $target = $target_entity->id();
      }
    }
    $form['target'] = [
      '#title' => $this->t('@entity_type_label ID', ['@entity_type_label' => $label]),
      '#type' => 'textfield',
      '#default_value' => $target,
    ];

    $form['bypass_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass access checks'),
      '#description' => $this->t('If enabled, access permissions for rendering the entity are not checked.'),
      '#default_value' => !empty($this->options['bypass_access']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    // Load the referenced entity and store its config target identifier if
    // the target does not contains tokens.
    // @todo Use a method to check for tokens in
    //   https://www.drupal.org/node/2396607.
    $options = $form_state->getValue('options');
    if (strpos($options['target'], '{{') === FALSE) {
      if ($entity = $this->entityTypeManager->getStorage($this->entityType)->load($options['target'])) {
        $options['target'] = $entity->getConfigTarget();
      }
      $form_state->setValue('options', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      // @todo Use a method to check for tokens in
      //   https://www.drupal.org/node/2396607.
      if (strpos($this->options['target'], '{{') !== FALSE) {
        // We cast as we need the integer/string value provided by the
        // ::tokenizeValue() call.
        $target_id = (string) $this->tokenizeValue($this->options['target']);
        if ($entity = $this->entityTypeManager->getStorage($this->entityType)->load($target_id)) {
          $target_entity = $entity;
        }
      }
      else {
        if ($entity = $this->entityRepository->loadEntityByConfigTarget($this->entityType, $this->options['target'])) {
          $target_entity = $entity;
        }
      }
      if (isset($target_entity) && (!empty($this->options['bypass_access']) || $target_entity->access('view'))) {
        $view_builder = $this->entityTypeManager->getViewBuilder($this->entityType);
        return $view_builder->view($target_entity, $this->options['view_mode']);
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Ensure that we don't add dependencies for placeholders.
    // @todo Use a method to check for tokens in
    //   https://www.drupal.org/node/2396607.
    if (strpos($this->options['target'], '{{') === FALSE) {
      if ($entity = $this->entityRepository->loadEntityByConfigTarget($this->entityType, $this->options['target'])) {
        $dependencies[$this->entityTypeManager->getDefinition($this->entityType)->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

}
