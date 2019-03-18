<?php

namespace Drupal\config_translation\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the config translation list builder for blocks.
 */
class ConfigTranslationBlockListBuilder extends ConfigTranslationEntityListBuilder {

  /**
   * An array of theme info keyed by theme name.
   *
   * @var array
   */
  protected $themes = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ThemeHandlerInterface $theme_handler) {
    parent::__construct($entity_type, $storage);
    $this->themes = $theme_handler->listInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
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
    $theme = $entity->getTheme();
    $plugin_definition = $entity->getPlugin()->getPluginDefinition();

    $row['label'] = [
      'data' => $entity->label(),
      'class' => 'table-filter-text-source',
    ];

    $row['theme'] = [
      'data' => $this->themes[$theme]->info['name'],
      'class' => 'table-filter-text-source',
    ];

    $row['category'] = [
      'data' => $plugin_definition['category'],
      'class' => 'table-filter-text-source',
    ];

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
    return $this->sortRowsMultiple($a, $b, ['theme', 'category', 'label']);
  }

}
