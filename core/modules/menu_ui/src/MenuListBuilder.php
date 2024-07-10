<?php

namespace Drupal\menu_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of menu entities.
 *
 * @see \Drupal\system\Entity\Menu
 * @see menu_entity_info()
 */
class MenuListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected const SORT_KEY = 'label';

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['description'] = [
      'data' => t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit menu');
      $operations['add'] = [
        'title' => t('Add link'),
        'weight' => 20,
        'url' => $entity->toUrl('add-link-form'),
        'query' => [
          'destination' => $entity->toUrl('edit-form')->toString(),
        ],
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['title'] = t('Delete menu');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureDestination(Url $url) {
    // We don't want to add the destination URL here, as it means we get
    // redirected back to the list-builder after adding/deleting menu links from
    // a menu.
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#attached']['library'][] = "menu_ui/drupal.menu_ui.adminforms";
    return $build;
  }

}
