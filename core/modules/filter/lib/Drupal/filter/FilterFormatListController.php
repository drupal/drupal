<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatListController.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\DraggableListController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the filter format list controller.
 */
class FilterFormatListController extends DraggableListController implements EntityControllerInterface {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'formats';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FilterFormatListController.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id()),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filter_admin_overview';
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
    $header['label'] = t('Name');
    $header['roles'] = t('Roles');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Check whether this is the fallback text format. This format is available
    // to all roles and cannot be disabled via the admin interface.
    if ($entity->isFallbackFormat()) {
      $row['label'] = String::placeholder($entity->label());

      $fallback_choice = $this->configFactory->get('filter.settings')->get('always_show_fallback_choice');
      if ($fallback_choice) {
        $roles_markup = String::placeholder(t('All roles may use this format'));
      }
      else {
        $roles_markup = String::placeholder(t('This format is shown when no other formats are available'));
      }
    }
    else {
      $row['label'] = $this->getLabel($entity);
      $roles = array_map('\Drupal\Component\Utility\String::checkPlain', filter_get_roles_by_format($entity));
      $roles_markup = $roles ? implode(', ', $roles) : t('No roles may use this format');
    }

    $row['roles'] = !empty($this->weightKey) ? array('#markup' => $roles_markup) : $roles_markup;

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

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = t('Save changes');
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    filter_formats_reset();
    drupal_set_message(t('The text format ordering has been saved.'));
  }

}
