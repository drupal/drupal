<?php

namespace Drupal\media\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to embed media items using a custom tag.
 *
 * @internal
 */
#[Filter(
  id: "media_embed",
  title: new TranslatableMarkup("Embed media"),
  description: new TranslatableMarkup("Embeds media items using a custom tag, <code>&lt;drupal-media&gt;</code>. If used in conjunction with the 'Align/Caption' filters, make sure this filter is configured to run after them."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
  weight: 100,
  settings: [
    "default_view_mode" => "default",
    "allowed_view_modes" => [],
    "allowed_media_types" => [],
  ],
)]
class MediaEmbed extends FilterBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * An array of counters for the recursive rendering protection.
   *
   * Each counter takes into account all the relevant information about the
   * field and the referenced entity that is being rendered.
   *
   * @var array
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter::$recursiveRenderDepth
   */
  protected static $recursiveRenderDepth = [];

  /**
   * Constructs a MediaEmbed object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeBundleInfoInterface $bundle_info, RendererInterface $renderer, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeBundleInfo = $bundle_info;
    $this->renderer = $renderer;
    $this->loggerFactory = $logger_factory;
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
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('renderer'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $view_mode_options = $this->entityDisplayRepository->getViewModeOptions('media');

    $form['default_view_mode'] = [
      '#type' => 'select',
      '#options' => $view_mode_options,
      '#title' => $this->t('Default view mode'),
      '#default_value' => $this->settings['default_view_mode'],
      '#description' => $this->t('The view mode that an embedded media item should be displayed in by default. This can be overridden using the <code>data-view-mode</code> attribute.'),
    ];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
    $bundle_options = array_map(function ($item) {
      return $item['label'];
    }, $bundles);
    $form['allowed_media_types'] = [
      '#title' => $this->t('Media types selectable in the Media Library'),
      '#type' => 'checkboxes',
      '#options' => $bundle_options,
      '#default_value' => $this->settings['allowed_media_types'],
      '#description' => $this->t('If none are selected, all will be allowed.'),
      '#element_validate' => [[static::class, 'validateOptions']],
    ];

    $form['allowed_view_modes'] = [
      '#title' => $this->t("View modes selectable in the 'Edit media' dialog"),
      '#type' => 'checkboxes',
      '#options' => $view_mode_options,
      '#default_value' => $this->settings['allowed_view_modes'],
      '#description' => $this->t("If two or more view modes are selected, users will be able to update the view mode that an embedded media item should be displayed in after it has been embedded.  If less than two view modes are selected, media will be embedded using the default view mode and no view mode options will appear after a media item has been embedded."),
      '#element_validate' => [[static::class, 'validateOptions']],
    ];

    return $form;
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The allowed_view_modes form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateOptions(array &$element, FormStateInterface $form_state) {
    // Filters the #value property so only selected values appear in the
    // config.
    $form_state->setValueForElement($element, array_filter($element['#value']));
  }

  /**
   * Builds the render array for the given media entity in the given langcode.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media entity to render.
   * @param string $view_mode
   *   The view mode to render it in.
   * @param string $langcode
   *   Language code in which the media entity should be rendered.
   *
   * @return array
   *   A render array.
   */
  protected function renderMedia(MediaInterface $media, $view_mode, $langcode) {
    // Due to render caching and delayed calls, filtering happens later
    // in the rendering process through a '#pre_render' callback, so we
    // need to generate a counter for the media entity that is being embedded.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    $recursive_render_id = $media->uuid();
    if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
      static::$recursiveRenderDepth[$recursive_render_id]++;
    }
    else {
      static::$recursiveRenderDepth[$recursive_render_id] = 1;
    }
    // Protect ourselves from recursive rendering: return an empty render array.
    if (static::$recursiveRenderDepth[$recursive_render_id] > EntityReferenceEntityFormatter::RECURSIVE_RENDER_LIMIT) {
      $this->loggerFactory->get('media')->error('During rendering of embedded media: recursive rendering detected for %entity_id. Aborting rendering.', [
        '%entity_id' => $media->id(),
      ]);
      return [];
    }

    $build = $this->entityTypeManager
      ->getViewBuilder('media')
      ->view($media, $view_mode, $langcode);

    // Allows other modules to treat embedded media items differently.
    $build['#embed'] = TRUE;

    // There are a few concerns when rendering an embedded media entity:
    // - entity access checking happens not during rendering but during routing,
    //   and therefore we have to do it explicitly here for the embedded entity.
    $build['#access'] = $media->access('view', NULL, TRUE);
    // - caching an embedded media entity separately is unnecessary; the host
    //   entity is already render cached.
    unset($build['#cache']['keys']);
    // - Contextual Links do not make sense for embedded entities; we only allow
    //   the host entity to be contextually managed.
    $build['#pre_render'][] = static::class . '::disableContextualLinks';
    // - default styling may break captioned media embeds; attach asset library
    //   to ensure captions behave as intended. Do not set this at the root
    //   level of the render array, otherwise it will be attached always,
    //   instead of only when #access allows this media to be viewed and hence
    //   only when media is actually rendered.
    $build[':media_embed']['#attached']['library'][] = 'media/filter.caption';

    return $build;
  }

  /**
   * Builds the render array for the indicator when media cannot be loaded.
   *
   * @return array
   *   A render array.
   */
  protected function renderMissingMediaIndicator() {
    return [
      '#theme' => 'media_embed_error',
      '#message' => $this->t('The referenced media source is missing and needs to be re-embedded.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, '<drupal-media') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    foreach ($xpath->query('//drupal-media[@data-entity-type="media" and normalize-space(@data-entity-uuid)!=""]') as $node) {
      /** @var \DOMElement $node */
      $uuid = $node->getAttribute('data-entity-uuid');
      $view_mode_id = $node->getAttribute('data-view-mode') ?: $this->settings['default_view_mode'];

      // Delete the consumed attributes.
      $node->removeAttribute('data-entity-type');
      $node->removeAttribute('data-entity-uuid');
      $node->removeAttribute('data-view-mode');

      $media = $this->entityRepository->loadEntityByUuid('media', $uuid);
      assert($media === NULL || $media instanceof MediaInterface);
      if (!$media) {
        $this->loggerFactory->get('media')->error('During rendering of embedded media: the media item with UUID "@uuid" does not exist.', ['@uuid' => $uuid]);
      }
      else {
        $media = $this->entityRepository->getTranslationFromContext($media, $langcode);
        $media = clone $media;
        $this->applyPerEmbedMediaOverrides($node, $media);
      }

      $view_mode = NULL;
      if ($view_mode_id !== EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE) {
        $view_mode = $this->entityRepository->loadEntityByConfigTarget('entity_view_mode', "media.$view_mode_id");
        if (!$view_mode) {
          $this->loggerFactory->get('media')->error('During rendering of embedded media: the view mode "@view-mode-id" does not exist.', ['@view-mode-id' => $view_mode_id]);
        }
      }

      $build = $media && ($view_mode || $view_mode_id === EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE)
        ? $this->renderMedia($media, $view_mode_id, $langcode)
        : $this->renderMissingMediaIndicator();

      if (empty($build['#attributes']['class'])) {
        $build['#attributes']['class'] = [];
      }
      // Any attributes not consumed by the filter should be carried over to the
      // rendered embedded entity. For example, `data-align` and `data-caption`
      // should be carried over, so that even when embedded media goes missing,
      // at least the caption and visual structure won't get lost.
      foreach ($node->attributes as $attribute) {
        if ($attribute->nodeName == 'class') {
          // We don't want to overwrite the existing CSS class of the embedded
          // media (or if the media entity can't be loaded, the missing media
          // indicator). But, we need to merge in CSS classes added by other
          // filters, such as filter_align, in order for those filters to work
          // properly.
          $build['#attributes']['class'] = array_unique(array_merge($build['#attributes']['class'], explode(' ', $attribute->nodeValue)));
        }
        else {
          $build['#attributes'][$attribute->nodeName] = $attribute->nodeValue;
        }
      }

      $this->renderIntoDomNode($build, $node, $result);
    }

    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
      <p>You can embed media items:</p>
      <ul>
        <li>Choose which media item to embed: <code>&lt;drupal-media data-entity-uuid="07bf3a2e-1941-4a44-9b02-2d1d7a41ec0e" /&gt;</code></li>
        <li>Optionally also choose a view mode: <code>data-view-mode="tiny_embed"</code>, otherwise the default view mode is used.</li>
        <li>The <code>data-entity-type="media"</code> attribute is required for consistency.</li>
      </ul>');
    }
    else {
      return $this->t('You can embed media items (using the <code>&lt;drupal-media&gt;</code> tag).');
    }
  }

  /**
   * Renders the given render array into the given DOM node.
   *
   * @param array $build
   *   The render array to render in isolation.
   * @param \DOMNode $node
   *   The DOM node to render into.
   * @param \Drupal\filter\FilterProcessResult $result
   *   The accumulated result of filter processing, updated with the metadata
   *   bubbled during rendering.
   */
  protected function renderIntoDomNode(array $build, \DOMNode $node, FilterProcessResult &$result) {
    // We need to render the embedded entity:
    // - without replacing placeholders, so that the placeholders are
    //   only replaced at the last possible moment. Hence we cannot use
    //   either renderInIsolation() or renderRoot(), so we must use render().
    // - without bubbling beyond this filter, because filters must
    //   ensure that the bubbleable metadata for the changes they make
    //   when filtering text makes it onto the FilterProcessResult
    //   object that they return ($result). To prevent that bubbling, we
    //   must wrap the call to render() in a render context.
    $markup = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
      return $this->renderer->render($build);
    });
    $result = $result->merge(BubbleableMetadata::createFromRenderArray($build));
    static::replaceNodeContent($node, $markup);
  }

  /**
   * Replaces the contents of a DOMNode.
   *
   * @param \DOMNode $node
   *   A DOMNode object.
   * @param string $content
   *   The text or HTML that will replace the contents of $node.
   */
  protected static function replaceNodeContent(\DOMNode &$node, $content) {
    if (strlen($content)) {
      // Load the content into a new DOMDocument and retrieve the DOM nodes.
      $replacement_nodes = Html::load($content)->getElementsByTagName('body')
        ->item(0)
        ->childNodes;
    }
    else {
      $replacement_nodes = [$node->ownerDocument->createTextNode('')];
    }

    foreach ($replacement_nodes as $replacement_node) {
      // Import the replacement node from the new DOMDocument into the original
      // one, importing also the child nodes of the replacement node.
      $replacement_node = $node->ownerDocument->importNode($replacement_node, TRUE);
      $node->parentNode->insertBefore($replacement_node, $node);
    }
    $node->parentNode->removeChild($node);
  }

  /**
   * Disables Contextual Links for the embedded media by removing its property.
   *
   * @param array $build
   *   The render array for the embedded media.
   *
   * @return array
   *   The updated render array.
   *
   * @see \Drupal\Core\Entity\EntityViewBuilder::addContextualLinks()
   */
  public static function disableContextualLinks(array $build) {
    unset($build['#contextual_links']);
    return $build;
  }

  /**
   * Applies attribute-based per-media embed overrides of media information.
   *
   * Currently, this only supports overriding an image media source's `alt` and
   * `title`. Support for more overrides may be added in the future.
   *
   * @param \DOMElement $node
   *   The HTML tag whose attributes may contain overrides, and if such
   *   attributes are applied, they will be considered consumed and will
   *   therefore be removed from the HTML.
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to apply attribute-based overrides to, if any.
   *
   * @see \Drupal\media\Plugin\media\Source\Image
   */
  protected function applyPerEmbedMediaOverrides(\DOMElement $node, MediaInterface $media) {
    if ($image_field = $this->getMediaImageSourceField($media)) {
      $settings = $media->{$image_field}->getItemDefinition()->getSettings();

      if (!empty($settings['alt_field']) && $node->hasAttribute('alt')) {
        // Allow the display of the image without an alt tag in special cases.
        // Since setting the value in the EditorMediaDialog to an empty string
        // restores the default value, this allows special cases where the alt
        // text should not be set to the default value, but should be
        // explicitly empty instead so it can be ignored by assistive
        // technologies, such as screen readers.
        if ($node->getAttribute('alt') === '""') {
          $node->setAttribute('alt', '');
        }
        $media->{$image_field}->alt = $node->getAttribute('alt');
        // All media entities have a thumbnail. In the case of image media, it
        // is conceivable that a particular view mode chooses to display the
        // thumbnail instead of the image field itself since the thumbnail
        // simply shows a smaller version of the actual media. So we must update
        // its `alt` too. Because its `alt` already is inherited from the image
        // field's `alt` at entity save time.
        // @see \Drupal\media\Plugin\media\Source\Image::getMetadata()
        $media->thumbnail->alt = $node->getAttribute('alt');
        // Delete the consumed attribute.
        $node->removeAttribute('alt');
      }

      if (!empty($settings['title_field']) && $node->hasAttribute('title')) {
        // See above, the explanations for `alt` also apply to `title`.
        $media->{$image_field}->title = $node->getAttribute('title');
        $media->thumbnail->title = $node->getAttribute('title');
        // Delete the consumed attribute.
        $node->removeAttribute('title');
      }
    }
  }

  /**
   * Get image field from source config.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media entity.
   *
   * @return string|null
   *   String of image field name.
   */
  protected function getMediaImageSourceField(MediaInterface $media) {
    $field_definition = $media->getSource()
      ->getSourceFieldDefinition($media->bundle->entity);
    $item_class = $field_definition->getItemDefinition()->getClass();
    if ($item_class == ImageItem::class || is_subclass_of($item_class, ImageItem::class)) {
      return $field_definition->getName();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['disableContextualLinks'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    // Combine the view modes from both config parameters.
    $view_modes = $this->settings['allowed_view_modes'] + [$this->settings['default_view_mode']];
    $view_modes = array_unique(array_values($view_modes));
    $dependencies += ['config' => []];
    $storage = $this->entityTypeManager->getStorage('entity_view_mode');
    foreach ($view_modes as $view_mode) {
      if ($entity_view_mode = $storage->load('media.' . $view_mode)) {
        $dependencies[$entity_view_mode->getConfigDependencyKey()][] = $entity_view_mode->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

}
