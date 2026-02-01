<?php

declare(strict_types=1);

namespace Drupal\contextual;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Helper methods to handle contextual links <-> ID conversion.
 */
readonly class ContextualLinksSerializer {

  public function __construct(
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Serializes #contextual_links property value array to a string.
   *
   * Examples:
   *  - node:node=1:langcode=en
   *  -
   * views_ui_edit:view=frontpage:location=page&view_name=frontpage&view_display_id=page_1&langcode=en
   *  - menu:menu=tools:langcode=en|block:block=olivero.tools:langcode=en
   *
   * So, expressed in a pattern:
   *  <group>:<route parameters>:<metadata>
   *
   * The route parameters and options are encoded as query strings.
   *
   * @param array $contextualLinks
   *   The $element['#contextual_links'] value for some render element.
   *
   * @return string
   *   A serialized representation of a #contextual_links property value array
   *   for use in a data-* attribute.
   */
  public function linksToId(array $contextualLinks): string {
    $ids = [];

    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    foreach ($contextualLinks as $group => $args) {
      $routeParameters = UrlHelper::buildQuery($args['route_parameters']);
      $args += ['metadata' => []];
      // Add the current URL language to metadata so a different ID will be
      // computed when URLs vary by language. This allows to store different
      // language-aware contextual links on the client side.
      $args['metadata'] += ['langcode' => $langcode];
      $metadata = UrlHelper::buildQuery($args['metadata']);
      $ids[] = "$group:$routeParameters:$metadata";
    }

    return implode('|', $ids);
  }

  /**
   * Unserializes the result of ::linksToId().
   *
   * Note that $id is user input. Before calling this method the ID should be
   * checked against the token stored in the 'data-contextual-token' attribute
   * which is passed via the 'tokens' request parameter to
   * \Drupal\contextual\ContextualController::render().
   *
   * @param string $id
   *   A serialized representation of a #contextual_links property value array.
   *
   * @return array
   *   The value for a #contextual_links property.
   *
   * @see self::linksToId()
   * @see \Drupal\contextual\ContextualController::render()
   */
  public function idToLinks(string $id): array {
    $contextualLinks = [];

    foreach (explode('|', $id) as $context) {
      [$group, $routeParametersRaw, $metadataRaw] = explode(':', $context);
      parse_str($routeParametersRaw, $routeParameters);
      $metadata = [];
      parse_str($metadataRaw, $metadata);
      $contextualLinks[$group] = [
        'route_parameters' => $routeParameters,
        'metadata' => $metadata,
      ];
    }

    return $contextualLinks;
  }

}
