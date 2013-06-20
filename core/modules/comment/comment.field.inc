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
      'instance_settings' => array(
        // Save this settings to allow other modules to migrate their data.
        // @todo The "comment_default" formatter settings should used instead.
        'default_mode' => COMMENT_MODE_THREADED,
        'per_page' => 50,
        'form_location' => COMMENT_FORM_BELOW,
        // Comment form settings per field bundle.
        'anonymous' => COMMENT_ANONYMOUS_MAYNOT_CONTACT,
        'subject' => 1,
        'preview' => DRUPAL_OPTIONAL,
      ),
      'default_widget' => 'comment_default',
      'default_formatter' => 'comment_default',
      'field item class' => 'Drupal\comment\Type\CommentItem',
    )
  );
}

/**
 * Implements hook_field_instance_settings_form().
 */
function comment_field_instance_settings_form($field, $instance) {
  $settings = $instance['settings'];
  $form['comment'] = array(
    '#type' => 'details',
    '#title' => t('Comment form settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#field_name' => $field['field_name'],
    '#process' => array('_comment_field_instance_settings_form_process'),
    '#attributes' => array(
      'class' => array('comment-instance-settings-form'),
    ),
    '#attached' => array(
      'library' => array(array('comment', 'drupal.comment')),
    ),
  );
  $form['comment']['default_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Threading'),
    '#default_value' => $settings['default_mode'],
    '#description' => t('Show comment replies in a threaded list.'),
  );
  $form['comment']['per_page'] = array(
    '#type' => 'select',
    '#title' => t('Comments per page'),
    '#default_value' => $settings['per_page'],
    '#options' => _comment_per_page(),
  );
  $form['comment']['anonymous'] = array(
    '#type' => 'select',
    '#title' => t('Anonymous commenting'),
    '#default_value' => $settings['anonymous'],
    '#options' => array(
      COMMENT_ANONYMOUS_MAYNOT_CONTACT => t('Anonymous posters may not enter their contact information'),
      COMMENT_ANONYMOUS_MAY_CONTACT => t('Anonymous posters may leave their contact information'),
      COMMENT_ANONYMOUS_MUST_CONTACT => t('Anonymous posters must leave their contact information'),
    ),
    '#access' => user_access('post comments', drupal_anonymous_user()),
  );
  $form['comment']['subject'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow comment title'),
    '#default_value' => $settings['subject'],
  );
  $form['comment']['form_location'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show reply form on the same page as comments'),
    '#default_value' => $settings['form_location'],
  );
  $form['comment']['preview'] = array(
    '#type' => 'radios',
    '#title' => t('Preview comment'),
    '#default_value' => $settings['preview'],
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
function _comment_field_instance_settings_form_process($element, $form_state) {
  // Settings should not be stored as nested.
  $parents = $element['#parents'];
  array_pop($parents);
  $element['#parents'] = $parents;
  // Add translation entity handlers.
  if (Drupal::moduleHandler()->moduleExists('translation_entity')) {
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
 * Implements hook_field_is_empty().
 */
function comment_field_is_empty($item, $field) {
  // We always want the values saved so we can rely on them.
  return FALSE;
}
