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
use Drupal\Core\Entity\EntityTypeInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage, Date $date_service) {
    parent::__construct($entity_type, $storage);

    $this->dateService = $date_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id()),
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
