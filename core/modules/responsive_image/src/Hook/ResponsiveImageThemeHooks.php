<?php

namespace Drupal\responsive_image\Hook;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\responsive_image\ResponsiveImageBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Theme hooks for responsive_image.
 */
class ResponsiveImageThemeHooks {

  public function __construct(
    protected BreakpointManagerInterface $breakpointManager,
    #[AutowireServiceClosure('logger.responsive_image')]
    protected \Closure $loggerClosure,
    protected ResponsiveImageBuilder $responsiveImageBuilder,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'responsive_image' => [
        'variables' => [
          'uri' => NULL,
          'attributes' => [],
          'responsive_image_style_id' => [],
          'height' => NULL,
          'width' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessResponsiveImage',
      ],
      'responsive_image_formatter' => [
        'variables' => [
          'attributes' => [],
          'item' => NULL,
          'item_attributes' => NULL,
          'url' => NULL,
          'responsive_image_style_id' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessResponsiveImageFormatter',
      ],
    ];
  }

  /**
   * Prepares variables for responsive image formatter templates.
   *
   * Default template: responsive-image-formatter.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - item: An ImageItem object.
   *   - item_attributes: An optional associative array of HTML attributes to be
   *     placed in the img tag.
   *   - responsive_image_style_id: A responsive image style.
   *   - url: An optional \Drupal\Core\Url object.
   */
  public function preprocessResponsiveImageFormatter(&$variables): void {
    // Provide fallback to standard image if valid responsive image style is not
    // provided in the responsive image formatter.
    $responsive_image_style = ResponsiveImageStyle::load($variables['responsive_image_style_id']);
    if ($responsive_image_style) {
      $variables['responsive_image'] = [
        '#type' => 'responsive_image',
        '#responsive_image_style_id' => $variables['responsive_image_style_id'],
      ];
    }
    else {
      $variables['responsive_image'] = [
        '#theme' => 'image',
      ];
    }
    $item = $variables['item'];
    $attributes = [];
    // Do not output an empty 'title' attribute.
    if (!is_null($item->title) && mb_strlen($item->title) != 0) {
      $attributes['title'] = $item->title;
    }
    $attributes['alt'] = $item->alt;
    // Need to check that item_attributes has a value since it can be NULL.
    if ($variables['item_attributes']) {
      $attributes += $variables['item_attributes'];
    }
    if (($entity = $item->entity) && empty($item->uri)) {
      $variables['responsive_image']['#uri'] = $entity->getFileUri();
    }
    else {
      $variables['responsive_image']['#uri'] = $item->uri;
    }

    // Override any attributes with those set in the render array.
    $attributes = array_merge($attributes, $variables['attributes']);

    foreach (['width', 'height'] as $key) {
      $variables['responsive_image']["#$key"] = $item->$key;
    }
    $variables['responsive_image']['#attributes'] = $attributes;
  }

  /**
   * Prepares variables for a responsive image.
   *
   * Default template: responsive-image.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - uri: The URI of the image.
   *   - width: The width of the image (if known).
   *   - height: The height of the image (if known).
   *   - attributes: Associative array of attributes to be placed in the img
   *     tag.
   *   - responsive_image_style_id: The ID of the responsive image style.
   */
  public function preprocessResponsiveImage(array &$variables): void {
    // Make sure that width and height are proper values, if they exist we'll
    // output them.
    if (isset($variables['width']) && empty($variables['width'])) {
      unset($variables['width']);
      unset($variables['height']);
    }
    elseif (isset($variables['height']) && empty($variables['height'])) {
      unset($variables['width']);
      unset($variables['height']);
    }

    $responsive_image_style = ResponsiveImageStyle::load($variables['responsive_image_style_id']);
    // If a responsive image style is not selected, log the error and stop
    // execution.
    if (!$responsive_image_style) {
      $variables['img_element'] = [];
      ($this->loggerClosure)()->error('Failed to load responsive image style: “@style“ while displaying responsive image.', ['@style' => $variables['responsive_image_style_id']]);
      return;
    }
    // Retrieve all breakpoints and multipliers and reverse order of
    // breakpoints. By default, breakpoints are ordered from smallest weight to
    // largest:
    // the smallest weight is expected to have the smallest breakpoint width,
    // while the largest weight is expected to have the largest breakpoint
    // width. For responsive images, we need largest breakpoint widths first, so
    // we need to reverse the order of these breakpoints.
    $breakpoints = array_reverse($this->breakpointManager->getBreakpointsByGroup($responsive_image_style->getBreakpointGroup()));
    foreach ($responsive_image_style->getKeyedImageStyleMappings() as $breakpoint_id => $multipliers) {
      if (isset($breakpoints[$breakpoint_id])) {
        $variables['sources'][] = $this->responsiveImageBuilder->buildSourceAttributes($variables, $breakpoints[$breakpoint_id], $multipliers);
      }
    }

    if (isset($variables['sources']) && count($variables['sources']) === 1 && !isset($variables['sources'][0]['media'])) {
      // There is only one source tag with an empty media attribute. This means
      // we can output an image tag with the srcset attribute instead of a
      // picture tag.
      $variables['output_image_tag'] = TRUE;
      foreach ($variables['sources'][0] as $attribute => $value) {
        if ($attribute != 'type') {
          $variables['attributes'][$attribute] = $value;
        }
      }
      $variables['img_element'] = [
        '#theme' => 'image',
        '#uri' => $this->responsiveImageBuilder->getImageStyleUrl($responsive_image_style->getFallbackImageStyle(), $variables['uri']),
        '#attributes' => [],
      ];
    }
    else {
      $variables['output_image_tag'] = FALSE;
      // Prepare the fallback image. We use the src attribute, which might cause
      // double downloads in browsers that don't support the picture tag.
      $variables['img_element'] = [
        '#theme' => 'image',
        '#uri' => $this->responsiveImageBuilder->getImageStyleUrl($responsive_image_style->getFallbackImageStyle(), $variables['uri']),
        '#attributes' => [],
      ];
    }

    // Get width and height from fallback responsive image style and transfer
    // them to img tag so browser can do aspect ratio calculation and prevent
    // recalculation of layout on image load.
    $dimensions = $this->responsiveImageBuilder->getImageDimensions($responsive_image_style->getFallbackImageStyle(), [
      'width' => $variables['width'],
      'height' => $variables['height'],
    ],
      $variables['uri']
    );
    $variables['img_element']['#width'] = $dimensions['width'];
    $variables['img_element']['#height'] = $dimensions['height'];

    if (isset($variables['attributes'])) {
      if (isset($variables['attributes']['alt'])) {
        $variables['img_element']['#alt'] = $variables['attributes']['alt'];
        unset($variables['attributes']['alt']);
      }
      if (isset($variables['attributes']['title'])) {
        $variables['img_element']['#title'] = $variables['attributes']['title'];
        unset($variables['attributes']['title']);
      }
      if (isset($variables['img_element']['#width'])) {
        $variables['attributes']['width'] = $variables['img_element']['#width'];
      }
      if (isset($variables['img_element']['#height'])) {
        $variables['attributes']['height'] = $variables['img_element']['#height'];
      }
      $variables['img_element']['#attributes'] = $variables['attributes'];
    }
  }

}
