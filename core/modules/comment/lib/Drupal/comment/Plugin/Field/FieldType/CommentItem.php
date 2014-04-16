<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Field\FieldType\CommentItem.
 */

namespace Drupal\comment\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Plugin implementation of the 'comment' field type.
 *
 * @FieldType(
 *   id = "comment",
 *   label = @Translation("Comments"),
 *   description = @Translation("This field manages configuration and presentation of comments on an entity."),
 *   default_widget = "comment_default",
 *   default_formatter = "comment_default"
 * )
 */
class CommentItem extends FieldItemBase implements CommentItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'description' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultInstanceSettings() {
    return array(
      'default_mode' => COMMENT_MODE_THREADED,
      'per_page' => 50,
      'form_location' => COMMENT_FORM_BELOW,
      'anonymous' => COMMENT_ANONYMOUS_MAYNOT_CONTACT,
      'subject' => 1,
      'preview' => DRUPAL_OPTIONAL,
    ) + parent::defaultInstanceSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Comment status value'));

    $properties['cid'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment ID'));

    $properties['last_comment_timestamp'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment timestamp'))
      ->setDescription(t('The time that the last comment was created.'));

    $properties['last_comment_name'] = DataDefinition::create('string')
      ->setLabel(t('Last comment name'))
      ->setDescription(t('The name of the user posting the last comment.'));

    $properties['last_comment_uid'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment user ID'));

    $properties['comment_count'] = DataDefinition::create('integer')
      ->setLabel(t('Number of comments'))
      ->setDescription(t('The number of comments.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'status' => array(
          'description' => 'Whether comments are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(),
      'foreign keys' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    $settings = $this->getSettings();

    $entity_type = $this->getEntity()->getEntityTypeId();
    $field_name = $this->getFieldDefinition()->getName();
    $anonymous_user = new AnonymousUserSession();

    $element['comment'] = array(
      '#type' => 'details',
      '#title' => t('Comment form settings'),
      '#open' => TRUE,
      '#bundle' => "{$entity_type}__{$field_name}",
      '#process' => array(array(get_class($this), 'processSettingsElement')),
      '#attributes' => array(
        'class' => array('comment-instance-settings-form'),
      ),
      '#attached' => array(
        'library' => array('comment/drupal.comment'),
      ),
    );
    $element['comment']['default_mode'] = array(
      '#type' => 'checkbox',
      '#title' => t('Threading'),
      '#default_value' => $settings['default_mode'],
      '#description' => t('Show comment replies in a threaded list.'),
    );
    $element['comment']['per_page'] = array(
      '#type' => 'number',
      '#title' => t('Comments per page'),
      '#default_value' => $settings['per_page'],
      '#required' => TRUE,
      '#min' => 10,
      '#max' => 1000,
      '#step' => 10,
    );
    $element['comment']['anonymous'] = array(
      '#type' => 'select',
      '#title' => t('Anonymous commenting'),
      '#default_value' => $settings['anonymous'],
      '#options' => array(
        COMMENT_ANONYMOUS_MAYNOT_CONTACT => t('Anonymous posters may not enter their contact information'),
        COMMENT_ANONYMOUS_MAY_CONTACT => t('Anonymous posters may leave their contact information'),
        COMMENT_ANONYMOUS_MUST_CONTACT => t('Anonymous posters must leave their contact information'),
      ),
      '#access' => $anonymous_user->hasPermission('post comments'),
    );
    $element['comment']['subject'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow comment title'),
      '#default_value' => $settings['subject'],
    );
    $element['comment']['form_location'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show reply form on the same page as comments'),
      '#default_value' => $settings['form_location'],
    );
    $element['comment']['preview'] = array(
      '#type' => 'radios',
      '#title' => t('Preview comment'),
      '#default_value' => $settings['preview'],
      '#options' => array(
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if ($name == 'status' && !isset($this->values[$name])) {
      // Get default value from field instance when no data saved in entity.
      $field_default_values = $this->getFieldDefinition()->getDefaultValue($this->getEntity());
      return $field_default_values[0]['status'];
    }
    else {
      return parent::__get($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // There is always a value for this field, it is one of
    // CommentItemInterface::OPEN, CommentItemInterface::CLOSED or
    // CommentItemInterface::HIDDEN.
    return FALSE;
  }

  /**
   * Process callback to add submit handler for instance settings form.
   *
   * Attaches the required translation entity handlers for the instance which
   * correlates one to one with the comment bundle.
   */
  public static function processSettingsElement($element) {
    // Settings should not be stored as nested.
    $parents = $element['#parents'];
    array_pop($parents);
    $element['#parents'] = $parents;
    // Add translation entity handlers.
    if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $comment_form = $element;
      $comment_form_state['content_translation']['key'] = 'language_configuration';
      $element += content_translation_enable_widget('comment', $element['#bundle'], $comment_form, $comment_form_state);
      $element['content_translation']['#parents'] = $element['content_translation']['#array_parents'] = array(
        'content_translation'
      );
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element = array();

    $element['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Field description'),
      '#description' => t('Describe this comment field. The text will be displayed on the <em>Comments Forms</em> page.'),
      '#default_value' => $this->getSetting('description'),
    );
    return $element;
  }

}
