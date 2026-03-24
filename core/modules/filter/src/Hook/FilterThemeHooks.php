<?php

namespace Drupal\filter\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;

/**
 * Theme hooks for filter.
 */
class FilterThemeHooks {

  public function __construct(protected AccountInterface $currentUser) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'filter_tips' => [
        'variables' => [
          'tips' => NULL,
          'long' => FALSE,
        ],
        'initial preprocess' => static::class . ':preprocessFilterTips',
      ],
      'text_format_wrapper' => [
        'variables' => [
          'children' => NULL,
          'description' => NULL,
          'attributes' => [],
        ],
        'initial preprocess' => static::class . ':preprocessTextFormatWrapper',
      ],
      'filter_guidelines' => [
        'variables' => [
          'format' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessFilterGuidelines',
      ],
      'filter_caption' => [
        'variables' => [
          'node' => NULL,
          'tag' => NULL,
          'caption' => NULL,
          'classes' => NULL,
        ],
      ],
    ];
  }

  /**
   * Prepares variables for text format guideline templates.
   *
   * Default template: filter-guidelines.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - format: An object representing a text format.
   */
  public function preprocessFilterGuidelines(array &$variables): void {
    $format = $variables['format'];
    $variables['tips'] = [
      '#theme' => 'filter_tips',
      '#tips' => $this->getFilterTips($format->id()),
    ];

    // Add format id for filter.js.
    $variables['attributes']['data-drupal-format-id'] = $format->id();
  }

  /**
   * Prepares variables for text format wrapper templates.
   *
   * Default template: text-format-wrapper.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - attributes: An associative array containing properties of the element.
   */
  public function preprocessTextFormatWrapper(array &$variables): void {
    $variables['aria_description'] = FALSE;
    // Add element class and id for screen readers.
    if (isset($variables['attributes']['aria-describedby'])) {
      $variables['aria_description'] = TRUE;
      $variables['attributes']['id'] = $variables['attributes']['aria-describedby'];
      // Remove aria-describedby attribute as it shouldn't be visible here.
      unset($variables['attributes']['aria-describedby']);
    }
  }

  /**
   * Retrieves the filter tips.
   *
   * @param string|null $formatId
   *   (optional) The ID of the text format for which to retrieve tips. If
   *   omitted, will return tips for all formats accessible to the current user.
   *
   * @return array
   *   An associative array of filtering tips, keyed by the filter name. Each
   *   filtering tip is an associative array with elements:
   *   - tip: Tip text.
   *   - id: Filter ID.
   */
  protected function getFilterTips(?string $formatId = NULL): array {
    $formats = filter_formats($this->currentUser);

    $tips = [];

    // If only listing one format, extract it from the $formats array.
    if ($formatId !== NULL) {
      $formats = [$formats[$formatId]];
    }

    foreach ($formats as $format) {
      foreach ($format->filters() as $name => $filter) {
        if ($filter->status) {
          $tip = $filter->tips();
          if (isset($tip)) {
            $tips[$format->label()][$name] = [
              'tip' => ['#markup' => $tip],
              'id' => $name,
            ];
          }
        }
      }
    }

    return $tips;
  }

  /**
   * Prepares variables for filter tips templates.
   *
   * Default template: filter-tips.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - tips: An array containing descriptions and a CSS ID in the form of
   *     'module-name/filter-id' (only used when $long is TRUE) for each
   *     filter in one or more text formats. Example:
   *     @code
   *       [
   *         'Full HTML' => [
   *           0 => [
   *             'tip' => 'Web page addresses and email addresses turn into links automatically.',
   *             'id' => 'filter/2',
   *           ],
   *         ],
   *       ];
   *     @endcode
   *   - long: (optional) Whether the passed-in filter tips contain extended
   *     explanations, i.e. intended to be output on the path 'filter/tips'
   *     (TRUE), or are in a short format, i.e. suitable to be displayed below a
   *     form element. Defaults to FALSE.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There is
   *   no replacement.
   *
   * @see https://www.drupal.org/node/3567879
   */
  public function preprocessFilterTips(array &$variables): void {
    $tips = $variables['tips'];

    foreach ($variables['tips'] as $name => $tip_list) {
      foreach ($tip_list as $tip_key => $tip) {
        $tip_list[$tip_key]['attributes'] = new Attribute();
      }

      $variables['tips'][$name] = [
        'attributes' => new Attribute(),
        'name' => $name,
        'list' => $tip_list,
      ];
    }

    $variables['multiple'] = count($tips) > 1;
  }

}
