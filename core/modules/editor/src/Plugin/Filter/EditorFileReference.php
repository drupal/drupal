<?php

namespace Drupal\editor\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\editor\Plugin\Filter\EditorFileReference object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
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
          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          if ($file) {
            $node->setAttribute('src', file_url_transform_relative(file_create_url($file->getFileUri())));
          }
        }

        // Only process the first occurrence of each file UUID.
        if (!isset($processed_uuids[$uuid])) {
          $processed_uuids[$uuid] = TRUE;

          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          if ($file) {
            $result->addCacheTags($file->getCacheTags());
          }
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
