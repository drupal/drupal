<?php

namespace Drupal\image\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Theme hook implementations for image module.
 */
class ImageThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    protected readonly ImageFactory $imageFactory,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TimeInterface $time,
    #[AutowireServiceClosure('logger.channel.image')]
    protected readonly \Closure $imageLogger,
  ) {
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'image_style' => [
        // HTML 4 and XHTML 1.0 always require an alt attribute. The HTML 5
        // draft allows the alt attribute to be omitted in some cases.
        // Therefore, default the alt attribute to an empty string, but allow
        // code using '#theme' => 'image_style' to pass explicit NULL for it to
        // be omitted.
        // Usually, neither omission nor an empty string satisfies accessibility
        // requirements, so it is strongly encouraged for code using '#theme' =>
        // 'image_style' to pass a meaningful value for the alt variable.
        // - https://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
        // - https://www.w3.org/TR/xhtml1/dtds.html
        // - http://dev.w3.org/html5/spec/Overview.html#alt
        // The title attribute is optional in all cases, so it is omitted by
        // default.
        'variables' => [
          'style_name' => NULL,
          'uri' => NULL,
          'width' => NULL,
          'height' => NULL,
          'alt' => '',
          'title' => NULL,
          'attributes' => [],
        ],
        'initial preprocess' => static::class . ':preprocessImageStyle',
      ],
      'image_style_preview' => [
        'variables' => [
          'style' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessImageStylePreview',
      ],
      'image_anchor' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessImageAnchor',
      ],
      'image_resize_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_scale_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_crop_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_scale_and_crop_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_rotate_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_widget' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessImageWidget',
      ],
      'image_formatter' => [
        'variables' => [
          'item' => NULL,
          'item_attributes' => NULL,
          'url' => NULL,
          'image_style' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessImageFormatter',
      ],
    ];
  }

  /**
   * Prepares variables for image widget templates.
   *
   * Default template: image-widget.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: A render element representing the image field widget.
   */
  public function preprocessImageWidget(array &$variables): void {
    $element = $variables['element'];

    $variables['attributes'] = ['class' => ['image-widget', 'js-form-managed-file', 'form-managed-file', 'clearfix']];

    $variables['data'] = [];
    foreach (Element::children($element) as $child) {
      $variables['data'][$child] = $element[$child];
    }

  }

  /**
   * Prepares variables for image formatter templates.
   *
   * Default template: image-formatter.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - item: An ImageItem object.
   *   - item_attributes: An optional associative array of html attributes to be
   *     placed in the img tag.
   *   - image_style: An optional image style.
   *   - url: An optional \Drupal\Core\Url object.
   */
  public function preprocessImageFormatter(array &$variables): void {
    if ($variables['image_style']) {
      $variables['image'] = [
        '#theme' => 'image_style',
        '#style_name' => $variables['image_style'],
      ];
    }
    else {
      $variables['image'] = [
        '#theme' => 'image',
      ];
    }
    $variables['image']['#attributes'] = $variables['item_attributes'];

    $item = $variables['item'];

    // Do not output an empty 'title' attribute.
    if (!is_null($item->title) && mb_strlen($item->title) != 0) {
      $variables['image']['#title'] = $item->title;
    }

    if (($entity = $item->entity) && empty($item->uri)) {
      $variables['image']['#uri'] = $entity->getFileUri();
    }
    else {
      $variables['image']['#uri'] = $item->uri;
    }

    foreach (['width', 'height', 'alt'] as $key) {
      $variables['image']["#$key"] = $item->$key;
    }
  }

  /**
   * Prepares variables for image style preview templates.
   *
   * Default template: image-style-preview.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - style: \Drupal\image\ImageStyleInterface image style being previewed.
   */
  public function preprocessImageStylePreview(array &$variables): void {
    // Style information.
    $style = $variables['style'];
    $variables['style_id'] = $style->id();
    $variables['style_name'] = $style->label();

    // Cache bypass token.
    $variables['cache_bypass'] = $this->time->getRequestTime();

    // Sample image info.
    $sample_width = 160;
    $sample_height = 160;

    // Set up original file information.
    $original_path = $this->configFactory->get('image.settings')->get('preview_image');
    $original_image = $this->imageFactory->get($original_path);
    $variables['original'] = [
      'url' => $this->fileUrlGenerator->generateString($original_path),
      'width' => $original_image->getWidth(),
      'height' => $original_image->getHeight(),
    ];
    if ($variables['original']['width'] > $variables['original']['height']) {
      $variables['preview']['original']['width'] = min($variables['original']['width'], $sample_width);
      $variables['preview']['original']['height'] = round($variables['preview']['original']['width'] / $variables['original']['width'] * $variables['original']['height']);
    }
    else {
      $variables['preview']['original']['height'] = min($variables['original']['height'], $sample_height);
      $variables['preview']['original']['width'] = round($variables['preview']['original']['height'] / $variables['original']['height'] * $variables['original']['width']);
    }

    // Set up derivative file information.
    $preview_file = $style->buildUri($original_path);
    // Create derivative if necessary.
    if (!file_exists($preview_file)) {
      $style->createDerivative($original_path, $preview_file);
    }
    $preview_image = $this->imageFactory->get($preview_file);

    // Generate an itok.
    $defaultScheme = $this->configFactory->get('system.file')->get('default_scheme');
    $variables['itok'] = $style->getPathToken($defaultScheme . '://' . $original_path);

    $variables['derivative'] = [
      'url' => $this->fileUrlGenerator->generateString($preview_file),
      'width' => $preview_image->getWidth(),
      'height' => $preview_image->getHeight(),
    ];
    if ($variables['derivative']['width'] > $variables['derivative']['height']) {
      $variables['preview']['derivative']['width'] = min($variables['derivative']['width'], $sample_width);
      $variables['preview']['derivative']['height'] = round($variables['preview']['derivative']['width'] / $variables['derivative']['width'] * $variables['derivative']['height']);
    }
    else {
      $variables['preview']['derivative']['height'] = min($variables['derivative']['height'], $sample_height);
      $variables['preview']['derivative']['width'] = round($variables['preview']['derivative']['height'] / $variables['derivative']['height'] * $variables['derivative']['width']);
    }

    // Build the preview of the original image.
    $variables['original']['rendered'] = [
      '#theme' => 'image',
      '#uri' => $original_path,
      '#alt' => $this->t('Source image: @width pixels wide, @height pixels high', [
        '@width' => $variables['original']['width'],
        '@height' => $variables['original']['height'],
      ]),
      '#title' => '',
      '#attributes' => [
        'width' => $variables['original']['width'],
        'height' => $variables['original']['height'],
        'style' => 'width: ' . $variables['preview']['original']['width'] . 'px; height: ' . $variables['preview']['original']['height'] . 'px;',
      ],
    ];

    // Build the preview of the image style derivative. Timestamps are added
    // to prevent caching of images on the client side.
    $variables['derivative']['rendered'] = [
      '#theme' => 'image',
      '#uri' => $variables['derivative']['url'] . '?cache_bypass=' . $variables['cache_bypass'] . '&itok=' . $variables['itok'],
      '#alt' => $this->t('Derivative image: @width pixels wide, @height pixels high', [
        '@width' => $variables['derivative']['width'],
        '@height' => $variables['derivative']['height'],
      ]),
      '#title' => '',
      '#attributes' => [
        'width' => $variables['derivative']['width'],
        'height' => $variables['derivative']['height'],
        'style' => 'width: ' . $variables['preview']['derivative']['width'] . 'px; height: ' . $variables['preview']['derivative']['height'] . 'px;',
      ],
    ];

  }

  /**
   * Prepares variables for image anchor templates.
   *
   * Default template: image-anchor.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the image.
   */
  public function preprocessImageAnchor(array &$variables): void {
    $element = $variables['element'];

    $rows = [];
    $row = [];
    foreach (Element::children($element) as $n => $key) {
      $element[$key]['#attributes']['title'] = $element[$key]['#title'];
      unset($element[$key]['#title']);
      $row[] = [
        'data' => $element[$key],
      ];
      if ($n % 3 == 3 - 1) {
        $rows[] = $row;
        $row = [];
      }
    }

    $variables['table'] = [
      '#type' => 'table',
      '#header' => [],
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['image-anchor'],
      ],
    ];
  }

  /**
   * Prepares variables for image style templates.
   *
   * Default template: image-style.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - width: The width of the image.
   *   - height: The height of the image.
   *   - style_name: The name of the image style to be applied.
   *   - uri: URI of the source image before styling.
   *   - alt: The alternative text for text-based browsers. HTML 4 and XHTML 1.0
   *     always require an alt attribute. The HTML 5 draft allows the alt
   *     attribute to be omitted in some cases. Therefore, this variable
   *     defaults to an empty string, but can be set to NULL for the attribute
   *     to be omitted. Usually, neither omission nor an empty string satisfies
   *     accessibility requirements, so it is strongly encouraged for code using
   *     '#theme' => 'image_style' to pass a meaningful value for this variable.
   *     - https://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
   *     - https://www.w3.org/TR/xhtml1/dtds.html
   *     - http://dev.w3.org/html5/spec/Overview.html#alt
   *   - title: The title text is displayed when the image is hovered in some
   *     popular browsers.
   *   - attributes: Associative array of additional attributes to be placed in
   *     the img tag.
   */
  public function preprocessImageStyle(array &$variables): void {
    $style = ImageStyle::load($variables['style_name']);

    // Determine the dimensions of the styled image.
    $dimensions = [
      'width' => $variables['width'],
      'height' => $variables['height'],
    ];

    $style->transformDimensions($dimensions, $variables['uri']);

    $variables['image'] = [
      '#theme' => 'image',
      '#width' => $dimensions['width'],
      '#height' => $dimensions['height'],
      '#attributes' => $variables['attributes'],
      '#style_name' => $variables['style_name'],
    ];

    // If the current image toolkit supports this file type, prepare the URI for
    // the derivative image. If not, just use the original image resized to the
    // dimensions specified by the style.
    if ($style->supportsUri($variables['uri'])) {
      $variables['image']['#uri'] = $style->buildUrl($variables['uri']);
    }
    else {
      $variables['image']['#uri'] = $variables['uri'];
      // Don't render the image by default, but allow other preprocess functions
      // to override that if they need to.
      $variables['image']['#access'] = FALSE;

      // Inform the site builders why their image didn't work.
      ($this->imageLogger)()->warning('Could not apply @style image style to @uri because the style does not support it.', [
        '@style' => $style->label(),
        '@uri' => $variables['uri'],
      ]);
    }

    if (\array_key_exists('alt', $variables)) {
      $variables['image']['#alt'] = $variables['alt'];
    }
    if (\array_key_exists('title', $variables)) {
      $variables['image']['#title'] = $variables['title'];
    }

  }

}
