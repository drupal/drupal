<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\editor\EditorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for entity link suggestions autocomplete route.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 *
 * @internal
 */
class EntityLinkSuggestionsController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The default limit for matches.
   */
  const DEFAULT_LIMIT = 100;

  /**
   * Constructs a EntityLinkSuggestionsController.
   */
  public function __construct(
    protected readonly SelectionPluginManagerInterface $selectionPluginManager,
    protected readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected readonly EntityRepositoryInterface $entityRepository,
    protected readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Checks access based on entity_links filter status on the text format.
   *
   * Note that access to the filter format is not checked here because the route
   * is configured to check entity access to the filter format.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function formatUsesEntityLinksFilter(EditorInterface $editor): AccessResultInterface {
    $filters = $editor->getFilterFormat()->filters();
    return AccessResult::allowedIf($filters->has('entity_links') && $filters->get('entity_links')->status)
      ->addCacheableDependency($editor);
  }

  /**
   * Generates entity link suggestions for use by an autocomplete.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor whose drupalEntityLinkSuggestions configuration to use.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function suggestions(Request $request, EditorInterface $editor): JsonResponse {
    $input = mb_strtolower($request->query->get('q'));
    $host_entity_type_id = $request->query->get('hostEntityTypeId');
    $host_entity_langcode = $request->query->get('hostEntityLangcode');
    $suggestions = [];

    if ($input) {
      $allowed_bundles = [];
      $all_bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
      foreach ($all_bundle_info as $entity_type => $bundles) {
        foreach ($bundles as $key => $bundle) {
          if (!empty($bundle['ckeditor5_link_suggestions'])) {
            $allowed_bundles[$entity_type][$key] = $key;
          }
        }
      }
      if (in_array($host_entity_type_id, array_keys($allowed_bundles), TRUE)) {
        $suggestions = $this->getSuggestions(
          $host_entity_type_id,
          $allowed_bundles[$host_entity_type_id],
          $input,
          $host_entity_langcode
        );
      }

      // Second, find suggestions for all other entity types, in the specified
      // order.
      $allowed_entity_type_ids = array_keys($allowed_bundles);
      foreach ($allowed_bundles as $entity_type_id => $bundles) {
        if ($host_entity_type_id === $entity_type_id) {
          continue;
        }
        if (in_array($entity_type_id, $allowed_entity_type_ids, TRUE)) {
          $suggestions = array_merge($suggestions, $this->getSuggestions(
            $entity_type_id,
            $bundles,
            $input,
            $host_entity_langcode
          ));
        }
      }

      // If no suggestions were found, add a special suggestion that has the
      // same path as the given string so users can select it and use it anyway.
      // This typically occurs when entering external links.
      if (!$suggestions) {
        $suggestions = [
          [
            'description' => $this->t('No content suggestions found. This URL will be used as is.'),
            'group' => $this->t('No results'),
            'label' => Html::escape($input),
            'href' => UrlHelper::isValid($input) ? $input : '',
          ],
        ];
      }
    }

    // Note that we intentionally:
    // - do not use \Drupal\Core\Cache\CacheableJsonResponse because caching it
    //   on the server side is wasteful, hence there is no need for cacheability
    //   metadata.
    // - mark the response as private, because the suggestions include only the
    //   ones accessible by the current user.
    return (new JsonResponse(['suggestions' => $suggestions]))
      // Do not allow any intermediary to cache the response, only the end user.
      ->setPrivate()
      // Allow the end user to cache it for up to 5 minutes.
      ->setMaxAge(300);
  }

  /**
   * Gets the suggestions.
   *
   * @param string $target_entity_type_id
   *   An entity type to get suggestions for.
   * @param null|string[] $target_bundles
   *   NULL to allow all bundles, a list of bundle names to restrict to those
   *   bundles.
   * @param string $string
   *   The string to search.
   * @param string $host_entity_langcode
   *   The langcode of the host entity.
   *
   * @return array
   *   An array of suggestion objects with populated entity data.
   *
   * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection::defaultConfiguration()
   */
  public function getSuggestions(string $target_entity_type_id, ?array $target_bundles, string $string, string $host_entity_langcode): array {
    // If the user input is a current entity URL, don't get more suggestions.
    if ($entity_id = static::findEntityIdByUrl($target_entity_type_id, $string)) {
      $entity = $this->entityTypeManager()->getStorage($target_entity_type_id)->load($entity_id);
      if ($entity?->language()->getId() === $host_entity_langcode) {
        return [$this->createSuggestion($entity)];
      }
    }

    // Do not call ::getPluginId() or ::getInstance() because this favors a
    // "link_target" variant of the default selection plugin for the given
    // entity type, if it exists.
    $selection_handler_groups = $this->selectionPluginManager->getSelectionGroups($target_entity_type_id);
    if (!array_key_exists('default', $selection_handler_groups)) {
      return [];
    }
    // Sort the selection plugins by weight and select the best match.
    uasort($selection_handler_groups['default'], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    end($selection_handler_groups['default']);
    // Select the link_target variant of the default selection plugin for the
    // entity type, if it exists. Otherwise, select the next best match.
    $link_target_selection_plugin_id = "default:{$target_entity_type_id}_link_target";
    $plugin_id = array_key_exists($link_target_selection_plugin_id, $selection_handler_groups['default'])
      ? $link_target_selection_plugin_id
      : key($selection_handler_groups['default']);
    $selection = $this->selectionPluginManager->createInstance($plugin_id, [
      'target_type' => $target_entity_type_id,
      'target_bundles' => $target_bundles,
    ]);

    $entities_by_bundle = $selection->getReferenceableEntities($string, 'CONTAINS', static::DEFAULT_LIMIT);
    // DefaultSelection::getReferenceableEntities() loads entities and even
    // their translation but then only keeps bundle, entity ID and label. Reload
    // them to generate rich results. Note that performance overhead of this is
    // minimal because all this data is statically cached already anyway.
    $entity_ids = array_reduce($entities_by_bundle, function ($flattened, $bundle_entities) {
      return array_merge($flattened, array_keys($bundle_entities));
    }, []);
    $entities = $this->entityTypeManager()->getStorage($target_entity_type_id)->loadMultiple($entity_ids);

    $suggestions = [];
    foreach ($entities as $entity) {
      $entity_translation = $entity->getEntityType()->isTranslatable() && $entity->hasTranslation($host_entity_langcode) ? $entity->getTranslation($host_entity_langcode) : $entity;
      if ($entity_translation->language()->getId() === $host_entity_langcode) {
        $suggestions[] = $this->createSuggestion($entity_translation);
      }
    }

    return $suggestions;
  }

  /**
   * Creates a suggestion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The matched entity.
   *
   * @return array
   *   A suggestion object with populated entity data.
   */
  protected function createSuggestion(EntityInterface $entity): array {
    return [
      'description' => $this->computeDescription($entity) ?? '',
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_uuid' => $entity->uuid(),
      'group' => $this->computeGroup($entity),
      'label' => $entity->label(),
      // Use the canonical URI as a valid fallback for the href. The
      // text_format filter will transform this to the final URL (e.g., alias).
      'path' => $entity->toUrl('canonical')->toString(),
    ];
  }

  /**
   * Computes a suggestion description.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The suggested entity for which to compute a description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A suggestion description.
   */
  protected function computeDescription(EntityInterface $entity): ?TranslatableMarkup {
    $entity_type = $entity->getEntityType();
    $owner = $entity_type->hasKey('owner') && $entity->getOwner()
      ? $entity->getOwner()->getDisplayName()
      : NULL;
    $creation_datetime = method_exists($entity, 'getCreatedTime')
      ? $this->dateFormatter->format($entity->getCreatedTime(), 'medium')
      : NULL;

    $arg_owner = ['@owner' => $owner];
    $arg_creation_datetime = ['@creation-datetime' => $creation_datetime];

    if ($owner && $creation_datetime) {
      return $this->t('by @owner on @creation-datetime', $arg_owner + $arg_creation_datetime);
    }
    elseif ($owner) {
      return $this->t('by @owner', $arg_owner);
    }
    elseif ($creation_datetime) {
      return $this->t('on @creation-datetime', $arg_creation_datetime);
    }
    else {
      return NULL;
    }
  }

  /**
   * Computers a suggestion group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The suggested entity for which to compute the group.
   *
   * @return string
   *   A suggestion group.
   */
  protected function computeGroup(EntityInterface $entity): string {
    // If the entity type does not have bundles, the group is very simple.
    if ($entity->getEntityType()->getBundleEntityType() === NULL) {
      return $entity->getEntityType()->getLabel();
    }

    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity->getEntityTypeId());

    return $entity->getEntityType()->getLabel() . ' - ' . $bundles[$entity->bundle()]['label'];
  }

  /**
   * Finds entity ID from the given input.
   *
   * @param string $target_entity_type_id
   *   An entity type to get suggestions for.
   * @param string $user_input
   *   The string to url parse.
   *
   * @return string|null
   *   An entity ID parsed from the user input, otherwise NULL.
   */
  protected static function findEntityIdByUrl(string $target_entity_type_id, string $user_input): ?string {
    $expected_url_prefix = "/$target_entity_type_id/";
    if (str_starts_with($user_input, $expected_url_prefix)) {
      return substr($user_input, strlen($expected_url_prefix));
    }
    return NULL;
  }

}
