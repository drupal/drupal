<?php

namespace Drupal\config_translation\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * Defines the configuration translation list builder for entities.
 */
class ConfigTranslationEntityListBuilder extends ConfigEntityListBuilder implements ConfigTranslationEntityListBuilderInterface {

  /**
   * Provides user facing strings for the filter element.
   *
   * @return array
   *   An associative array of facing strings.
   */
  protected function getFilterLabels() {
    return [
      'placeholder' => $this->t('Enter label'),
      'description' => $this->t('Enter a part of the label or description to filter by.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $filter = $this->getFilterLabels();

    usort($build['table']['#rows'], [$this, 'sortRows']);

    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
      '#weight' => -10,
    ];

    $build['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $filter['placeholder'],
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '.config-translation-entity-list',
        'autocomplete' => 'off',
        'title' => $filter['description'],
      ],
    ];

    $build['table']['#attributes']['class'][] = 'config-translation-entity-list';
    $build['table']['#weight'] = 0;
    $build['#attached']['library'][] = 'system/drupal.system.modules';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = $entity->label();
    $row['label']['class'][] = 'table-filter-text-source';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    foreach (array_keys($operations) as $operation) {
      // This is a translation UI for translators. Show the translation
      // operation only.
      if (!($operation == 'translate')) {
        unset($operations[$operation]);
      }
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function sortRows($a, $b) {
    return $this->sortRowsMultiple($a, $b, ['label']);
  }

  /**
   * Sorts an array by multiple criteria.
   *
   * @param array $a
   *   First item for comparison.
   * @param array $b
   *   Second item for comparison.
   * @param array $keys
   *   The array keys to sort on.
   *
   * @return int
   *   The comparison result for uasort().
   */
  protected function sortRowsMultiple($a, $b, $keys) {
    $key = array_shift($keys);
    $a_value = (is_array($a) && isset($a[$key]['data'])) ? $a[$key]['data'] : '';
    $b_value = (is_array($b) && isset($b[$key]['data'])) ? $b[$key]['data'] : '';

    if ($a_value == $b_value && !empty($keys)) {
      return $this->sortRowsMultiple($a, $b, $keys);
    }

    return strnatcasecmp($a_value, $b_value);
  }

  /**
   * {@inheritdoc}
   */
  public function setMapperDefinition($mapper_definition) {
    // @todo Why is this method called on all config list controllers?
    return $this;
  }

}
