<?php

namespace Drupal\node\Hook;

use Drupal\language\ConfigurableLanguageInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Form\NodePreviewForm;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Component\Utility\Xss;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node.
 */
class NodeHooks1 {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    // Remind site administrators about the {node_access} table being flagged
    // for rebuild. We don't need to issue the message on the confirm form, or
    // while the rebuild is being processed.
    if ($route_name != 'node.configure_rebuild_confirm' && $route_name != 'system.batch_page.html' && $route_name != 'help.page.node' && $route_name != 'help.main' && \Drupal::currentUser()->hasPermission('administer nodes') && node_access_needs_rebuild()) {
      if ($route_name == 'system.status') {
        $message = t('The content access permissions need to be rebuilt.');
      }
      else {
        $message = t('The content access permissions need to be rebuilt. <a href=":node_access_rebuild">Rebuild permissions</a>.', [
          ':node_access_rebuild' => Url::fromRoute('node.configure_rebuild_confirm')->toString(),
        ]);
      }
      \Drupal::messenger()->addError($message);
    }
    switch ($route_name) {
      case 'help.page.node':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Node module manages the creation, editing, deletion, settings, and display of the main site content. Content items managed by the Node module are typically displayed as pages on your site, and include a title, some meta-data (author, creation time, content type, etc.), and optional fields containing text or other data (fields are managed by the <a href=":field">Field module</a>). For more information, see the <a href=":node">online documentation for the Node module</a>.', [
          ':node' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/node-module',
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Creating content') . '</dt>';
        $output .= '<dd>' . t('When new content is created, the Node module records basic information about the content, including the author, date of creation, and the <a href=":content-type">Content type</a>. It also manages the <em>publishing options</em>, which define whether or not the content is published, promoted to the front page of the site, and/or sticky at the top of content lists. Default settings can be configured for each <a href=":content-type">type of content</a> on your site.', [
          ':content-type' => Url::fromRoute('entity.node_type.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Creating custom content types') . '</dt>';
        $output .= '<dd>' . t('The Node module gives users with the <em>Administer content types</em> permission the ability to <a href=":content-new">create new content types</a> in addition to the default ones already configured. Creating custom content types gives you the flexibility to add <a href=":field">fields</a> and configure default settings that suit the differing needs of various site content.', [
          ':content-new' => Url::fromRoute('node.type_add')->toString(),
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Administering content') . '</dt>';
        $output .= '<dd>' . t('The <a href=":content">Content</a> page lists your content, allowing you add new content, filter, edit or delete existing content, or perform bulk operations on existing content.', [':content' => Url::fromRoute('system.admin_content')->toString()]) . '</dd>';
        $output .= '<dt>' . t('Creating revisions') . '</dt>';
        $output .= '<dd>' . t('The Node module also enables you to create multiple versions of any content, and revert to older versions using the <em>Revision information</em> settings.') . '</dd>';
        $output .= '<dt>' . t('User permissions') . '</dt>';
        $output .= '<dd>' . t('The Node module makes a number of permissions available for each content type, which can be set by role on the <a href=":permissions">permissions page</a>.', [
          ':permissions' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'node',
          ])->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'node.type_add':
        return '<p>' . t('Individual content types can have different fields, behaviors, and permissions assigned to them.') . '</p>';

      case 'entity.entity_form_display.node.default':
      case 'entity.entity_form_display.node.form_mode':
        $type = $route_match->getParameter('node_type');
        return '<p>' . t('Content items can be edited using different form modes. Here, you can define which fields are shown and hidden when %type content is edited in each form mode, and define how the field form widgets are displayed in each form mode.', ['%type' => $type->label()]) . '</p>';

      case 'entity.entity_view_display.node.default':
      case 'entity.entity_view_display.node.view_mode':
        $type = $route_match->getParameter('node_type');
        return '<p>' . t('Content items can be displayed using different view modes: Teaser, Full content, Print, RSS, etc. <em>Teaser</em> is a short format that is typically used in lists of multiple content items. <em>Full content</em> is typically used when the content is displayed on its own page.') . '</p>' . '<p>' . t('Here, you can define which fields are shown and hidden when %type content is displayed in each view mode, and define how the fields are displayed in each view mode.', ['%type' => $type->label()]) . '</p>';

      case 'entity.node.version_history':
        return '<p>' . t('Revisions allow you to track differences between multiple versions of your content, and revert to older versions.') . '</p>';

      case 'entity.node.edit_form':
        $node = $route_match->getParameter('node');
        $type = NodeType::load($node->getType());
        $help = $type->getHelp();
        return !empty($help) ? Xss::filterAdmin($help) : '';

      case 'node.add':
        $type = $route_match->getParameter('node_type');
        $help = $type->getHelp();
        return !empty($help) ? Xss::filterAdmin($help) : '';
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'node' => [
        'render element' => 'elements',
      ],
      'node_add_list' => [
        'variables' => [
          'content' => NULL,
        ],
      ],
      'node_edit_form' => [
        'render element' => 'form',
      ],
          // @todo Delete the next three entries as part of
          // https://www.drupal.org/node/3015623
      'field__node__title' => [
        'base hook' => 'field',
      ],
      'field__node__uid' => [
        'base hook' => 'field',
      ],
      'field__node__created' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, $context): void {
    if ($context['entity_type'] == 'node') {
      // Hide field labels in search index.
      if ($context['view_mode'] == 'search_index') {
        foreach ($display->getComponents() as $name => $options) {
          if (isset($options['label'])) {
            $options['label'] = 'hidden';
            $display->setComponent($name, $options);
          }
        }
      }
    }
  }

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(&$local_tasks) : void {
    // Removes 'Revisions' local task added by deriver. Local task
    // 'entity.node.version_history' will be replaced by
    // 'entity.version_history:node.version_history' after
    // https://www.drupal.org/project/drupal/issues/3153559.
    unset($local_tasks['entity.version_history:node.version_history']);
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $extra = [];
    $description = t('Node module element');
    foreach (NodeType::loadMultiple() as $bundle) {
      $extra['node'][$bundle->id()]['display']['links'] = [
        'label' => t('Links'),
        'description' => $description,
        'weight' => 100,
        'visible' => TRUE,
      ];
    }
    return $extra;
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    // Calculate the oldest and newest node created times, for use in search
    // rankings. (Note that field aliases have to be variables passed by
    // reference.)
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $min_alias = 'min_created';
      $max_alias = 'max_created';
      $result = \Drupal::entityQueryAggregate('node')->accessCheck(FALSE)->aggregate('created', 'MIN', NULL, $min_alias)->aggregate('created', 'MAX', NULL, $max_alias)->execute();
      if (isset($result[0])) {
        // Make an array with definite keys and store it in the state system.
        $array = ['min_created' => $result[0][$min_alias], 'max_created' => $result[0][$max_alias]];
        \Drupal::state()->set('node.min_max_update_time', $array);
      }
    }
  }

  /**
   * Implements hook_ranking().
   */
  #[Hook('ranking')]
  public function ranking() {
    // Create the ranking array and add the basic ranking options.
    $ranking = [
      'relevance' => [
        'title' => t('Keyword relevance'),
              // Average relevance values hover around 0.15
        'score' => 'i.relevance',
      ],
      'sticky' => [
        'title' => t('Content is sticky at top of lists'),
              // The sticky flag is either 0 or 1, which is automatically normalized.
        'score' => 'n.sticky',
      ],
      'promote' => [
        'title' => t('Content is promoted to the front page'),
              // The promote flag is either 0 or 1, which is automatically normalized.
        'score' => 'n.promote',
      ],
    ];
    // Add relevance based on updated date, but only if it the scale values have
    // been calculated in node_cron().
    if ($node_min_max = \Drupal::state()->get('node.min_max_update_time')) {
      $ranking['recent'] = [
        'title' => t('Recently created'),
            // Exponential decay with half life of 14% of the age range of nodes.
        'score' => 'EXP(-5 * (1 - (n.created - :node_oldest) / :node_range))',
        'arguments' => [
          ':node_oldest' => $node_min_max['min_created'],
          ':node_range' => max($node_min_max['max_created'] - $node_min_max['min_created'], 1),
        ],
      ];
    }
    return $ranking;
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for user entities.
   */
  #[Hook('user_predelete')]
  public function userPredelete($account) {
    // Delete nodes (current revisions).
    // @todo Introduce node_mass_delete() or make node_mass_update() more flexible.
    $nids = \Drupal::entityQuery('node')->condition('uid', $account->id())->accessCheck(FALSE)->execute();
    // Delete old revisions.
    $storage_controller = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $storage_controller->loadMultiple($nids);
    $storage_controller->delete($nodes);
    $revisions = $storage_controller->userRevisionIds($account);
    foreach ($revisions as $revision) {
      $storage_controller->deleteRevision($revision);
    }
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    // Add 'Back to content editing' link on preview page.
    $route_match = \Drupal::routeMatch();
    if ($route_match->getRouteName() == 'entity.node.preview') {
      $page_top['node_preview'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'node-preview-container',
            'container-inline',
          ],
        ],
        'view_mode' => \Drupal::formBuilder()->getForm(NodePreviewForm::class, $route_match->getParameter('node_preview')),
      ];
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Alters the theme form to use the admin theme on node editing.
   *
   * @see node_form_system_themes_admin_form_submit()
   */
  #[Hook('form_system_themes_admin_form_alter')]
  public function formSystemThemesAdminFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $form['admin_theme']['use_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => t('Use the administration theme when editing or creating content'),
      '#description' => t('Control which roles can "View the administration theme" on the <a href=":permissions">Permissions page</a>.', [
        ':permissions' => Url::fromRoute('user.admin_permissions.module', [
          'modules' => 'system',
        ])->toString(),
      ]),
      '#default_value' => \Drupal::configFactory()->getEditable('node.settings')->get('use_admin_theme'),
    ];
    $form['#submit'][] = 'node_form_system_themes_admin_form_submit';
  }

  /**
   * @defgroup node_access Node access rights
   * @{
   * The node access system determines who can do what to which nodes.
   *
   * In determining access rights for an existing node,
   * \Drupal\node\NodeAccessControlHandler first checks whether the user has the
   * "bypass node access" permission. Such users have unrestricted access to all
   * nodes. user 1 will always pass this check.
   *
   * Next, all implementations of hook_ENTITY_TYPE_access() for node will
   * be called. Each implementation may explicitly allow, explicitly forbid, or
   * ignore the access request. If at least one module says to forbid the request,
   * it will be rejected. If no modules deny the request and at least one says to
   * allow it, the request will be permitted.
   *
   * If all modules ignore the access request, then the node_access table is used
   * to determine access. All node access modules are queried using
   * hook_node_grants() to assemble a list of "grant IDs" for the user. This list
   * is compared against the table. If any row contains the node ID in question
   * (or 0, which stands for "all nodes"), one of the grant IDs returned, and a
   * value of TRUE for the operation in question, then access is granted. Note
   * that this table is a list of grants; any matching row is sufficient to grant
   * access to the node.
   *
   * In node listings (lists of nodes generated from a select query, such as the
   * default home page at path 'node', an RSS feed, a recent content block, etc.),
   * the process above is followed except that hook_ENTITY_TYPE_access() is not
   * called on each node for performance reasons and for proper functioning of
   * the pager system. When adding a node listing to your module, be sure to use
   * an entity query, which will add a tag of "node_access". This will allow
   * modules dealing with node access to ensure only nodes to which the user has
   * access are retrieved, through the use of hook_query_TAG_alter(). See the
   * @link entity_api Entity API topic @endlink for more information on entity
   * queries. Tagging a query with "node_access" does not check the
   * published/unpublished status of nodes, so the base query is responsible
   * for ensuring that unpublished nodes are not displayed to inappropriate users.
   *
   * Note: Even a single module returning an AccessResultInterface object from
   * hook_ENTITY_TYPE_access() whose isForbidden() method equals TRUE will block
   * access to the node. Therefore, implementers should take care to not deny
   * access unless they really intend to. Unless a module wishes to actively
   * forbid access it should return an AccessResultInterface object whose
   * isAllowed() nor isForbidden() methods return TRUE, to allow other modules or
   * the node_access table to control access.
   *
   * Note also that access to create nodes is handled by
   * hook_ENTITY_TYPE_create_access().
   *
   * @see \Drupal\node\NodeAccessControlHandler
   */

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    $type = $node->bundle();
    // Note create access is handled by hook_ENTITY_TYPE_create_access().
    switch ($operation) {
      case 'update':
        $access = AccessResult::allowedIfHasPermission($account, 'edit any ' . $type . ' content');
        if (!$access->isAllowed() && $account->hasPermission('edit own ' . $type . ' content')) {
          $access = $access->orIf(AccessResult::allowedIf($account->id() == $node->getOwnerId())->cachePerUser()->addCacheableDependency($node));
        }
        break;

      case 'delete':
        $access = AccessResult::allowedIfHasPermission($account, 'delete any ' . $type . ' content');
        if (!$access->isAllowed() && $account->hasPermission('delete own ' . $type . ' content')) {
          $access = $access->orIf(AccessResult::allowedIf($account->id() == $node->getOwnerId()))->cachePerUser()->addCacheableDependency($node);
        }
        break;

      default:
        $access = AccessResult::neutral();
    }
    return $access;
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * This is the hook_query_alter() for queries tagged with 'node_access'. It adds
   * node access checks for the user account given by the 'account' meta-data (or
   * current user if not provided), for an operation given by the 'op' meta-data
   * (or 'view' if not provided; other possible values are 'update' and 'delete').
   *
   * Queries tagged with 'node_access' that are not against the {node} table
   * must add the base table as metadata. For example:
   * @code
   *   $query
   *     ->addTag('node_access')
   *     ->addMetaData('base_table', 'taxonomy_index');
   * @endcode
   */
  #[Hook('query_node_access_alter')]
  public function queryNodeAccessAlter(AlterableInterface $query): void {
    // Read meta-data from query, if provided.
    if (!($account = $query->getMetaData('account'))) {
      $account = \Drupal::currentUser();
    }
    if (!($op = $query->getMetaData('op'))) {
      $op = 'view';
    }
    // If $account can bypass node access, or there are no node access modules,
    // or the operation is 'view' and the $account has a global view grant
    // (such as a view grant for node ID 0), we don't need to alter the query.
    if ($account->hasPermission('bypass node access')) {
      return;
    }
    if (!\Drupal::moduleHandler()->hasImplementations('node_grants')) {
      return;
    }
    if ($op == 'view' && node_access_view_all_nodes($account)) {
      return;
    }
    $tables = $query->getTables();
    $base_table = $query->getMetaData('base_table');
    // If the base table is not given, default to one of the node base tables.
    if (!$base_table) {
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = \Drupal::entityTypeManager()->getStorage('node')->getTableMapping();
      $node_base_tables = $table_mapping->getTableNames();
      foreach ($tables as $table_info) {
        if (!$table_info instanceof SelectInterface) {
          $table = $table_info['table'];
          // Ensure that 'node' and 'node_field_data' are always preferred over
          // 'node_revision' and 'node_field_revision'.
          if ($table == 'node' || $table == 'node_field_data') {
            $base_table = $table;
            break;
          }
          // If one of the node base tables are in the query, add it to the list
          // of possible base tables to join against.
          if (in_array($table, $node_base_tables)) {
            $base_table = $table;
          }
        }
      }
      // Bail out if the base table is missing.
      if (!$base_table) {
        throw new \Exception('Query tagged for node access but there is no node table, specify the base_table using meta data.');
      }
    }
    // Update the query for the given storage method.
    \Drupal::service('node.grant_storage')->alterQuery($query, $tables, $op, $account, $base_table);
    // Bubble the 'user.node_grants:$op' cache context to the current render
    // context.
    $renderer = \Drupal::service('renderer');
    if ($renderer->hasRenderContext()) {
      $build = ['#cache' => ['contexts' => ['user.node_grants:' . $op]]];
      $renderer->render($build);
    }
  }

  /**
   * @} End of "defgroup node_access".
   */

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules) {
    // Check if any of the newly enabled modules require the node_access table to
    // be rebuilt.
    if (!node_access_needs_rebuild() && \Drupal::moduleHandler()->hasImplementations('node_grants', $modules)) {
      node_access_needs_rebuild(TRUE);
    }
  }

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled($modules) {
    // Check whether any of the disabled modules implemented hook_node_grants(),
    // in which case the node access table needs to be rebuilt.
    foreach ($modules as $module) {
      // At this point, the module is already disabled, but its code is still
      // loaded in memory. Module functions must no longer be called. We only
      // check whether a hook implementation function exists and do not invoke it.
      // Node access also needs to be rebuilt if language module is disabled to
      // remove any language-specific grants.
      if (!node_access_needs_rebuild() && (\Drupal::moduleHandler()->hasImplementations('node_grants', $module) || $module == 'language')) {
        node_access_needs_rebuild(TRUE);
      }
    }
    // If there remains no more node_access module, rebuilding will be
    // straightforward, we can do it right now.
    if (node_access_needs_rebuild() && !\Drupal::moduleHandler()->hasImplementations('node_grants')) {
      node_access_rebuild();
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'configurable_language'.
   */
  #[Hook('configurable_language_delete')]
  public function configurableLanguageDelete(ConfigurableLanguageInterface $language) {
    // On nodes with this language, unset the language.
    \Drupal::entityTypeManager()->getStorage('node')->clearRevisionsLanguage($language);
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for comment entities.
   */
  #[Hook('comment_insert')]
  public function commentInsert($comment) {
    // Reindex the node when comments are added.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      node_reindex_node_search($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for comment entities.
   */
  #[Hook('comment_update')]
  public function commentUpdate($comment) {
    // Reindex the node when comments are changed.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      node_reindex_node_search($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for comment entities.
   */
  #[Hook('comment_delete')]
  public function commentDelete($comment) {
    // Reindex the node when comments are deleted.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      node_reindex_node_search($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_config_translation_info_alter().
   */
  #[Hook('config_translation_info_alter')]
  public function configTranslationInfoAlter(&$info): void {
    $info['node_type']['class'] = 'Drupal\node\ConfigTranslation\NodeTypeMapper';
  }

}
