<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockTypeListController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of custom block types.
 */
class CustomBlockTypeListController extends ConfigEntityListController {

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (module_exists('field_ui')) {
      $uri = $entity->uri();
      $operations['manage-fields'] = array(
        'title' => t('Manage fields'),
        'href' => $uri['path'] . '/fields',
        'options' => $uri['options'],
        'weight' => 0,
      );
      $operations['manage-form-display'] = array(
        'title' => t('Manage form display'),
        'href' => $uri['path'] . '/form-display',
        'options' => $uri['options'],
        'weight' => 5,
      );
      $operations['manage-display'] = array(
        'title' => t('Manage display'),
        'href' => $uri['path'] . '/display',
        'options' => $uri['options'],
        'weight' => 10,
      );
    }
    return $operations;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['type'] = t('Block type');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $uri = $entity->uri();
    $row['type'] = l($entity->label(), $uri['path'], $uri['options']);
    $row['description'] = filter_xss_admin($entity->description);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // @todo Remove this once https://drupal.org/node/2032535 is in.
    drupal_set_title(t('Custom block types'));
    return parent::render();
  }

}
