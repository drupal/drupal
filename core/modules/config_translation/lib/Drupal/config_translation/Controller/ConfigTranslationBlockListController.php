<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationBlockListController.
 */

namespace Drupal\config_translation\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the config translation controller for blocks.
 */
class ConfigTranslationBlockListController extends ConfigTranslationEntityListController {

  /**
   * An array of theme info keyed by theme name.
   *
   * @var array
   */
  protected $themes = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage, ThemeHandlerInterface $theme_handler) {
    parent::__construct($entity_type, $storage);
    $this->themes = $theme_handler->listInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id()),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterLabels() {
    $info = parent::getFilterLabels();

    $info['placeholder'] = $this->t('Enter block, theme or category');
    $info['description'] = $this->t('Enter a part of the block, theme or category to filter by.');

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $theme = $entity->get('theme');
    $plugin_definition = $entity->getPlugin()->getPluginDefinition();

    $row['label'] = array(
      'data' => $this->getLabel($entity),
      'class' => 'table-filter-text-source',
    );

    $row['theme'] = array(
      'data' => String::checkPlain($this->themes[$theme]->info['name']),
      'class' => 'table-filter-text-source',
    );

    $row['category'] = array(
      'data' => String::checkPlain($plugin_definition['category']),
      'class' => 'table-filter-text-source',
    );

    $row['operations']['data'] = $this->buildOperations($entity);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Block');
    $header['theme'] = $this->t('Theme');
    $header['category'] = $this->t('Category');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function sortRows($a, $b) {
    return $this->sortRowsMultiple($a, $b, array('theme', 'category', 'label'));
  }

}
