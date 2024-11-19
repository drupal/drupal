<?php

namespace Drupal\comment\Hook;

use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\user\UserInterface;
use Drupal\user\RoleInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\comment\Entity\CommentType;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for comment.
 */
class CommentHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.comment':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Comment module allows users to comment on site content, set commenting defaults and permissions, and moderate comments. For more information, see the <a href=":comment">online documentation for the Comment module</a>.', [':comment' => 'https://www.drupal.org/documentation/modules/comment']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Enabling commenting') . '</dt>';
        $output .= '<dd>' . t('Comment functionality can be enabled for any entity sub-type (for example, a <a href=":content-type">content type</a>) by adding a <em>Comments</em> field on its <em>Manage fields page</em>. Adding or removing commenting for an entity through the user interface requires the <a href=":field_ui">Field UI</a> module to be installed, even though the commenting functionality works without it. For more information on fields and entities, see the <a href=":field">Field module help page</a>.', [
          ':content-type' => \Drupal::moduleHandler()->moduleExists('node') ? Url::fromRoute('entity.node_type.collection')->toString() : '#',
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . t('Configuring commenting settings') . '</dt>';
        $output .= '<dd>' . t('Commenting settings can be configured by editing the <em>Comments</em> field on the <em>Manage fields page</em> of an entity type if the <em>Field UI module</em> is installed. Configuration includes the label of the comments field, the number of comments to be displayed, and whether they are shown in threaded list. Commenting can be configured as: <em>Open</em> to allow new comments, <em>Closed</em> to view existing comments, but prevent new comments, or <em>Hidden</em> to hide existing comments and prevent new comments. Changing this configuration for an entity type will not change existing entity items.') . '</dd>';
        $output .= '<dt>' . t('Overriding default settings') . '</dt>';
        $output .= '<dd>' . t('Users with the appropriate permissions can override the default commenting settings of an entity type when they create an item of that type.') . '</dd>';
        $output .= '<dt>' . t('Adding comment types') . '</dt>';
        $output .= '<dd>' . t('Additional <em>comment types</em> can be created per entity sub-type and added on the <a href=":field">Comment types page</a>. If there are multiple comment types available you can select the appropriate one after adding a <em>Comments field</em>.', [
          ':field' => Url::fromRoute('entity.comment_type.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Approving and managing comments') . '</dt>';
        $output .= '<dd>' . t('Comments from users who have the <em>Skip comment approval</em> permission are published immediately. All other comments are placed in the <a href=":comment-approval">Unapproved comments</a> queue, until a user who has permission to <em>Administer comments and comment settings</em> publishes or deletes them. Published comments can be bulk managed on the <a href=":admin-comment">Published comments</a> administration page. When a comment has no replies, it remains editable by its author, as long as the author has <em>Edit own comments</em> permission.', [
          ':comment-approval' => Url::fromRoute('comment.admin_approval')->toString(),
          ':admin-comment' => Url::fromRoute('comment.admin')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.comment_type.collection':
        $output = '<p>' . t('This page provides a list of all comment types on the site and allows you to manage the fields, form and display settings for each.') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $return = [];
    foreach (CommentType::loadMultiple() as $comment_type) {
      $return['comment'][$comment_type->id()] = [
        'form' => [
          'author' => [
            'label' => t('Author'),
            'description' => t('Author textfield'),
            'weight' => -2,
          ],
        ],
      ];
      $return['comment'][$comment_type->id()]['display']['links'] = [
        'label' => t('Links'),
        'description' => t('Comment operation links'),
        'weight' => 100,
        'visible' => TRUE,
      ];
    }
    return $return;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'comment' => [
        'render element' => 'elements',
      ],
      'field__comment' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for 'field_config'.
   */
  #[Hook('field_config_create')]
  public function fieldConfigCreate(FieldConfigInterface $field) {
    if ($field->getType() == 'comment' && !$field->isSyncing()) {
      // Assign default values for the field.
      $default_value = $field->getDefaultValueLiteral();
      $default_value += [[]];
      $default_value[0] += [
        'status' => CommentItemInterface::OPEN,
        'cid' => 0,
        'last_comment_timestamp' => 0,
        'last_comment_name' => '',
        'last_comment_uid' => 0,
        'comment_count' => 0,
      ];
      $field->setDefaultValue($default_value);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'field_config'.
   */
  #[Hook('field_config_update')]
  public function fieldConfigUpdate(FieldConfigInterface $field) {
    if ($field->getType() == 'comment') {
      // Comment field settings also affects the rendering of *comment* entities,
      // not only the *commented* entities.
      \Drupal::entityTypeManager()->getViewBuilder('comment')->resetCache();
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for 'field_storage_config'.
   */
  #[Hook('field_storage_config_insert')]
  public function fieldStorageConfigInsert(FieldStorageConfigInterface $field_storage) {
    if ($field_storage->getType() == 'comment') {
      // Check that the target entity type uses an integer ID.
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      if (!_comment_entity_uses_integer_id($entity_type_id)) {
        throw new \UnexpectedValueException('You cannot attach a comment field to an entity with a non-integer ID field');
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'field_config'.
   */
  #[Hook('field_config_delete')]
  public function fieldConfigDelete(FieldConfigInterface $field) {
    if ($field->getType() == 'comment') {
      // Delete all comments that used by the entity bundle.
      $entity_query = \Drupal::entityQuery('comment')->accessCheck(FALSE);
      $entity_query->condition('entity_type', $field->getEntityTypeId());
      $entity_query->condition('field_name', $field->getName());
      $cids = $entity_query->execute();
      $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
      $comments = $comment_storage->loadMultiple($cids);
      $comment_storage->delete($comments);
    }
  }

  /**
   * Implements hook_node_links_alter().
   */
  #[Hook('node_links_alter')]
  public function nodeLinksAlter(array &$links, NodeInterface $node, array &$context): void {
    // Comment links are only added to node entity type for backwards
    // compatibility. Should you require comment links for other entity types you
    // can do so by implementing a new field formatter.
    // @todo Make this configurable from the formatter. See
    //   https://www.drupal.org/node/1901110.
    $comment_links = \Drupal::service('comment.link_builder')->buildCommentedEntityLinks($node, $context);
    $links += $comment_links;
  }

  /**
   * Implements hook_entity_view().
   */
  #[Hook('entity_view')]
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    if ($entity instanceof FieldableEntityInterface && $view_mode == 'rss' && $display->getComponent('links')) {
      /** @var \Drupal\comment\CommentManagerInterface $comment_manager */
      $comment_manager = \Drupal::service('comment.manager');
      $fields = $comment_manager->getFields($entity->getEntityTypeId());
      foreach ($fields as $field_name => $detail) {
        if ($entity->hasField($field_name) && $entity->get($field_name)->status != CommentItemInterface::HIDDEN) {
          // Add a comments RSS element which is a URL to the comments of this
          // entity.
          $options = ['fragment' => 'comments', 'absolute' => TRUE];
          $entity->rss_elements[] = [
            'key' => 'comments',
            'value' => $entity->toUrl('canonical', $options)->toString(),
          ];
        }
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter() for node entities.
   */
  #[Hook('node_view_alter')]
  public function nodeViewAlter(array &$build, EntityInterface $node, EntityViewDisplayInterface $display): void {
    if (\Drupal::moduleHandler()->moduleExists('history')) {
      $build['#attributes']['data-history-node-id'] = $node->id();
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for field_ui_field_storage_add_form.
   */
  #[Hook('form_field_ui_field_storage_add_form_alter')]
  public function formFieldUiFieldStorageAddFormAlter(&$form, FormStateInterface $form_state) : void {
    $route_match = \Drupal::routeMatch();
    if ($form_state->get('entity_type_id') == 'comment' && $route_match->getParameter('commented_entity_type')) {
      $form['#title'] = \Drupal::service('comment.manager')->getFieldUIPageTitle($route_match->getParameter('commented_entity_type'), $route_match->getParameter('field_name'));
    }
  }

  /**
   * Implements hook_field_info_entity_type_ui_definitions_alter().
   */
  #[Hook('field_info_entity_type_ui_definitions_alter')]
  public function fieldInfoEntityTypeUiDefinitionsAlter(array &$ui_definitions, string $entity_type_id): void {
    if (!_comment_entity_uses_integer_id($entity_type_id)) {
      unset($ui_definitions['comment']);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_field_ui_form_display_overview_form_alter')]
  public function formFieldUiFormDisplayOverviewFormAlter(&$form, FormStateInterface $form_state) : void {
    $route_match = \Drupal::routeMatch();
    if ($form['#entity_type'] == 'comment' && $route_match->getParameter('commented_entity_type')) {
      $form['#title'] = \Drupal::service('comment.manager')->getFieldUIPageTitle($route_match->getParameter('commented_entity_type'), $route_match->getParameter('field_name'));
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_field_ui_display_overview_form_alter')]
  public function formFieldUiDisplayOverviewFormAlter(&$form, FormStateInterface $form_state) : void {
    $route_match = \Drupal::routeMatch();
    if ($form['#entity_type'] == 'comment' && $route_match->getParameter('commented_entity_type')) {
      $form['#title'] = \Drupal::service('comment.manager')->getFieldUIPageTitle($route_match->getParameter('commented_entity_type'), $route_match->getParameter('field_name'));
    }
  }

  /**
   * Implements hook_entity_storage_load().
   *
   * @see \Drupal\comment\Plugin\Field\FieldType\CommentItem::propertyDefinitions()
   */
  #[Hook('entity_storage_load')]
  public function entityStorageLoad($entities, $entity_type) {
    // Comments can only be attached to content entities, so skip others.
    if (!\Drupal::entityTypeManager()->getDefinition($entity_type)->entityClassImplements(FieldableEntityInterface::class)) {
      return;
    }
    if (!\Drupal::service('comment.manager')->getFields($entity_type)) {
      // Do not query database when entity has no comment fields.
      return;
    }
    // Load comment information from the database and update the entity's
    // comment statistics properties, which are defined on each CommentItem field.
    $result = \Drupal::service('comment.statistics')->read($entities, $entity_type);
    foreach ($result as $record) {
      // Skip fields that entity does not have.
      if (!$entities[$record->entity_id]->hasField($record->field_name)) {
        continue;
      }
      $comment_statistics = $entities[$record->entity_id]->get($record->field_name);
      $comment_statistics->cid = $record->cid;
      $comment_statistics->last_comment_timestamp = $record->last_comment_timestamp;
      $comment_statistics->last_comment_name = $record->last_comment_name;
      $comment_statistics->last_comment_uid = $record->last_comment_uid;
      $comment_statistics->comment_count = $record->comment_count;
    }
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity) {
    // Allow bulk updates and inserts to temporarily disable the
    // maintenance of the {comment_entity_statistics} table.
    if (\Drupal::state()->get('comment.maintain_entity_statistics') && ($fields = \Drupal::service('comment.manager')->getFields($entity->getEntityTypeId()))) {
      \Drupal::service('comment.statistics')->create($entity, $fields);
    }
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity) {
    // Entities can have non-numeric IDs, but {comment} and
    // {comment_entity_statistics} tables have integer columns for entity ID, and
    // PostgreSQL throws exceptions if you attempt query conditions with
    // mismatched types. So, we need to verify that the ID is numeric (even for an
    // entity type that has an integer ID, $entity->id() might be a string
    // containing a number), and then cast it to an integer when querying.
    if ($entity instanceof FieldableEntityInterface && is_numeric($entity->id())) {
      $entity_query = \Drupal::entityQuery('comment')->accessCheck(FALSE);
      $entity_query->condition('entity_id', (int) $entity->id());
      $entity_query->condition('entity_type', $entity->getEntityTypeId());
      $cids = $entity_query->execute();
      $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
      $comments = $comment_storage->loadMultiple($cids);
      $comment_storage->delete($comments);
      \Drupal::service('comment.statistics')->delete($entity);
    }
  }

  /**
   * Implements hook_node_update_index().
   */
  #[Hook('node_update_index')]
  public function nodeUpdateIndex(EntityInterface $node) {
    $index_comments =& drupal_static('comment_node_update_index');
    if ($index_comments === NULL) {
      // Do not index in the following three cases:
      // 1. 'Authenticated user' can search content but can't access comments.
      // 2. 'Anonymous user' can search content but can't access comments.
      // 3. Any role can search content but can't access comments and access
      // comments is not granted by the 'authenticated user' role. In this case
      // all users might have both permissions from various roles but it is also
      // possible to set up a user to have only search content and so a user
      // edit could change the security situation so it is not safe to index the
      // comments.
      $index_comments = TRUE;
      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
      $authenticated_can_access = $roles[RoleInterface::AUTHENTICATED_ID]->hasPermission('access comments');
      foreach ($roles as $rid => $role) {
        if ($role->hasPermission('search content') && !$role->hasPermission('access comments')) {
          if ($rid == RoleInterface::AUTHENTICATED_ID || $rid == RoleInterface::ANONYMOUS_ID || !$authenticated_can_access) {
            $index_comments = FALSE;
            break;
          }
        }
      }
    }
    $build = [];
    if ($index_comments) {
      foreach (\Drupal::service('comment.manager')->getFields('node') as $field_name => $info) {
        // Skip fields that entity does not have.
        if (!$node->hasField($field_name)) {
          continue;
        }
        $field_definition = $node->getFieldDefinition($field_name);
        $mode = $field_definition->getSetting('default_mode');
        $comments_per_page = $field_definition->getSetting('per_page');
        if ($node->get($field_name)->status) {
          $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadThread($node, $field_name, $mode, $comments_per_page);
          if ($comments) {
            $build[] = \Drupal::entityTypeManager()->getViewBuilder('comment')->viewMultiple($comments);
          }
        }
      }
    }
    return \Drupal::service('renderer')->renderInIsolation($build);
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    // Store the maximum possible comments per thread (used for node search
    // ranking by reply count).
    \Drupal::state()->set('comment.node_comment_statistics_scale', 1.0 / max(1, \Drupal::service('comment.statistics')->getMaximumCount('node')));
  }

  /**
   * Implements hook_node_search_result().
   *
   * Formats a comment count string and returns it, for display with search
   * results.
   */
  #[Hook('node_search_result')]
  public function nodeSearchResult(EntityInterface $node) {
    $comment_fields = \Drupal::service('comment.manager')->getFields('node');
    $comments = 0;
    $open = FALSE;
    foreach ($comment_fields as $field_name => $info) {
      // Skip fields that entity does not have.
      if (!$node->hasField($field_name)) {
        continue;
      }
      // Do not make a string if comments are hidden.
      $status = $node->get($field_name)->status;
      if (\Drupal::currentUser()->hasPermission('access comments') && $status != CommentItemInterface::HIDDEN) {
        if ($status == CommentItemInterface::OPEN) {
          // At least one comment field is open.
          $open = TRUE;
        }
        $comments += $node->get($field_name)->comment_count;
      }
    }
    // Do not make a string if there are no comment fields, or no comments exist
    // or all comment fields are hidden.
    if ($comments > 0 || $open) {
      return [
        'comment' => \Drupal::translation()->formatPlural($comments, '1 comment', '@count comments'),
      ];
    }
  }

  /**
   * Implements hook_user_cancel().
   */
  #[Hook('user_cancel')]
  public function userCancel($edit, UserInterface $account, $method) {
    switch ($method) {
      case 'user_cancel_block_unpublish':
        $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadByProperties(['uid' => $account->id()]);
        foreach ($comments as $comment) {
          $comment->setUnpublished();
          $comment->save();
        }
        break;

      case 'user_cancel_reassign':
        /** @var \Drupal\comment\CommentInterface[] $comments */
        $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadByProperties(['uid' => $account->id()]);
        foreach ($comments as $comment) {
          $langcodes = array_keys($comment->getTranslationLanguages());
          // For efficiency manually save the original comment before applying any
          // changes.
          $comment->original = clone $comment;
          foreach ($langcodes as $langcode) {
            $comment_translated = $comment->getTranslation($langcode);
            $comment_translated->setOwnerId(0);
            $comment_translated->setAuthorName(\Drupal::config('user.settings')->get('anonymous'));
          }
          $comment->save();
        }
        break;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for user entities.
   */
  #[Hook('user_predelete')]
  public function userPredelete($account) {
    $entity_query = \Drupal::entityQuery('comment')->accessCheck(FALSE);
    $entity_query->condition('uid', $account->id());
    $cids = $entity_query->execute();
    $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
    $comments = $comment_storage->loadMultiple($cids);
    $comment_storage->delete($comments);
  }

  /**
   * Implements hook_ranking().
   */
  #[Hook('ranking')]
  public function ranking() {
    return \Drupal::service('comment.statistics')->getRankingInfo();
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for entity_view_display entities.
   */
  #[Hook('entity_view_display_presave')]
  public function entityViewDisplayPresave(EntityViewDisplayInterface $display) {
    // Act only on comment view displays being disabled.
    if ($display->isNew() || $display->getTargetEntityTypeId() !== 'comment' || $display->status()) {
      return;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    if (!$storage->loadUnchanged($display->getOriginalId())->status()) {
      return;
    }
    // Disable the comment field formatter when the used view display is disabled.
    foreach ($storage->loadMultiple() as $view_display) {
      $changed = FALSE;
      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
      foreach ($view_display->getComponents() as $field => $component) {
        if (isset($component['type']) && $component['type'] === 'comment_default') {
          if ($component['settings']['view_mode'] === $display->getMode()) {
            $view_display->removeComponent($field);
            /** @var \Drupal\Core\Entity\EntityViewModeInterface $mode */
            $mode = EntityViewMode::load($display->getTargetEntityTypeId() . '.' . $display->getMode());
            $arguments = [
              '@id' => $view_display->id(),
              '@name' => $field,
              '@display' => $mode->label(),
              '@mode' => $display->getMode(),
            ];
            \Drupal::logger('system')->warning("View display '@id': Comment field formatter '@name' was disabled because it is using the comment view display '@display' (@mode) that was just disabled.", $arguments);
            $changed = TRUE;
          }
        }
      }
      if ($changed) {
        $view_display->save();
      }
    }
  }

  /**
   * Implements hook_field_type_category_info_alter().
   */
  #[Hook('field_type_category_info_alter')]
  public function fieldTypeCategoryInfoAlter(&$definitions): void {
    // The `comment` field type belongs in the `general` category, so the
    // libraries need to be attached using an alter hook.
    $definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY]['libraries'][] = 'comment/drupal.comment-icon';
  }

}
