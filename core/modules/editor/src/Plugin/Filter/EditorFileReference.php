<?php

namespace Drupal\editor\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to track images uploaded via a Text Editor.
 *
 * Generates file URLs and associates the cache tags of referenced files.
 *
 * @Filter(
 *   id = "editor_file_reference",
 *   title = @Translation("Track images uploaded via a Text Editor"),
 *   description = @Translation("Ensures that the latest versions of images uploaded via a Text Editor are displayed."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class EditorFileReference extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Constructs a \Drupal\editor\Plugin\Filter\EditorFileReference object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, ImageFactory $image_factory) {
    $this->entityRepository = $entity_repository;
    $this->imageFactory = $image_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-entity-type="file"') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $processed_uuids = [];
      foreach ($xpath->query('//*[@data-entity-type="file" and @data-entity-uuid]') as $node) {
        $uuid = $node->getAttribute('data-entity-uuid');

        // If there is a 'src' attribute, set it to the file entity's current
        // URL. This ensures the URL works even after the file location changes.
        if ($node->hasAttribute('src')) {
          $file = $this->entityRepository->loadEntityByUuid('file', $uuid);
          if ($file instanceof FileInterface) {
            $node->setAttribute('src', $file->createFileUrl());
            if ($node->nodeName == 'img') {
              // Without dimensions specified, layout shifts can occur,
              // which are more noticeable on pages that take some time to load.
              // As a result, only mark images as lazy load that have dimensions.
              $image = $this->imageFactory->get($file->getFileUri());
              $width = $image->getWidth();
              $height = $image->getHeight();
              if ($width !== NULL && $height !== NULL) {
                if (!$node->hasAttribute('width')) {
                  $node->setAttribute('width', $width);
                }
                if (!$node->hasAttribute('height')) {
                  $node->setAttribute('height', $height);
                }
                if (!$node->hasAttribute('loading')) {
                  $node->setAttribute('loading', 'lazy');
                }
              }
            }
          }
        }

        // Only process the first occurrence of each file UUID.
        if (!isset($processed_uuids[$uuid])) {
          $processed_uuids[$uuid] = TRUE;

          $file = $this->entityRepository->loadEntityByUuid('file', $uuid);
          if ($file instanceof FileInterface) {
            $result->addCacheTags($file->getCacheTags());
          }
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
