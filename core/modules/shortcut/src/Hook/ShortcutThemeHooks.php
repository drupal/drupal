<?php

namespace Drupal\shortcut\Hook;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for shortcut.
 */
class ShortcutThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ThemeSettingsProvider $themeSettingsProvider,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'shortcut') {
      $variables['attributes']['role'] = 'navigation';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for page title templates.
   */
  #[Hook('preprocess_page_title')]
  public function preprocessPageTitle(&$variables): void {
    // Only display the shortcut link if the user has the ability to edit
    // shortcuts, the feature is enabled for the current theme and if the
    // page's actual content is being shown (for example, we do not want to
    // display it on "access denied" or "page not found" pages).
    if (shortcut_set_edit_access()->isAllowed() && $this->themeSettingsProvider->getSetting('third_party_settings.shortcut.module_link') && !\Drupal::request()->attributes->has('exception')) {
      $link = Url::fromRouteMatch(\Drupal::routeMatch())->getInternalPath();
      $route_match = \Drupal::routeMatch();
      // Replicate template_preprocess_html()'s processing to get the title in
      // string form, so we can set the default name for the shortcut.
      $name = $variables['title'] ?? '';
      if (is_array($name)) {
        $name = \Drupal::service('renderer')->render($name);
      }
      $query = [
        'link' => $link,
        'name' => trim(strip_tags($name)),
      ];
      $shortcut_set = \Drupal::entityTypeManager()->getStorage('shortcut_set')->getDisplayedToUser(\Drupal::currentUser());
      // Pages with the add or remove shortcut button need cache invalidation
      // when a shortcut is added, edited, or removed.
      $cacheability_metadata = CacheableMetadata::createFromRenderArray($variables);
      $cacheability_metadata->addCacheTags(\Drupal::entityTypeManager()->getDefinition('shortcut')->getListCacheTags());
      $cacheability_metadata->applyTo($variables);
      // Check if $link is already a shortcut and set $link_mode accordingly.
      $shortcuts = \Drupal::entityTypeManager()->getStorage('shortcut')->loadByProperties([
        'shortcut_set' => $shortcut_set->id(),
      ]);
      /** @var \Drupal\shortcut\ShortcutInterface $shortcut */
      foreach ($shortcuts as $shortcut) {
        if (($shortcut_url = $shortcut->getUrl()) && $shortcut_url->isRouted() && $shortcut_url->getRouteName() == $route_match->getRouteName() && $shortcut_url->getRouteParameters() == $route_match->getRawParameters()->all()) {
          $shortcut_id = $shortcut->id();
          break;
        }
      }
      $link_mode = isset($shortcut_id) ? "remove" : "add";
      if ($link_mode == "add") {
        $link_text = shortcut_set_switch_access()->isAllowed() ? $this->t('Add to %shortcut_set shortcuts', [
          '%shortcut_set' => $shortcut_set->label(),
        ]) : $this->t('Add to shortcuts');
        $route_name = 'shortcut.link_add_inline';
        $route_parameters = [
          'shortcut_set' => $shortcut_set->id(),
        ];
      }
      else {
        $query['id'] = $shortcut_id;
        $link_text = shortcut_set_switch_access()->isAllowed() ? $this->t('Remove from %shortcut_set shortcuts', [
          '%shortcut_set' => $shortcut_set->label(),
        ]) : $this->t('Remove from shortcuts');
        $route_name = 'entity.shortcut.link_delete_inline';
        $route_parameters = [
          'shortcut' => $shortcut_id,
        ];
      }
      $query += \Drupal::destination()->getAsArray();
      $variables['title_suffix']['add_or_remove_shortcut'] = [
        '#attached' => [
          'library' => [
            'shortcut/drupal.shortcut',
          ],
        ],
        '#type' => 'link',
        '#title' => new FormattableMarkup('<span class="shortcut-action__icon"></span><span class="shortcut-action__message">@text</span>', [
          '@text' => $link_text,
        ]),
        '#url' => Url::fromRoute($route_name, $route_parameters),
        '#options' => [
          'query' => $query,
        ],
        '#attributes' => [
          'class' => [
            'shortcut-action',
            'shortcut-action--' . $link_mode,
          ],
        ],
      ];
    }
  }

}
