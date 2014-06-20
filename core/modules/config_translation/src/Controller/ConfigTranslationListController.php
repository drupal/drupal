<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationListController.
 */

namespace Drupal\config_translation\Controller;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the configuration translation list controller.
 */
class ConfigTranslationListController extends ControllerBase {

  /**
   * The definition of the config mapper.
   *
   * @var array
   */
  protected $mapperDefinition;

  /**
   * The config mapper.
   *
   * @var \Drupal\config_translation\ConfigEntityMapper
   */
  protected $mapper;

  /**
   * Constructs a new ConfigTranslationListController object.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The config mapper manager.
   * @param string $config_translation_mapper
   *   The config mapper id.
   */
  public function __construct(ConfigMapperManagerInterface $mapper_manager, $config_translation_mapper) {
    $this->mapperDefinition = $mapper_manager->getDefinition($config_translation_mapper);
    $this->mapper = $mapper_manager->createInstance($config_translation_mapper, $this->mapperDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('request')->attributes->get('_raw_variables')->get('config_translation_mapper')
    );
  }

  /**
   * Provides the listing page for any entity type.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if a mapper plugin could not be instantiated from the
   *   mapper definition in the constructor.
   */
  public function listing() {
    if (!$this->mapper) {
      throw new NotFoundHttpException();
    }
    $entity_type = $this->mapper->getType();
    // If the mapper, for example the mapper for field instances, has a custom
    // list controller defined, use it. Other mappers, for examples the ones for
    // node_type and block, fallback to the generic configuration translation
    // list controller.
    $build = $this->entityManager()
      ->getController($entity_type, 'config_translation_list')
      ->setMapperDefinition($this->mapperDefinition)
      ->render();
    $build['#title'] = $this->mapper->getTypeLabel();
    return $build;
  }

}
