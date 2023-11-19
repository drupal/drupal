<?php

namespace Drupal\config_translation\Controller;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the configuration translation list controller.
 */
class ConfigTranslationListController extends ControllerBase {

  /**
   * The mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a new ConfigTranslationListController object.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The config mapper manager.
   */
  public function __construct(ConfigMapperManagerInterface $mapper_manager) {
    $this->mapperManager = $mapper_manager;
  }

  /**
   * Provides the listing page for any entity type.
   *
   * @param string $mapper_id
   *   The name of the mapper.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if a mapper plugin could not be instantiated from the
   *   mapper definition in the constructor.
   */
  public function listing($mapper_id) {
    $mapper_definition = $this->mapperManager->getDefinition($mapper_id);
    $mapper = $this->mapperManager->createInstance($mapper_id, $mapper_definition);
    if (!$mapper) {
      throw new NotFoundHttpException();
    }
    $entity_type = $mapper->getType();
    // If the mapper, for example the mapper for fields, has a custom list
    // controller defined, use it. Other mappers, for examples the ones for
    // node_type and block, fallback to the generic configuration translation
    // list controller.
    $build = $this->entityTypeManager()
      ->getHandler($entity_type, 'config_translation_list')
      ->setMapperDefinition($mapper_definition)
      ->render();
    $build['#title'] = $mapper->getTypeLabel();
    return $build;
  }

}
