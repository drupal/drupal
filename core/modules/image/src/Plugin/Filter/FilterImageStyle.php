<?php

namespace Drupal\image\Plugin\Filter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to render inline images as image styles.
 *
 * @Filter(
 *   id = "filter_image_style",
 *   module = "image",
 *   title = @Translation("Display image styles"),
 *   description = @Translation("Uses the data-image-style attribute on &lt;img&gt; tags to display image styles."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "allowed_styles" = {},
 *   },
 * )
 */
class FilterImageStyle extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a \Drupal\image\Plugin\Filter\FilterImageStyle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, ImageFactory $image_factory, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->imageFactory = $image_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('image.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    // Don't process the filter if no image style img elements are found.
    if (stristr($text, 'data-image-style') === FALSE) {
      return new FilterProcessResult($text);
    }
    // Load all image styles so each image found in the text can be checked
    // against a valid image style.
    $image_styles = array_keys($this->getAllowedImageStyles());

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Process each image element found with the necessary attributes.
    /** @var \DOMElement $dom_element */
    foreach ($xpath->query('//img[@data-entity-uuid and @data-image-style]') as $dom_element) {
      // Get the UUID and image style for the file.
      $file_uuid = $dom_element->getAttribute('data-entity-uuid');
      $image_style_id = $dom_element->getAttribute('data-image-style');

      // If the image style is not a valid one, then don't transform the HTML.
      if (empty($file_uuid) || !in_array($image_style_id, $image_styles, TRUE)) {
        continue;
      }
      if (!$this->entityRepository->loadEntityByUuid('file', $file_uuid)) {
        continue;
      }

      // Transform the HTML for the img element by applying an image style.
      $altered_img_markup = $this->getImageStyleHtml($file_uuid, $image_style_id, $dom_element);
      $altered_img = $dom->createDocumentFragment();
      $altered_img->appendXML($altered_img_markup);
      $dom_element->parentNode->replaceChild($altered_img, $dom_element);
    }

    return new FilterProcessResult(Html::serialize($dom));
  }

  /**
   * Returns the allowed image styles.
   *
   * @return \Drupal\image\ImageStyleInterface[]
   *   The allowed image styles.
   */
  protected function getAllowedImageStyles(): array {
    $ids = !empty($this->settings['allowed_styles']) ? $this->settings['allowed_styles'] : NULL;
    /** @var \Drupal\image\ImageStyleInterface[] $image_styles */
    $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple($ids);
    return $image_styles;
  }

  /**
   * Returns a list of image styles to be used as '#options' in select elements.
   *
   * @return string[]
   *   An associative array of image style labels keyed by their image style ID.
   */
  public function getAllowedImageStylesAsOptions(): array {
    return array_map(function (ImageStyleInterface $image_style): string {
      return $image_style->label();
    }, $this->getAllowedImageStyles());
  }

  /**
   * Returns a list of image style theme variables given the image file UUID.
   *
   * @param string $file_uuid
   *   The UUID for the file.
   *
   * @return array
   *   Image information as an associative array with the following keys:
   *   - #uri: The image URI,
   *   - #width: The image width,
   *   - #height: The image height.
   *   Note that prefixing the keys with '#' makes possible to pass these values
   *   directly as theme variables, in ::getImageStyleHtml().
   *
   * @see \Drupal\image\Plugin\Filter\FilterImageStyle::getImageStyleHtml()
   */
  protected function getImageStyleThemeVariables(string $file_uuid): array {
    $image_uri = $image_width = $image_height = NULL;

    /** @var \Drupal\file\FileInterface $file */
    if ($file = $this->entityRepository->loadEntityByUuid('file', $file_uuid)) {
      $image_uri = $file->getFileUri();

      // Determine the width and height of the source image.
      $image = $this->imageFactory->get($file->getFileUri());
      if ($image->isValid()) {
        $image_width = $image->getWidth();
        $image_height = $image->getHeight();
      }
    }

    return [
      '#uri' => $image_uri,
      '#width' => $image_width,
      '#height' => $image_height,
    ];
  }

  /**
   * Removes attributes that will be generated from image style theme function.
   *
   * The method prepares a list of image attributes by removing those that are
   * added by the image style theme, such as src, width, height. The list is
   * passed to the image style theme as #attributes theme variable, in
   * ::getImageStyleHtml().
   *
   * @param \DOMElement $dom_element
   *   The DOM element for the img element.
   *
   * @return array
   *   The attributes, as an associative array, keyed by attribute name and
   *   having the attribute value as values.
   *
   * @see \Drupal\image\Plugin\Filter\FilterImageStyle::getImageStyleHtml()
   */
  protected function prepareImageAttributesForTheme(\DOMElement $dom_element): array {
    // @todo Given that this piece of code is potentially useful elsewhere, move
    //   it into into a utility method, in #3000715.
    // @see https://www.drupal.org/project/drupal/issues/3000715
    $attributes = [];
    for ($i = 0; $i < $dom_element->attributes->length; $i++) {
      $attribute = $dom_element->attributes->item($i);
      // Keep attributes from the original image markup, except those that are
      // specific to image style theme.
      if (!in_array($attribute->name, ['src', 'width', 'height'], TRUE)) {
        $attributes[$attribute->name] = $attribute->value;
      }
    }

    return $attributes;
  }

  /**
   * Gets the HTML for the image element after image style is applied.
   *
   * @param string $file_uuid
   *   The UUID for the file.
   * @param string $image_style_id
   *   The ID for the image style.
   * @param \DOMElement $dom_element
   *   The DOM element for the image element.
   *
   * @return string
   *   The img element with the image style applied.
   */
  protected function getImageStyleHtml(string $file_uuid, string $image_style_id, \DOMElement $dom_element): string {
    // Remove attributes that will be generated by the image style.
    $attributes = $this->prepareImageAttributesForTheme($dom_element);

    // Re-render as an image style.
    $image = [
      '#theme' => 'image_style',
      '#style_name' => $image_style_id,
      '#attributes' => $attributes,
    ] + $this->getImageStyleThemeVariables($file_uuid);

    return $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$image): MarkupInterface {
      return $this->renderer->render($image);
    })->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $options = array_map(function (ImageStyleInterface $image_style): string {
      return $image_style->label();
    }, $image_styles);
    $is_select = count($image_styles) > 10;
    $form['allowed_styles'] = [
      '#type' => $is_select ? 'select' : 'checkboxes',
      '#title' => $this->t('Allowed image styles'),
      '#options' => $options,
      '#default_value' => $this->settings['allowed_styles'],
      '#description' => $this->t('The image styles that can be used. If none are selected then all image styles can be used.'),
    ];
    if ($is_select) {
      $form['allowed_styles']['#multiple'] = TRUE;
      // Limit the select box in length if there are a large number of image
      // styles.
      $form['allowed_styles']['#size'] = min(20, count($image_styles));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): self {
    parent::setConfiguration($configuration);
    // The allowed styles can be a select list or checkboxes. Checkboxes should
    // be filtered to remove unselected options and it doesn't harm selects.
    if (isset($this->settings['allowed_styles'])) {
      $this->settings['allowed_styles'] = array_values(array_filter($this->settings['allowed_styles']));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return [
        [
          '#markup' => $this->t('You can display images using site-wide styles by adding a <code>data-image-style</code> attribute whose values is the image style machine-name. The following image styles can be used:'),
        ],
        [
          '#theme' => 'item_list',
          '#items' => $this->getAllowedImageStylesAsOptions(),
        ],
        [
          '#markup' => $this->t('The image file <code>data-entity-uuid</code> should be also present.'),
        ],
      ];
    }
    return $this->t('You can display images using site-wide styles by adding a <code>data-image-style</code> attribute.');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();

    if ($this->settings['allowed_styles']) {
      foreach ($this->getAllowedImageStyles() as $image_style) {
        $dependencies[$image_style->getConfigDependencyKey()][] = $image_style->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);

    if ($this->settings['allowed_styles']) {
      foreach ($this->getAllowedImageStyles() as $image_style_id => $image_style) {
        if (isset($dependencies[$image_style->getConfigDependencyKey()][$image_style->getConfigDependencyName()])) {
          unset($this->settings['allowed_styles'][array_search($image_style_id, $this->settings['allowed_styles'])]);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

}
