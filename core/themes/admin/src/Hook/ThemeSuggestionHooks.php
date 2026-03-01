<?php

declare(strict_types=1);

namespace Drupal\admin\Hook;

use Drupal\admin\Helper;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides theme suggestion hook implementations.
 */
class ThemeSuggestionHooks {

  /**
   * Constructs the theme related hooks.
   */
  public function __construct(
    protected readonly RequestStack $requestStack,
    protected readonly PathMatcherInterface $pathMatcher,
    protected readonly RouteMatchInterface $routeMatch,
    protected ClassResolverInterface $classResolver,
  ) {}

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for details.
   */
  #[Hook('theme_suggestions_details_alter')]
  public function details(array &$suggestions, array $variables): void {
    if (!empty($variables['element']['#vertical_tab_item'])) {
      $suggestions[] = 'details__vertical_tabs';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for form.
   */
  #[Hook('theme_suggestions_form_alter')]
  public function form(array &$suggestions, array $variables): void {
    $suggestions[] = 'form__' . str_replace('-', '_', $variables['element']['#id']);
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for form_element.
   */
  #[Hook('theme_suggestions_form_element_alter')]
  public function formElement(array &$suggestions, array $variables): void {
    if (!empty($variables['element']['#type'])) {
      $suggestions[] = 'form_element__' . $variables['element']['#type'];
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for maintenance_page.
   */
  #[Hook('theme_suggestions_maintenance_page_alter')]
  public function maintenancePage(array &$suggestions): void {
    try {
      $is_front = $this->pathMatcher->isFrontPage();
    }
    catch (\Exception) {
      // An exception could mean that the database is offline. This scenario
      // should also be rendered using the frontpage template.
      $is_front = TRUE;
    }

    if ($is_front) {
      // Add theme suggestion for maintenance page rendered as front page. This
      // allows separating different applications such as update.php from the
      // actual maintenance page.
      $suggestions[] = 'maintenance_page__front';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for page.
   */
  #[Hook('theme_suggestions_page_alter')]
  public function page(array &$suggestions): void {
    $path = $this->requestStack->getCurrentRequest()?->getPathInfo();

    if ($path !== '/') {
      $path = trim($path, '/');
      $arg = str_replace(["/", '-'], ['_', '_'], $path);
      $suggestions[] = 'page__' . $arg;
    }

    // The node page template is required to use the node content form.
    if (!in_array('page__node', $suggestions, TRUE) && Helper::isContentForm()) {
      $suggestions[] = 'page__node';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for table.
   */
  #[Hook('theme_suggestions_table_alter')]
  public function table(array &$suggestions, array $variables): void {
    if (empty($variables['attributes']['class'])) {
      return;
    }

    if (is_array($variables['attributes']['class']) && in_array('field-multiple-table', $variables['attributes']['class'], TRUE)) {
      $suggestions[] = 'table__simple';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for top_bar.
   */
  #[Hook('theme_suggestions_top_bar_alter')]
  public function topBar(array &$suggestions): void {
    $suggestions[] = 'top_bar__gin';
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for views_view_field.
   */
  #[Hook('theme_suggestions_views_view_field_alter')]
  public function viewsViewField(array &$suggestions, array $variables): void {
    $field_name = $variables['field']->field;
    if ($field_name === 'status') {
      $suggestions[] = 'views_view_field__' . $field_name;
    }
  }

}
