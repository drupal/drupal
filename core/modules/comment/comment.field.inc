<?php

/**
 * @file
 * Enables comments with moderation on entities
 *
 * Provides a field and formatters to allow administrators to enable and
 * configure comments attached to entities.
 */
use \Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_field_info().
 */
function comment_field_info() {
  return array(
    'comment' => array(
      'label' => t('Comments'),
      'description' => t('This field manages configuration and presentation of comments on an entity'),
      'settings' => array(),
      'instance_settings' => array(
        'comment' => array(
          'comment' => COMMENT_OPEN,
          'comment_default_mode' => COMMENT_MODE_THREADED,
          'comment_default_per_page' => 50,
          'comment_anonymous' => COMMENT_ANONYMOUS_MAYNOT_CONTACT,
          'comment_subject_field' => 1,
          'comment_form_location' => COMMENT_FORM_BELOW,
          'comment_preview' => DRUPAL_OPTIONAL
        )
      ),
      'default_widget' => 'comment_default',
      'default_formatter' => 'comment_default'
    )
  );
}

/**
 * Implements hook_field_instance_settings_form().
 */
function comment_field_instance_settings_form($field, $instance) {
  $settings = $instance['settings']['comment'];
  $form['comment'] = array(
    '#type' => 'details',
    '#title' => t('Comment settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#field_name' => $field['field_name'],
    '#process' => array('comment_instance_settings_translation_entity_process'),
    '#attributes' => array(
      'class' => array('comment-instance-settings-form'),
    ),
    '#attached' => array(
      'library' => array(array('comment', 'drupal.comment')),
    ),
  );
  $form['comment']['comment'] = array(
    '#type' => 'select',
    '#title' => t('Default comment setting for new content'),
    '#default_value' => $settings['comment'],
    '#options' => array(
      COMMENT_OPEN => t('Open'),
      COMMENT_CLOSED => t('Closed'),
      COMMENT_HIDDEN => t('Hidden'),
    ),
  );
  $form['comment']['comment_default_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Threading'),
    '#default_value' => $settings['comment_default_mode'],
    '#description' => t('Show comment replies in a threaded list.'),
  );
  $form['comment']['comment_default_per_page'] = array(
    '#type' => 'select',
    '#title' => t('Comments per page'),
    '#default_value' => $settings['comment_default_per_page'],
    '#options' => _comment_per_page(),
  );
  $form['comment']['comment_anonymous'] = array(
    '#type' => 'select',
    '#title' => t('Anonymous commenting'),
    '#default_value' => $settings['comment_anonymous'],
    '#options' => array(
      COMMENT_ANONYMOUS_MAYNOT_CONTACT => t('Anonymous posters may not enter their contact information'),
      COMMENT_ANONYMOUS_MAY_CONTACT => t('Anonymous posters may leave their contact information'),
      COMMENT_ANONYMOUS_MUST_CONTACT => t('Anonymous posters must leave their contact information'),
    ),
    '#access' => user_access('post comments', drupal_anonymous_user()),
  );
  $form['comment']['comment_subject_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow comment title'),
    '#default_value' => $settings['comment_subject_field'],
  );
  $form['comment']['comment_form_location'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show reply form on the same page as comments'),
    '#default_value' => $settings['comment_form_location'],
  );
  $form['comment']['comment_preview'] = array(
    '#type' => 'radios',
    '#title' => t('Preview comment'),
    '#default_value' => $settings['comment_preview'],
    '#options' => array(
      DRUPAL_DISABLED => t('Disabled'),
      DRUPAL_OPTIONAL => t('Optional'),
      DRUPAL_REQUIRED => t('Required'),
    ),
  );
  return $form;
}

/**
 * Process callback to add submit handler for instance settings form.
 *
 * Attaches the required translation entity handlers for the instance which
 * correlates one to one with the comment bundle.
 */
function comment_instance_settings_translation_entity_process($element, $form_state) {
  if (module_exists('translation_entity')) {
    $comment_form = $element;
    $comment_form_state['translation_entity']['key'] = 'language_configuration';
    $element += translation_entity_enable_widget('comment', $element['#field_name'], $comment_form, $comment_form_state);
    $element['translation_entity']['#parents'] = $element['translation_entity']['#array_parents'] = array(
      'translation_entity'
    );
  }
  return $element;
}

/**
 * Implements hook_field_formatter_info().
 */
function comment_field_formatter_info() {
  return array(
    'comment_default' => array(
      'label' => t('Comments'),
      'field types' => array('comment'),
    ),
  );
}

/**
 * Implements hook_field_widget_info().
 *
 * @todo convert this to a WidgetPlugin.
 */
