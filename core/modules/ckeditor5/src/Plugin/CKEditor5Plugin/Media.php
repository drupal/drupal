<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Drupal\media\Entity\MediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;

/**
 * CKEditor 5 Media plugin.
 *
 * Provides drupal-media element and options provided by the CKEditor 5 build.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Media extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface {

  use DynamicPluginConfigWithCsrfTokenUrlTrait;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Media constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository'));
  }

  /**
   * Configures allowed view modes.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array containing view modes, style configuration,
   *   and toolbar configuration.
   */
  private function configureViewModes(EditorInterface $editor) {
    $element_style_configuration = [];
    $toolbar_configuration = [];

    $media_embed_filter = $editor->getFilterFormat()->filters('media_embed');
    $media_bundles = MediaType::loadMultiple();
    $bundles_per_view_mode = [];
    $all_view_modes = $this->entityDisplayRepository->getViewModeOptions('media');
    $allowed_view_modes = $media_embed_filter->settings['allowed_view_modes'];
    $default_view_mode = $media_embed_filter->settings['default_view_mode'];
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3277049.
    // This is a workaround until the above issue is fixed to prevent the
    // editor from crashing because the frontend expects the default view mode
    // to exist in drupalElementStyles.
    if (!array_key_exists($default_view_mode, $allowed_view_modes)) {
      $allowed_view_modes[$default_view_mode] = $default_view_mode;
    }
    // Return early since there is no need to configure if there
    // are less than 2 view modes.
    if ($allowed_view_modes < 2) {
      return [];
    }

    // Configure view modes.
    foreach (array_keys($media_bundles) as $bundle) {
      $allowed_view_modes_by_bundle = $this->entityDisplayRepository->getViewModeOptionsByBundle('media', $bundle);

      foreach (array_keys($allowed_view_modes_by_bundle) as $view_mode) {
        // Get the bundles that have this view mode enabled.
        $bundles_per_view_mode[$view_mode][] = $bundle;
      }
    }
    // Limit to view modes allowed by filter.
    $bundles_per_view_mode = array_intersect_key($bundles_per_view_mode, $allowed_view_modes);

    // Configure view mode element styles.
    foreach (array_keys($all_view_modes) as $view_mode) {
      if (array_key_exists($view_mode, $bundles_per_view_mode)) {
        $specific_bundles = $bundles_per_view_mode[$view_mode];
        if ($view_mode == $default_view_mode) {
          $element_style_configuration[] = [
            'isDefault' => TRUE,
            'name' => $default_view_mode,
            'title' => $all_view_modes[$view_mode],
            'attributeName' => 'data-view-mode',
            'attributeValue' => $view_mode,
            'modelElements' => ['drupalMedia'],
            'modelAttributes' => [
              'drupalMediaType' => array_keys($media_bundles),
            ],
          ];
        }
        else {
          $element_style_configuration[] = [
            'name' => $view_mode,
            'title' => $all_view_modes[$view_mode],
            'attributeName' => 'data-view-mode',
            'attributeValue' => $view_mode,
            'modelElements' => ['drupalMedia'],
            'modelAttributes' => [
              'drupalMediaType' => $specific_bundles,
            ],
          ];
        }
      }
    }

    $items = [];

    foreach (array_keys($allowed_view_modes) as $view_mode) {
      $items[] = "drupalElementStyle:viewMode:$view_mode";
    }

    $default_item = 'drupalElementStyle:viewMode:' . $default_view_mode;
    if (!empty($allowed_view_modes)) {
      // Configure toolbar dropdown menu.
      $toolbar_configuration = [
        'name' => 'drupalMedia:viewMode',
        'display' => 'listDropdown',
        'defaultItem' => $default_item,
        'defaultText' => 'View mode',
        'items' => $items,
      ];
    }
    return [
      $element_style_configuration,
      $toolbar_configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $dynamic_plugin_config = $static_plugin_config;
    $dynamic_plugin_config['drupalMedia']['previewURL'] = Url::fromRoute('media.filter.preview')
      ->setRouteParameter('filter_format', $editor->getFilterFormat()->id())
      ->toString(TRUE)
      ->getGeneratedUrl();
    [$element_style_configuration, $toolbar_configuration,
    ] = self::configureViewModes($editor);

    $dynamic_plugin_config['drupalElementStyles']['viewMode'] = $element_style_configuration;
    $dynamic_plugin_config['drupalMedia']['toolbar'][] = $toolbar_configuration;
    $dynamic_plugin_config['drupalMedia']['metadataUrl'] = self::getUrlWithReplacedCsrfTokenPlaceholder(
      Url::fromRoute('ckeditor5.media_entity_metadata')
        ->setRouteParameter('editor', $editor->id())
    );
    $dynamic_plugin_config['drupalMedia']['previewCsrfToken'] = \Drupal::csrfToken()->get('X-Drupal-MediaPreview-CSRF-Token');
    return $dynamic_plugin_config;
  }

}
