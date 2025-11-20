<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Entity Link Suggestions plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class EntityLinkSuggestions extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;
  use DynamicPluginConfigWithCsrfTokenUrlTrait;

  /**
   * EntityLinkSuggestions constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $dynamic_plugin_config = $static_plugin_config;
    $dynamic_plugin_config['drupalEntityLinkSuggestions']['suggestionsUrl'] = self::getUrlWithReplacedCsrfTokenPlaceholder(
      Url::fromRoute('ckeditor5.entity_link_suggestions')
        ->setRouteParameter('editor', $editor->id())
    );
    return $dynamic_plugin_config;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $allowed_bundles = [];
    $all_bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    foreach ($all_bundle_info as $entity_type => $bundles) {
      foreach ($bundles as $key => $bundle) {
        if (!empty($bundle['ckeditor5_link_suggestions'])) {
          $allowed_bundles[$entity_type][$key] = $key;
        }
      }
    }

    $header = [
      'enabled' => $this->t('Supported entity types'),
      'bundles' => $this->t('Bundles'),
    ];
    $form['entity_types'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->t('Allowed entity types'),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($allowed_bundles as $entity_type_id => $bundles) {
      $entity_type = $entity_types[$entity_type_id];
      $bundles_for_entity_type = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      $bundles_list = [];
      foreach ($bundles as $bundle) {
        $bundles_list[] = $bundles_for_entity_type[$bundle]['label'];
      }
      $row = [
        'enabled' => [
          '#markup' => $entity_type->getCollectionLabel(),
        ],
        'bundles' => [
          [
            '#markup' => $this->t('Included %bundles', [
              '%bundles' => $this->entityTypeManager
                ->getDefinition($entity_type->getBundleEntityType())
                ->getPluralLabel(),
            ]),
          ], [
            '#theme' => 'item_list',
            '#items' => $bundles_list,
            '#list_type' => 'ol',
          ],
        ],
      ];
      $form['entity_types'][$entity_type_id] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

}
