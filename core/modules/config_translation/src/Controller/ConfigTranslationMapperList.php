<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationMapperList.
 */

namespace Drupal\config_translation\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\config_translation\ConfigMapperInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration translation mapper list.
 *
 * Groups all defined configuration mapper instances by weight.
 */
class ConfigTranslationMapperList extends ControllerBase {

  /**
   * A array of configuration mapper instances.
   *
   * @var \Drupal\config_translation\ConfigMapperInterface[]
   */
  protected $mappers;

  /**
   * Constructs a new ConfigTranslationMapperList object.
   *
   * @param \Drupal\config_translation\ConfigMapperInterface[] $mappers
   *   The configuration mapper manager.
   */
  public function __construct(array $mappers) {
    $this->mappers = $mappers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.config_translation.mapper')->getMappers()
    );
  }

  /**
   * Builds the mappers as a renderable array for table.html.twig.
   *
   * @return array
   *   Renderable array with config translation mappers.
   */
  public function render() {
    $build = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
    );

    $mappers = array();

    foreach ($this->mappers as $mapper) {
      if ($row = $this->buildRow($mapper)) {
        $mappers[$mapper->getWeight()][] = $row;
      }
    }

    // Group by mapper weight and sort by label.
    ksort($mappers);
    foreach ($mappers as $weight => $mapper) {
      usort($mapper, function ($a, $b) {
        $a_title = (isset($a['label'])) ? $a['label'] : '';
        $b_title = (isset($b['label'])) ? $b['label'] : '';
        return strnatcasecmp($a_title, $b_title);
      });
      $mappers[$weight] = $mapper;
    }

    foreach ($mappers as $mapper) {
      $build['#rows'] = array_merge($build['#rows'], $mapper);
    }

    return $build;
  }

  /**
   * Builds a row for a mapper in the mapper listing.
   *
   * @param \Drupal\config_translation\ConfigMapperInterface $mapper
   *   The mapper.
   *
   * @return array
   *   A render array structure of fields for this mapper.
   */
  public function buildRow(ConfigMapperInterface $mapper) {
    $row['label'] = SafeMarkup::checkPlain($mapper->getTypeLabel());
    $row['operations']['data'] = $this->buildOperations($mapper);
    return $row;
  }

  /**
   * Builds the header row for the mapper listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $row['Label'] = $this->t('Label');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param \Drupal\config_translation\ConfigMapperInterface $mapper
   *   The mapper.
   *
   * @return array
   *   A renderable array of operation links.
   *
   * @see \Drupal\Core\Entity\EntityList::buildOperations()
   */
  protected function buildOperations(ConfigMapperInterface $mapper) {
    // Retrieve and sort operations.
    $operations = $mapper->getOperations();
    uasort($operations, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    $build = array(
      '#type' => 'operations',
      '#links' => $operations,
    );
    return $build;
  }

}
