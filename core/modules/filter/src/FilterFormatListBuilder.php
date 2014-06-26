<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatListBuilder.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of filter format entities.
 *
 * @see \Drupal\filter\Entity\FilterFormat
 */
class FilterFormatListBuilder extends DraggableListBuilder {

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
   * Constructs a new FilterFormatListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
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
    $header['label'] = $this->t('Name');
    $header['roles'] = $this->t('Roles');
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
        $roles_markup = String::placeholder($this->t('All roles may use this format'));
      }
      else {
        $roles_markup = String::placeholder($this->t('This format is shown when no other formats are available'));
      }
    }
    else {
      $row['label'] = $this->getLabel($entity);
      $roles = array_map('\Drupal\Component\Utility\String::checkPlain', filter_get_roles_by_format($entity));
      $roles_markup = $roles ? implode(', ', $roles) : $this->t('No roles may use this format');
    }

    $row['roles'] = !empty($this->weightKey) ? array('#markup' => $roles_markup) : $roles_markup;

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
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
    $form['actions']['submit']['#value'] = $this->t('Save changes');
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    filter_formats_reset();
    drupal_set_message($this->t('The text format ordering has been saved.'));
  }

}
