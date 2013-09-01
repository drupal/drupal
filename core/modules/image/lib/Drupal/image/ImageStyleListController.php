<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleListController.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of image styles.
 */
class ImageStyleListController extends ConfigEntityListController implements EntityControllerInterface {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new ImageStyleListController object.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $image_style_storage
   *   The image style entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $image_style_storage, ModuleHandlerInterface $module_handler, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $entity_info, $image_style_storage, $module_handler);
    $this->urlGenerator = $url_generator;
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
      $container->get('url_generator'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Style name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = $this->t('There are currently no styles. <a href="!url">Add a new one</a>.', array(
      '!url' => $this->urlGenerator->generateFromPath('admin/config/media/image-styles/add'),
    ));
    return $build;
  }

}
