<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatListController.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the filter format list controller.
 */
class FilterFormatListController extends ConfigEntityListController implements FormInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new FilterFormatListController.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'filter_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return drupal_get_form($this);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Only list enabled filters.
    return array_filter(parent::load(), function ($entity) {
      return $entity->status();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    $header['roles'] = t('Roles');
    $header['weight'] = t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $entity->get('weight');

    // Check whether this is the fallback text format. This format is available
    // to all roles and cannot be disabled via the admin interface.
    $row['#is_fallback'] = $entity->isFallbackFormat();
    if ($row['#is_fallback']) {
      $row['name'] = array('#markup' => String::placeholder($entity->label()));

      $fallback_choice = $this->configFactory->get('filter.settings')->get('always_show_fallback_choice');
      if ($fallback_choice) {
        $roles_markup = String::placeholder(t('All roles may use this format'));
      }
      else {
        $roles_markup = String::placeholder(t('This format is shown when no other formats are available'));
      }
    }
    else {
      $row['name'] = array('#markup' => $this->getLabel($entity));
      $roles = array_map('\Drupal\Component\Utility\String::checkPlain', filter_get_roles_by_format($entity));
      $roles_markup = $roles ? implode(', ', $roles) : t('No roles may use this format');
    }

    $row['roles'] = array('#markup' => $roles_markup);

    $row['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $entity->label())),
      '#title_display' => 'invisible',
      '#default_value' => $entity->get('weight'),
      '#attributes' => array('class' => array('text-format-order-weight')),
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Configure');
    }

    // The fallback format may not be disabled.
    if ($entity->isFallbackFormat()) {
      unset($operations['disable']);
    }

    // Formats can never be deleted.
    unset($operations['delete']);
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['#tree'] = TRUE;
    $form['formats'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
      '#tabledrag' => array(
        array('order', 'sibling', 'text-format-order-weight'),
      ),
    );
    foreach ($this->load() as $entity) {
      $form['formats'][$entity->id()] = $this->buildRow($entity);
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values']['formats'];

    $entities = $this->storage->loadMultiple(array_keys($values));
    foreach ($values as $id => $value) {
      if (isset($entities[$id]) && $value['weight'] != $entities[$id]->get('weight')) {
        // Update changed weight.
        $entities[$id]->set('weight', $value['weight']);
        $entities[$id]->save();
      }
    }

    filter_formats_reset();
    drupal_set_message(t('The text format ordering has been saved.'));
  }

}
