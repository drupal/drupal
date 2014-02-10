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
use Drupal\Core\Entity\EntityTypeInterface;
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
   * @param EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $image_style_storage
   *   The image style entity storage controller class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $image_style_storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $image_style_storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id()),
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
  public function getOperations(EntityInterface $entity) {
    $flush = array(
      'title' => t('Flush'),
      'weight' => 200,
    ) + $entity->urlInfo('flush-form');

    return parent::getOperations($entity) + array('flush' => $flush);
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
