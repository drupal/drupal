<?php

namespace Drupal\umami\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\views\Form\ViewsForm;
use Drupal\views\Element\View;
use Drupal\views\Views;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search\SearchPageInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for umami.
 */
class UmamiHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_preprocess_HOOK() for html.
   *
   * Adds body classes if certain regions have content.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    // Add a sidebar class if the sidebar has content in it.
    if (!empty($variables['page']['sidebar'])) {
      $variables['attributes']['class'][] = 'two-columns';
      $variables['#attached']['library'][] = 'umami/two-columns';
    }
    else {
      $variables['attributes']['class'][] = 'one-column';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for field.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(&$variables, $hook): void {
    $element = $variables['element'];
    // Add class to label and items fields to be styled using the meta styles.
    if (isset($element['#field_name'])) {
      if ($element['#field_name'] == 'field_recipe_category' || $element['#field_name'] == 'field_tags' || $element['#field_name'] == 'field_difficulty') {
        $variables['attributes']['class'][] = 'label-items';
        if ($element['#view_mode'] == 'card' && $element['#field_name'] == 'field_difficulty') {
          $variables['attributes']['class'][] = 'umami-card__label-items';
        }
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for field templates.
   *
   * Adds a view mode based suggestion so that a field can be themed for one
   * view mode only, as the recipe badges are.
   */
  #[Hook('theme_suggestions_field_alter')]
  public function themeSuggestionsFieldAlter(array &$suggestions, array $variables): void {
    $element = $variables['element'];
    if (isset($element['#view_mode'])) {
      $suggestions[] = 'field__' . $element['#entity_type'] . '__' . $element['#field_name'] . '__' . $element['#bundle'] . '__' . $element['#view_mode'];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for node--recipe--full.
   *
   * The recipe design closes with a listing of recipes from the same category.
   * It is rendered by the template rather than placed in a region so that it
   * stays inside the recipe itself.
   */
  #[Hook('preprocess_node__recipe__full')]
  public function preprocessNodeRecipeFull(&$variables): void {
    $view = Views::getView('related_recipes');
    if (!$view) {
      return;
    }
    $build = $view->buildRenderable('related_recipes_block');
    // Render the view now, so that a recipe with no related recipes shows
    // neither the listing nor its heading. Keep the cacheability that explains
    // why the listing is empty.
    // @see \Drupal\views\Plugin\Block\ViewsBlock::build()
    $build = View::preRenderViewElement($build);
    if (empty($build['view_build'])) {
      // Nothing to show. Keep the cacheability that says why, so that the
      // recipe is rebuilt once a related recipe exists.
      CacheableMetadata::createFromRenderArray($build)
        ->merge(CacheableMetadata::createFromRenderArray($variables))
        ->applyTo($variables);
      return;
    }
    $variables['related_recipes'] = $build;
    $variables['related_recipes_title'] = $view->getTitle();
  }

  /**
   * Implements hook_preprocess_HOOK() for block.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    $variables['title_attributes']['class'][] = 'block__title';
    // Add a class indicating the content block bundle.
    if (isset($variables['elements']['content']['#block_content'])) {
      $variables['attributes']['class'][] = Html::getClass('block-type-' . $variables['elements']['content']['#block_content']->bundle());
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for block templates.
   */
  #[Hook('theme_suggestions_block_alter')]
  public function themeSuggestionsBlockAlter(array &$suggestions, array $variables): void {
    // Block suggestions for content block bundles.
    if (isset($variables['elements']['content']['#block_content'])) {
      array_splice($suggestions, 1, 0, 'block__bundle__' . $variables['elements']['content']['#block_content']->bundle());
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block--bundle--banner-block.
   */
  #[Hook('preprocess_block__bundle__banner_block')]
  public function preprocessBlockBundleBannerBlock(&$variables): void {
    if (isset($variables['content']['field_content_link'])) {
      foreach (Element::children($variables['content']['field_content_link']) as $key) {
        $variables['content']['field_content_link'][$key]['#attributes']['class'][] = 'button';
        $variables['content']['field_content_link'][$key]['#attributes']['class'][] = 'button--primary';
        $variables['content']['field_content_link'][$key]['#attributes']['class'][] = 'banner__button';
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block bundle footer promo.
   */
  #[Hook('preprocess_block__bundle__footer_promo_block')]
  public function preprocessBlockBundleFooterPromoBlock(&$variables): void {
    if (isset($variables['content']['field_content_link'])) {
      foreach (Element::children($variables['content']['field_content_link']) as $key) {
        $variables['content']['field_content_link'][$key]['#attributes']['class'][] = 'footer-block__link';
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for breadcrumb.
   */
  #[Hook('preprocess_breadcrumb')]
  public function preprocessBreadcrumb(&$variables): void {
    // We are creating a variable for the Current Page Title, to allow us to
    // print it after the breadcrumbs loop has run.
    $route_match = \Drupal::routeMatch();
    // Search page titles aren't resolved using the title_resolver service - it
    // will always return 'Search' instead of 'Search for [term]', which would
    // give us a breadcrumb of Home >> Search >> Search.
    // @todo Revisit after https://www.drupal.org/project/drupal/issues/2359901
    // @todo Revisit after https://www.drupal.org/project/drupal/issues/2403359
    $entity = $route_match->getParameter('entity');
    if ($entity instanceof SearchPageInterface) {
      $variables['current_page_title'] = $entity->getPlugin()->suggestedTitle();
    }
    else {
      $variables['current_page_title'] = \Drupal::service('title_resolver')->getTitle(\Drupal::request(), $route_match->getRouteObject());
    }
    // Since we are printing the 'Current Page Title', add the URL cache
    // context. If we don't, then we might end up with something like
    // "Home > Articles" on the Recipes page, which should read
    // "Home > Recipes".
    $variables['#cache']['contexts'][] = 'url';
  }

  /**
   * Implements hook_preprocess_HOOK() for menu-local-task.
   */
  #[Hook('preprocess_menu_local_task')]
  public function preprocessMenuLocalTask(&$variables): void {
    $variables['link']['#options']['attributes']['class'][] = 'tabs__link';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for search_block_form.
   */
  #[Hook('form_search_block_form_alter')]
  public function formSearchBlockFormAlter(&$form, FormStateInterface $form_state): void {
    $form['keys']['#attributes']['placeholder'] = $this->t('Search by keyword, ingredient, dish');
  }

  /**
   * Implements hook_preprocess_HOOK() for links--media-library-menu.
   *
   * This targets the menu of available media types in the media library's modal
   * dialog.
   *
   * @todo Do this in the relevant template once
   *   https://www.drupal.org/project/drupal/issues/3088856 is resolved.
   */
  #[Hook('preprocess_links__media_library_menu')]
  public function preprocessLinksMediaLibraryMenu(array &$variables): void {
    foreach ($variables['links'] as &$link) {
      $link['link']['#options']['attributes']['class'][] = 'media-library-menu__link';
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * @todo revisit in https://drupal.org/node/3110132
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ViewsForm && str_starts_with($form_object->getBaseFormId(), 'views_form_media_library')) {
      $form['#attributes']['class'][] = 'media-library-views-form';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for image-widget.
   *
   * @todo Revisit in https://drupal.org/node/3117430
   */
  #[Hook('preprocess_image_widget')]
  public function preprocessImageWidget(&$variables): void {
    if (!empty($variables['element']['fids']['#value'])) {
      $file = reset($variables['element']['#files']);
      $variables['data']["file_{$file->id()}"]['filename']['#suffix'] = ' <span class="file-size">(' . ByteSizeMarkup::create($file->getSize()) . ')</span> ';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for links.
   *
   * This makes it so array keys of #links items are added as a class. This
   * functionality was removed in Drupal 8.1, but still necessary in some
   * instances.
   *
   * @todo remove in https://drupal.org/node/3120962
   */
  #[Hook('preprocess_links')]
  public function preprocessLinks(&$variables): void {
    if (!empty($variables['links'])) {
      foreach ($variables['links'] as $key => $value) {
        if (!is_numeric($key)) {
          $class = Html::getClass($key);
          $variables['links'][$key]['attributes']->addClass($class);
        }
      }
    }
  }

}
