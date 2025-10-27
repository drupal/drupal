<?php

namespace Drupal\olivero\Hook;

use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Hook\Attribute\Hook;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Page preprocess hooks for olivero.
 */
class OliveroPagePreprocessHooks {

  public function __construct(
    protected RequestStack $requestStack,
    protected ThemeExtensionList $themeExtensionList,
    protected AssetQueryStringInterface $assetQueryString,
    protected ThemeSettingsProvider $themeSettingsProvider,
  ) {
  }

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   *
   * Adds body classes if certain regions have content.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    if ($this->themeSettingsProvider->getSetting('mobile_menu_all_widths') === 1) {
      $variables['attributes']['class'][] = 'is-always-mobile-nav';
    }

    // Convert custom hex to hsl so we can use the hue value.
    $brand_color_hex = $this->themeSettingsProvider->getSetting('base_primary_color') ?? '#1b9ae4';
    [$h, $s, $l] = _olivero_hex_to_hsl($brand_color_hex);

    $variables['html_attributes']->setAttribute('style', "--color--primary-hue:$h;--color--primary-saturation:$s%;--color--primary-lightness:$l");

    // So fonts can be preloaded from base theme in the event Olivero is used as
    // a subtheme.
    $variables['olivero_path'] = $this->requestStack->getCurrentRequest()->getBasePath() . '/' . $this->themeExtensionList->getPath('olivero');

    $query_string = $this->assetQueryString->get();

    // Create render array with noscript tag to output non-JavaScript
    // stylesheet for primary menu.
    $variables['noscript_styles'] = [
      '#type' => 'html_tag',
      '#noscript' => TRUE,
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'stylesheet',
        'href' => $variables['olivero_path'] . '/css/components/navigation/nav-primary-no-js.css?' . $query_string,
      ],
    ];
  }

}
