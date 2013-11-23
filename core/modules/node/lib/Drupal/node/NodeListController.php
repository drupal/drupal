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
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
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
      $container->get('entity.manager')->getStorageController($entity_type),
      $container->get('module_handler'),
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
    if (language_multilingual()) {
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
    $mark = array(
      '#theme' => 'mark',
      '#mark_type' => node_mark($entity->id(), $entity->getChangedTime()),
    );
    $langcode = $entity->language()->id;
    $uri = $entity->uri();
    $row['title']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->label(),
      '#href' => $uri['path'],
      '#options' => $uri['options'] + ($langcode != Language::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? array('language' => $languages[$langcode]) : array()),
      '#suffix' => ' ' . drupal_render($mark),
    );
    $row['type'] = String::checkPlain(node_get_type_label($entity));
    $row['author']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity->getAuthor(),
    );
    $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');
    $row['changed'] = $this->dateService->format($entity->getChangedTime(), 'short');
    if (language_multilingual()) {
      $row['language_name'] = language_name($langcode);
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
