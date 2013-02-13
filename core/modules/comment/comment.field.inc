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
          // Save this settings to allow other modules to migrate their data.
          // The "comment_default" formatter settings are used instead.
          'comment_default_mode' => COMMENT_MODE_THREADED,
          'comment_default_per_page' => 50,
          'comment_form_location' => COMMENT_FORM_BELOW,
          // Comment form settings per field bundle.
          'comment_anonymous' => COMMENT_ANONYMOUS_MAYNOT_CONTACT,
          'comment_subject_field' => 1,
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
    '#title' => t('Comment form settings'),
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
 * Implements hook_field_widget_info_alter().
 *
 * @todo convert this to a WidgetPlugin annotation.
 */
function comment_field_widget_info_alter(&$info) {
  $info['comment_default']['behaviors'] = array(
    'multiple values' => FIELD_BEHAVIOR_CUSTOM,
    'default value' => FIELD_BEHAVIOR_NONE,
  );
}

/**
 * Implements hook_field_is_empty().
 */
function comment_field_is_empty($item, $field) {
  // We always want the values saved so we can rely on them.
  return FALSE;
}
