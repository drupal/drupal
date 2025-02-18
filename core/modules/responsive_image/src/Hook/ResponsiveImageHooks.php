<?php

namespace Drupal\responsive_image\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for responsive_image.
 */
class ResponsiveImageHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.responsive_image':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Responsive Image module provides an image formatter that allows browsers to select which image file to display based on media queries or which image file types the browser supports, using the HTML 5 picture and source elements and/or the sizes, srcset and type attributes. For more information, see the <a href=":responsive_image">online documentation for the Responsive Image module</a>.', [
          ':responsive_image' => 'https://www.drupal.org/documentation/modules/responsive_image',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Defining responsive image styles') . '</dt>';
        $output .= '<dd>' . $this->t('By creating responsive image styles you define which options the browser has in selecting which image file to display. In most cases this means providing different image sizes based on the viewport size. On the <a href=":responsive_image_style">Responsive image styles</a> page, click <em>Add responsive image style</em> to create a new style. First choose a label, a fallback image style and a breakpoint group and click Save.', [
          ':responsive_image_style' => Url::fromRoute('entity.responsive_image_style.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Fallback image style') . '</dt>';
        $output .= '<dd>' . $this->t('The fallback image style is typically the smallest size image you expect to appear in this space. The fallback image should only appear on a site if an error occurs.') . '</dd>';
        $output .= '<dt>' . $this->t('Breakpoint groups: viewport sizing vs art direction') . '</dt>';
        $output .= '<dd>' . $this->t('The breakpoint group typically only needs a single breakpoint with an empty media query in order to do <em>viewport sizing.</em> Multiple breakpoints are used for changing the crop or aspect ratio of images at different viewport sizes, which is often referred to as <em>art direction.</em> A new breakpoint group should be created for each aspect ratio to avoid content shift. Once you select a breakpoint group, you can choose which breakpoints to use for the responsive image style. By default, the option <em>do not use this breakpoint</em> is selected for each breakpoint. See the <a href=":breakpoint_help">help page of the Breakpoint module</a> for more information.', [
          ':breakpoint_help' => Url::fromRoute('help.page', [
            'name' => 'breakpoint',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Breakpoint settings: sizes vs image styles') . '</dt>';
        $output .= '<dd>' . $this->t('While you have the option to provide only one image style per breakpoint, the sizes attribute allows you to provide more options to browsers as to which image file it can display. If using sizes field and art direction, all selected image styles should use the same aspect ratio to avoid content shifting. Breakpoints are defined in the configuration files of the theme.') . '</dd>';
        $output .= '<dt>' . $this->t('Sizes field') . '</dt>';
        $output .= '<dd>' . $this->t('The sizes attribute paired with the srcset attribute provides information on how much space these images take up within the viewport at different browser breakpoints, but the aspect ratios should remain the same across those breakpoints. Once the sizes option is selected, you can let the browser know the size of this image in relation to the site layout, using the <em>Sizes</em> field. For a hero image that always fills the entire screen, you could simply enter 100vw, which means 100% of the viewport width. For an image that fills 90% of the screen for small viewports, but only fills 40% of the screen when the viewport is larger than 40em (typically 640px), you could enter "(min-width: 40em) 40vw, 90vw" in the Sizes field. The last item in the comma-separated list is the smallest viewport size: other items in the comma-separated list should have a media condition paired with an image width. <em>Media conditions</em> are similar to a media query, often a min-width paired with a viewport width using em or px units: e.g. (min-width: 640px) or (min-width: 40em). This is paired with the <em>image width</em> at that viewport size using px, em or vw units. The vw unit is viewport width and is used instead of a percentage because the percentage always refers to the width of the entire viewport.') . '</dd>';
        $output .= '<dt>' . $this->t('Image styles for sizes') . '</dt>';
        $output .= '<dd>' . $this->t('Below the Sizes field you can choose multiple image styles so the browser can choose the best image file size to fill the space defined in the Sizes field. Typically you will want to use image styles that resize your image to have options that range from the smallest px width possible for the space the image will appear in to the largest px width possible, with a variety of widths in between. You may want to provide image styles with widths that are 1.5x to 2x the space available in the layout to account for high resolution screens. Image styles can be defined on the <a href=":image_styles">Image styles page</a> that is provided by the <a href=":image_help">Image module</a>.', [
          ':image_styles' => Url::fromRoute('entity.image_style.collection')->toString(),
          ':image_help' => Url::fromRoute('help.page', [
            'name' => 'image',
          ])->toString(),
        ]) . '</dd>';
        $output .= '</dl></dd>';
        $output .= '<dt>' . $this->t('Using responsive image styles in Image fields') . '</dt>';
        $output .= '<dd>' . $this->t('After defining responsive image styles, you can use them in the display settings for your Image fields, so that the site displays responsive images using the HTML5 picture tag. Open the Manage display page for the entity type (content type, taxonomy vocabulary, etc.) that the Image field is attached to. Choose the format <em>Responsive image</em>, click the Edit icon, and select one of the responsive image styles that you have created. For general information on how to manage fields and their display see the <a href=":field_ui">Field UI module help page</a>. For background information about entities and fields see the <a href=":field_help">Field module help page</a>.', [
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':field_help' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.responsive_image_style.collection':
        return '<p>' . $this->t('A responsive image style associates an image style with each breakpoint defined by your theme.') . '</p>';
    }
    return NULL;
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
      ],
      'responsive_image_formatter' => [
        'variables' => [
          'item' => NULL,
          'item_attributes' => NULL,
          'url' => NULL,
          'responsive_image_style_id' => NULL,
        ],
      ],
    ];
  }

}
