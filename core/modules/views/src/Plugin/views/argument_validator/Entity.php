<?php

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a argument validator plugin for each entity type.
 *
 * @ViewsArgumentValidator(
 *   id = "entity",
 *   deriver = "Drupal\views\Plugin\Derivative\ViewsEntityArgumentValidator"
 * )
 *
 * @see \Drupal\views\Plugin\Derivative\ViewsEntityArgumentValidator
 */
class Entity extends ArgumentValidatorPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * If this validator can handle multiple arguments.
   *
   * @var bool
   */
  protected $multipleCapable = TRUE;

  /**
   * Constructs an \Drupal\views\Plugin\views\argument_validator\Entity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['bundles'] = ['default' => []];
    $options['access'] = ['default' => FALSE];
    $options['operation'] = ['default' => 'view'];
    $options['multiple'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type_id = $this->definition['entity_type'];
    // Derivative IDs are all entity:entity_type. Sanitized for js.
    // The ID is converted back on submission.
    $sanitized_id = ArgumentPluginBase::encodeValidatorId($this->definition['id']);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    // If the entity has bundles, allow option to restrict to bundle(s).
    if ($entity_type->hasKey('bundle')) {
      $bundle_options = [];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle_id => $bundle_info) {
        $bundle_options[$bundle_id] = $bundle_info['label'];
      }

      $form['bundles'] = [
        '#title' => $entity_type->getBundleLabel() ?: $this->t('Bundles'),
        '#default_value' => $this->options['bundles'],
        '#type' => 'checkboxes',
        '#options' => $bundle_options,
        '#description' => $this->t('If none are selected, all are allowed.'),
      ];
    }

    // Offer the option to filter by access to the entity in the argument.
    $form['access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate user has access to the %name', ['%name' => $entity_type->getLabel()]),
      '#default_value' => $this->options['access'],
    ];
    $form['operation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Access operation to check'),
      '#options' => [
        'view' => $this->t('View'),
        'update' => $this->t('Edit'),
        'delete' => $this->t('Delete'),
      ],
      '#default_value' => $this->options['operation'],
      '#states' => [
        'visible' => [
          ':input[name="options[validate][options][' . $sanitized_id . '][access]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // If class is multiple capable give the option to validate single/multiple.
    if ($this->multipleCapable) {
      $form['multiple'] = [
        '#type' => 'radios',
        '#title' => $this->t('Multiple arguments'),
        '#options' => [
          0 => $this->t('Single ID', ['%type' => $entity_type->getLabel()]),
          1 => $this->t('One or more IDs separated by , or +', ['%type' => $entity_type->getLabel()]),
        ],
        '#default_value' => (string) $this->options['multiple'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = []) {
    // Filter out unused options so we don't store giant unnecessary arrays.
    // Note that the bundles form option doesn't appear on the form if the
    // entity type doesn't support bundles, so the option may not be set.
    if (!empty($options['bundles'])) {
      $options['bundles'] = array_filter($options['bundles']);
    }
    else {
      // Set bundles back to its default empty value.
      $options['bundles'] = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    $entity_type = $this->definition['entity_type'];

    if ($this->multipleCapable && $this->options['multiple']) {
      // At this point only interested in individual IDs no matter what type,
      // just splitting by the allowed delimiters.
      $ids = array_filter(preg_split('/[,+ ]/', $argument));
    }
    elseif ($argument) {
      $ids = [$argument];
    }
    // No specified argument should be invalid.
    else {
      return FALSE;
    }

    $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
    // Validate each id => entity. If any fails break out and return false.
    foreach ($ids as $id) {
      // There is no entity for this ID.
      if (!isset($entities[$id])) {
        return FALSE;
      }
      if (!$this->validateEntity($entities[$id])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Validates an individual entity against class access settings.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   *   True if validated.
   */
  protected function validateEntity(EntityInterface $entity) {
    // If access restricted by entity operation.
    if ($this->options['access'] && !$entity->access($this->options['operation'])) {
      return FALSE;
    }
    // If restricted by bundle.
    $bundles = $this->options['bundles'];
    if (!empty($bundles) && empty($bundles[$entity->bundle()])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $entity_type_id = $this->definition['entity_type'];
    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType();

    // The bundle entity type might not exist. For example, users do not have
    // bundles.
    if ($this->entityTypeManager->hasHandler($bundle_entity_type, 'storage')) {
      $bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type);

      foreach ($bundle_entity_storage->loadMultiple(array_keys($this->options['bundles'])) as $bundle_entity) {
        $dependencies[$bundle_entity->getConfigDependencyKey()][] = $bundle_entity->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    return EntityContextDefinition::fromEntityTypeId($this->definition['entity_type'])
      ->setLabel($this->argument->adminLabel())
      ->setRequired(FALSE);
  }

}
