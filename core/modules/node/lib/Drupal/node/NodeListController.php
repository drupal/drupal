<?php

/**
 * @file
 * Contains \Drupal\node\NodeListController.
 */

namespace Drupal\node;

use Drupal\Component\Utility\String;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for nodes.
 */
class NodeListController extends EntityListController {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * Constructs a new NodeListController object.
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
  public function buildHeader() {
    // Enable language column and filter if multiple languages are enabled.
    $header = array(
      'title' => $this->t('Title'),
      'type' => array(
        'data' => $this->t('Content type'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'author' => array(
        'data' => $this->t('Author'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'status' => $this->t('Status'),
      'changed' => array(
        'data' => $this->t('Updated'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = array(
        'data' => $this->t('Language'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      );
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\node\NodeInterface $entity */
    $mark = array(
      '#theme' => 'mark',
      '#mark_type' => node_mark($entity->id(), $entity->getChangedTime()),
    );
    $langcode = $entity->language()->id;
    $uri = $entity->urlInfo();
    $row['title']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->label(),
      '#route_name' => $uri['route_name'],
      '#route_parameters' => $uri['route_parameters'],
      '#options' => $uri['options'] + ($langcode != Language::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? array('language' => $languages[$langcode]) : array()),
      '#suffix' => ' ' . drupal_render($mark),
    );
    $row['type'] = String::checkPlain(node_get_type_label($entity));
    $row['author']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    );
    $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');
    $row['changed'] = $this->dateService->format($entity->getChangedTime(), 'short');
    $language_manager = \Drupal::languageManager();
    if ($language_manager->isMultilingual()) {
      $row['language_name'] = $language_manager->getLanguageName($langcode);
    }
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $destination = drupal_get_destination();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
