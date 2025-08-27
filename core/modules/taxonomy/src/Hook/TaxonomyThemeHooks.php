<?php

namespace Drupal\taxonomy\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hook implementations for taxonomy.
 */
class TaxonomyThemeHooks {

  public function __construct(
    protected readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'taxonomy_term' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessTaxonomyTerm',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_taxonomy_term')]
  public function themeSuggestionsTaxonomyTerm(array $variables): array {
    $suggestions = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $variables['elements']['#taxonomy_term'];
    $suggestions[] = 'taxonomy_term__' . $term->bundle();
    $suggestions[] = 'taxonomy_term__' . $term->id();
    return $suggestions;
  }

  /**
   * Prepares variables for taxonomy term templates.
   *
   * Default template: taxonomy-term.html.twig.
   *
   * By default this function performs special preprocessing to move the name
   * base field out of the elements array into a separate variable. This
   * preprocessing is skipped if:
   * - a module makes the field's display configurable via the field UI by means
   *   of BaseFieldDefinition::setDisplayConfigurable()
   * - AND the additional entity type property
   *   'enable_base_field_custom_preprocess_skipping' has been set using
   *   hook_entity_type_build().
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing the taxonomy term and any
   *     fields attached to the term. Properties used:
   *     - #taxonomy_term: A \Drupal\taxonomy\TermInterface object.
   *     - #view_mode: The current view mode for this taxonomy term, e.g.
   *       'full' or 'teaser'.
   *   - attributes: HTML attributes for the containing element.
   */
  public function preprocessTaxonomyTerm(array &$variables): void {
    $variables['view_mode'] = $variables['elements']['#view_mode'];
    $variables['term'] = $variables['elements']['#taxonomy_term'];
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $variables['term'];

    $variables['url'] = !$term->isNew() ? $term->toUrl()->toString() : NULL;

    // Make name field available separately.  Skip this custom preprocessing if
    // the field display is configurable and skipping has been enabled.
    // @todo https://www.drupal.org/project/drupal/issues/3015623
    //   Eventually delete this code and matching template lines. Using
    //   $variables['content'] is more flexible and consistent.
    $skip_custom_preprocessing = $term->getEntityType()->get('enable_base_field_custom_preprocess_skipping');
    if (!$skip_custom_preprocessing || !$term->getFieldDefinition('name')->isDisplayConfigurable('view')) {
      // We use name here because that is what appears in the UI.
      $variables['name'] = $variables['elements']['name'];
      unset($variables['elements']['name']);
    }

    // The page variable is deprecated.
    $variables['deprecations']['page'] = "'page' is deprecated in drupal:11.3.0 and is removed in drupal:13.0.0. Use 'view_mode' instead. See https://www.drupal.org/node/3542527";

    $variables['page'] = FALSE;
    if ($variables['view_mode'] == 'full' && ($this->routeMatch->getRouteName() == 'entity.taxonomy_term.canonical' && $this->routeMatch->getRawParameter('taxonomy_term') == $term->id())) {
      $variables['page'] = TRUE;
    }

    // Helpful $content variable for templates.
    $variables['content'] = [];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
  }

}
