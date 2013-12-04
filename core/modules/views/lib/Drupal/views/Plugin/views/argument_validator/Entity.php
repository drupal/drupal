<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument_validator\Entity.
 */

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a argument validator plugin for each entity type.
 *
 * @ViewsArgumentValidator(
 *   id = "entity",
 *   derivative = "Drupal\views\Plugin\Derivative\ViewsEntityArgumentValidator"
 * )
 *
 * @see \Drupal\views\Plugin\Derivative\ViewsEntityArgumentValidator
 */
class Entity extends ArgumentValidatorPluginBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Boolean if this validator can handle multiple arguments.
   */
  protected $multipleCapable;

  /**
   * Constructs an \Drupal\views\Plugin\views\argument_validator\Entity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManager $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->multipleCapable = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['bundles'] = array('default' => array());
    $options['access'] = array('default' => FALSE, 'bool' => TRUE);
    $options['operation'] = array('default' => 'view');
    $options['multiple'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type = $this->definition['entity_type'];
    // Derivative IDs are all entity:entity_type. Sanitized for js.
    // The ID is converted back on submission.
    $sanitized_id = ArgumentPluginBase::encodeValidatorId($this->definition['id']);
    $entity_definitions = $this->entityManager->getDefinitions();
    $bundle_type = $entity_definitions[$entity_type]['entity_keys']['bundle'];

    // If the entity has bundles, allow option to restrict to bundle(s).
    if ($bundle_type) {
      $bundles = entity_get_bundles($entity_type);
      $bundle_options = array();
      foreach ($bundles as $bundle_id => $bundle_info) {
        $bundle_options[$bundle_id] = $bundle_info['label'];
      }
      $bundles_title = empty($entity_definitions[$entity_type]['bundle_label']) ? t('Bundles') : $entity_definitions[$entity_type]['bundle_label'];
      if (in_array('Drupal\Core\Entity\ContentEntityInterface', class_implements($entity_definitions[$entity_type]['class']))) {
        $fields = $this->entityManager->getFieldDefinitions($entity_type);
      }
      $bundle_name = (empty($fields) || empty($fields[$bundle_type]['label'])) ? t('bundles') : $fields[$bundle_type]['label'];
      $form['bundles'] = array(
        '#title' => $bundles_title,
        '#default_value' => $this->options['bundles'],
        '#type' => 'checkboxes',
        '#options' => $bundle_options,
        '#description' => t('Restrict to one or more %bundle_name. If none selected all are allowed.', array('%bundle_name' => $bundle_name)),
      );
    }

    // Offer the option to filter by access to the entity in the argument.
    $form['access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Validate user has access to the %name', array('%name' => $entity_definitions[$entity_type]['label'])),
      '#default_value' => $this->options['access'],
    );
    $form['operation'] = array(
      '#type' => 'radios',
      '#title' => t('Access operation to check'),
      '#options' => array('view' => t('View'), 'update' => t('Edit'), 'delete' => t('Delete')),
      '#default_value' => $this->options['operation'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[validate][options][' . $sanitized_id . '][access]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // If class is multiple capable give the option to validate single/multiple.
    if ($this->multipleCapable) {
      $form['multiple'] = array(
        '#type' => 'radios',
        '#title' => t('Multiple arguments'),
        '#options' => array(
          0 => t('Single ID', array('%type' => $entity_definitions[$entity_type]['label'])),
          1 => t('One or more IDs separated by , or +', array('%type' => $entity_definitions[$entity_type]['label'])),
        ),
        '#default_value' => (string) $this->options['multiple'],
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, &$form_state, &$options = array()) {
    // Filter out unused options so we don't store giant unnecessary arrays.
    $options['bundles'] = array_filter($options['bundles']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    $entity_type = $this->definition['entity_type'];

    if ($this->options['multiple']) {
      // At this point only interested in individual IDs no matter what type,
      // just splitting by the allowed delimiters.
      $ids = array_filter(preg_split('/[,+ ]/', $argument));
    }
    elseif ($argument) {
      $ids = array($argument);
    }
    // No specified argument should be invalid.
    else {
      $ids = array();
      return FALSE;
    }

    $entities = $this->entityManager->getStorageController($entity_type)->loadMultiple($ids);
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
    if ($this->options['access'] && ! $entity->access($this->options['operation'])) {
      return FALSE;
    }
    // If restricted by bundle.
    $bundles = $this->options['bundles'];
    if (count($bundles) && empty($bundles[$entity->bundle()])) {
      return FALSE;
    }

    return TRUE;
  }

}
