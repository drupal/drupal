<?php

/**
 * @file
 * Contains \Drupal\system\DateFormatListController.
 */

namespace Drupal\system;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of date formats.
 */
class DateFormatListController extends ConfigEntityListController {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * Constructs a new DateFormatListController object.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, Date $date_service) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);

    $this->dateService = $date_service;
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
      $container->get('date')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return array_filter(parent::load(), function ($entity) {
      return !$entity->isLocked();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('Machine name');
    $header['label'] = t('Name');
    $header['pattern'] = t('Pattern');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['label'] = $this->getLabel($entity);
    $row['pattern'] = $this->dateService->format(REQUEST_TIME, $entity->id());
    return $row + parent::buildRow($entity);
  }

}
