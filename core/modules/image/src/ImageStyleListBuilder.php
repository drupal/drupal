<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleListBuilder.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of image style entities.
 *
 * @see \Drupal\image\Entity\ImageStyle
 */
class ImageStyleListBuilder extends ConfigEntityListBuilder {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new ImageStyleListBuilder object.
   *
   * @param EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $image_style_storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $image_style_storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
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
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $flush = array(
      'title' => t('Flush'),
      'weight' => 200,
      'url' => $entity->urlInfo('flush-form'),
    );

    return parent::getDefaultOperations($entity) + array(
      'flush' => $flush,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are currently no styles. <a href=":url">Add a new one</a>.', [
      ':url' => $this->urlGenerator->generateFromRoute('image.style_add'),
    ]);
    return $build;
  }

}