function comment_field_widget_info() {
  return array(
    'comment_default' => array(
      'label' => t('Comment'),
      'field types' => array('comment'),
      'settings' => array(),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
        'default value' => FIELD_BEHAVIOR_NONE,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_form().
 *
 * @todo convert this to a WidgetPlugin.
 */
function comment_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $entity = $element['#entity'];
  $settings = $instance['settings']['comment'];
  $element += array(
    '#type' => 'details',
    '#access' => user_access('administer comments'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'advanced',
    '#attributes' => array(
      'class' => array('comment-node-settings-form'),
    ),
    '#attached' => array(
      'library' => array('comment', 'drupal.comment'),
    ),
    '#weight' => 30,
  );
  $values = reset($items);
  if (empty($values)) {
    $values = array(
      // Default taken from instance settings.
      'comment' => $settings['comment'],
    );
  }
  $entity_id = $entity->id();
  $comment_count = empty($entity->comment_statistics[$field['field_name']]->comment_count) ? 0 : $entity->comment_statistics[$field['field_name']]->comment_count;
  $comment_settings = ($values['comment'] == COMMENT_HIDDEN && empty($comment_count)) ? COMMENT_CLOSED : $values['comment'];
  $element['comment'] = array(
    '#type' => 'radios',
    '#title' => t('Comments'),
    '#title_display' => 'invisible',
    '#default_value' => $comment_settings,
    '#options' => array(
      COMMENT_OPEN => t('Open'),
      COMMENT_CLOSED => t('Closed'),
      COMMENT_HIDDEN => t('Hidden'),
    ),
    COMMENT_OPEN => array(
      '#description' => t('Users with the "Post comments" permission can post comments.'),
    ),
    COMMENT_CLOSED => array(
      '#description' => t('Users cannot post comments, but existing comments will be displayed.'),
    ),
    COMMENT_HIDDEN => array(
      '#description' => t('Comments are hidden from view.'),
    ),
  );
  // If the node doesn't have any comments, the "hidden" option makes no
  // sense, so don't even bother presenting it to the user.
  if (empty($comment_count)) {
    $element['comment'][COMMENT_HIDDEN]['#access'] = FALSE;
    // Also adjust the description of the "closed" option.
    $element['comment'][COMMENT_CLOSED]['#description'] = t('Users cannot post comments.');
  }
  return array($element);
}

/**
 * Implements hook_field_formatter_view().
 */
function comment_field_formatter_view(EntityInterface $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();

  switch ($display['type']) {
    case 'comment_default':
      // We only ever have one value.
      $values = reset($items);
      if ($values['comment'] != COMMENT_HIDDEN) {
        $additions = FALSE;
        if ($values['comment'] && empty($entity->in_preview)) {
          // Only attempt to render comments if the node has visible comments.
          // Unpublished comments are not included in
          // $node->entity_statistics[$field_name]->comment_count, so show
          // comments unconditionally if the user is an administrator.
          $comment_settings = $instance['settings']['comment'];
          if (((!empty($entity->comment_statistics[$field['field_name']]->comment_count) && user_access('access comments')) || user_access('administer comments')) &&
              !empty($entity->content['#view_mode']) &&
              !in_array($entity->content['#view_mode'], array('search_result', 'search_index'))) {
            // Comment threads aren't added to search results/indexes using the
            // formatter, @see comment_node_update_index().
            $mode = $comment_settings['comment_default_mode'];
            $comments_per_page = $comment_settings['comment_default_per_page'];
            if ($cids = comment_get_thread($entity, $field['field_name'], $mode, $comments_per_page)) {
              $comments = comment_load_multiple($cids);
              comment_prepare_thread($comments);
              $build = comment_view_multiple($comments);
              $build['pager']['#theme'] = 'pager';
              $additions['comments'] = $build;
            }
          }

          // Append comment form if needed.
          if ($values['comment'] == COMMENT_OPEN && $comment_settings['comment_form_location'] == COMMENT_FORM_BELOW) {
            // Only show the add comment form if the user has permission and the
            // view mode is not search_result or search_index.
            if (user_access('post comments') && !empty($entity->content['#view_mode']) &&
              !in_array($entity->content['#view_mode'], array('search_result', 'search_index'))) {
              $additions['comment_form'] = comment_add($entity, $field['field_name']);
            }
          }
          if ($additions) {
            $additions += array(
              '#theme' => 'comment_wrapper__' . $entity->entityType() . '__' . $entity->bundle() . '__' . $field['field_name'],
              '#entity' => $entity,
              '#display_mode' => $instance['settings']['comment']['comment_default_mode'],
              'comments' => array(),
              'comment_form' => array(),
            );
          }
          if (!empty($additions)) {
            $element[] = $additions;
          }
        }
      }
      break;

  }

  return $element;
}

/**
 * Implements hook_field_is_empty().
 */
function comment_field_is_empty($item, $field) {
  // We always want the values saved so we can rely on them.
  return FALSE;
}
