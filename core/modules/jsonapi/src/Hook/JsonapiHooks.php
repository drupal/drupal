<?php

namespace Drupal\jsonapi\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\Routing\Routes;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for jsonapi.
 */
class JsonapiHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.jsonapi':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The JSON:API module is a fully compliant implementation of the <a href=":spec">JSON:API Specification</a>. By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus on what matters: your application. Clients built around JSON:API are able to take advantage of features like efficient response caching, which can sometimes eliminate network requests entirely. For more information, see the <a href=":docs">online documentation for the JSON:API module</a>.', [
          ':spec' => 'https://jsonapi.org',
          ':docs' => 'https://www.drupal.org/docs/8/modules/json-api',
        ]) . '</p>';
        $output .= '<dl>';
        $output .= '<dt>' . t('General') . '</dt>';
        $output .= '<dd>' . t('JSON:API is a particular implementation of REST that provides conventions for resource relationships, collections, filters, pagination, and sorting. These conventions help developers build clients faster and encourages reuse of code.') . '</dd>';
        $output .= '<dd>' . t('The <a href=":jsonapi-docs">JSON:API</a> and <a href=":rest-docs">RESTful Web Services</a> modules serve similar purposes. <a href=":comparison">Read the comparison of the RESTFul Web Services and JSON:API modules</a> to determine the best choice for your site.', [
          ':jsonapi-docs' => 'https://www.drupal.org/docs/8/modules/json-api',
          ':rest-docs' => 'https://www.drupal.org/docs/8/core/modules/rest',
          ':comparison' => 'https://www.drupal.org/docs/8/modules/jsonapi/jsonapi-vs-cores-rest-module',
        ]) . '</dd>';
        $output .= '<dd>' . t('Some multilingual features currently do not work well with JSON:API. See the <a href=":jsonapi-docs">JSON:API multilingual support documentation</a> for more information on the current status of multilingual support.', [':jsonapi-docs' => 'https://www.drupal.org/docs/8/modules/jsonapi/translations']) . '</dd>';
        $output .= '<dd>' . t('Revision support is currently read-only and only for the "Content" and "Media" entity types in JSON:API. See the <a href=":jsonapi-docs">JSON:API revision support documentation</a> for more information on the current status of revision support.', [':jsonapi-docs' => 'https://www.drupal.org/docs/8/modules/jsonapi/revisions']) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules) {
    $potential_conflicts = ['content_translation', 'config_translation', 'language'];
    if (!empty(array_intersect($modules, $potential_conflicts))) {
      \Drupal::messenger()->addWarning(t('Some multilingual features currently do not work well with JSON:API. See the <a href=":jsonapi-docs">JSON:API multilingual support documentation</a> for more information on the current status of multilingual support.', [':jsonapi-docs' => 'https://www.drupal.org/docs/8/modules/jsonapi/translations']));
    }
  }

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate() {
    Routes::rebuild();
  }

  /**
   * Implements hook_entity_bundle_delete().
   */
  #[Hook('entity_bundle_delete')]
  public function entityBundleDelete() {
    Routes::rebuild();
  }

  /**
   * Implements hook_entity_create().
   */
  #[Hook('entity_create')]
  public function entityCreate(EntityInterface $entity) {
    if (in_array($entity->getEntityTypeId(), ['field_storage_config', 'field_config'])) {
      // @todo Only do this when relationship fields are updated, not just any field.
      Routes::rebuild();
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    if (in_array($entity->getEntityTypeId(), ['field_storage_config', 'field_config'])) {
      // @todo Only do this when relationship fields are updated, not just any field.
      Routes::rebuild();
    }
  }

  /**
   * Implements hook_jsonapi_entity_filter_access().
   */
  #[Hook('jsonapi_entity_filter_access')]
  public function jsonapiEntityFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // All core entity types and most or all contrib entity types allow users
    // with the entity type's administrative permission to view all of the
    // entities, so enable similarly permissive filtering to those users as well.
    // A contrib module may override this decision by returning
    // AccessResult::forbidden() from its implementation of this hook.
    if ($admin_permission = $entity_type->getAdminPermission()) {
      return [
        JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, $admin_permission),
      ];
    }
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'block_content'.
   */
  #[Hook('jsonapi_block_content_filter_access')]
  public function jsonapiBlockContentFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
    // \Drupal\jsonapi\Access\TemporaryQueryGuard adds the condition for
    // (isReusable()), so this does not have to.
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'access block library'),
      JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowed(),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'comment'.
   */
  #[Hook('jsonapi_comment_filter_access')]
  public function jsonapiCommentFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\comment\CommentAccessControlHandler::checkAccess()
    // \Drupal\jsonapi\Access\TemporaryQueryGuard adds the condition for
    // (access to the commented entity), so this does not have to.
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'administer comments'),
      JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowedIfHasPermission($account, 'access comments'),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'entity_test'.
   */
  #[Hook('jsonapi_entity_test_filter_access')]
  public function jsonapiEntityTestFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\entity_test\EntityTestAccessControlHandler::checkAccess()
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'view test entity'),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'file'.
   */
  #[Hook('jsonapi_file_filter_access')]
  public function jsonapiFileFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\file\FileAccessControlHandler::checkAccess()
    // \Drupal\jsonapi\Access\TemporaryQueryGuard adds the condition for
    // (public OR owner), so this does not have to.
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'access content'),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'media'.
   */
  #[Hook('jsonapi_media_filter_access')]
  public function jsonapiMediaFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\media\MediaAccessControlHandler::checkAccess()
    return [
      JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowedIfHasPermission($account, 'view media'),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'node'.
   */
  #[Hook('jsonapi_node_filter_access')]
  public function jsonapiNodeFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\node\NodeAccessControlHandler::access()
    if ($account->hasPermission('bypass node access')) {
      return [
        JSONAPI_FILTER_AMONG_ALL => AccessResult::allowed()->cachePerPermissions(),
      ];
    }
    if (!$account->hasPermission('access content')) {
      $forbidden = AccessResult::forbidden("The 'access content' permission is required.")->cachePerPermissions();
      return [
        JSONAPI_FILTER_AMONG_ALL => $forbidden,
        JSONAPI_FILTER_AMONG_OWN => $forbidden,
        JSONAPI_FILTER_AMONG_PUBLISHED => $forbidden,
            // For legacy reasons, the Node entity type has a "status" key, so forbid
            // this subset as well, even though it has no semantic meaning.
        JSONAPI_FILTER_AMONG_ENABLED => $forbidden,
      ];
    }
    return [
          // @see \Drupal\node\NodeAccessControlHandler::checkAccess()
      JSONAPI_FILTER_AMONG_OWN => AccessResult::allowedIfHasPermission($account, 'view own unpublished content'),
          // @see \Drupal\node\NodeGrantDatabaseStorage::access()
          // Note that:
          // - This is just for the default grant. Other node access conditions are
          //   added via the 'node_access' query tag.
          // - Permissions were checked earlier in this function, so we must vary the
          //   cache by them.
      JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowed()->cachePerPermissions(),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'shortcut'.
   */
  #[Hook('jsonapi_shortcut_filter_access')]
  public function jsonapiShortcutFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\shortcut\ShortcutAccessControlHandler::checkAccess()
    // \Drupal\jsonapi\Access\TemporaryQueryGuard adds the condition for
    // (shortcut_set = $shortcut_set_storage->getDisplayedToUser($current_user)),
    // so this does not have to.
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'administer shortcuts')->orIf(AccessResult::allowedIfHasPermissions($account, [
        'access shortcuts',
        'customize shortcut links',
      ])),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'taxonomy_term'.
   */
  #[Hook('jsonapi_taxonomy_term_filter_access')]
  public function jsonapiTaxonomyTermFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\taxonomy\TermAccessControlHandler::checkAccess()
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'administer taxonomy'),
      JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowedIfHasPermission($account, 'access content'),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'user'.
   */
  #[Hook('jsonapi_user_filter_access')]
  public function jsonapiUserFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\user\UserAccessControlHandler::checkAccess()
    // \Drupal\jsonapi\Access\TemporaryQueryGuard adds the condition for
    // (!isAnonymous()), so this does not have to.
    return [
      JSONAPI_FILTER_AMONG_OWN => AccessResult::allowed(),
      JSONAPI_FILTER_AMONG_ENABLED => AccessResult::allowedIfHasPermission($account, 'access user profiles'),
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access() for 'workspace'.
   */
  #[Hook('jsonapi_workspace_filter_access')]
  public function jsonapiWorkspaceFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    // @see \Drupal\workspaces\WorkspaceAccessControlHandler::checkAccess()
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'view any workspace'),
      JSONAPI_FILTER_AMONG_OWN => AccessResult::allowedIfHasPermission($account, 'view own workspace'),
    ];
  }

}
