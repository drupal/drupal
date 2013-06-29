<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\field\field_type\CommentItem.
 */

namespace Drupal\comment\Plugin\field\field_type;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\field\Plugin\Core\Entity\Field;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;

/**
 * Plugin implementation of the 'comment' field type.
 *
 * @FieldType(
 *   id = "comment",
 *   module = "comment",
 *   label = @Translation("Comments"),
 *   description = @Translation("This field manages configuration and presentation of comments on an entity."),
 *   instance_settings = {
 *     "default_mode" = COMMENT_MODE_THREADED,
 *     "per_page" = 50,
 *     "form_location" = COMMENT_FORM_BELOW,
 *     "anonymous" = COMMENT_ANONYMOUS_MAYNOT_CONTACT,
 *     "subject" = 1,
 *     "preview" = DRUPAL_OPTIONAL,
 *   },
 *   default_widget = "comment_default",
 *   default_formatter = "comment_default"
 * )
 */
class CommentItem extends ConfigFieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['status'] = array(
        'type' => 'integer',
        'label' => t('Comment status value'),
        'settings' => array('default_value' => COMMENT_OPEN),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(Field $field) {
    return array(
      'columns' => array(
        'status' => array(
          'description' => 'Whether comments are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    $settings = $this->getInstance()->settings;
    $field = $this->getInstance()->getField();

    $element['comment'] = array(
      '#type' => 'details',
      '#title' => t('Comment form settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#field_name' => $field->id(),
      '#process' => array('_comment_field_instance_settings_form_process'),
      '#attributes' => array(
        'class' => array('comment-instance-settings-form'),
      ),
      '#attached' => array(
        'library' => array(array('comment', 'drupal.comment')),
      ),
    );
    $element['comment']['default_mode'] = array(
      '#type' => 'checkbox',
      '#title' => t('Threading'),
      '#default_value' => $settings['default_mode'],
      '#description' => t('Show comment replies in a threaded list.'),
    );
    $element['comment']['per_page'] = array(
      '#type' => 'select',
      '#title' => t('Comments per page'),
      '#default_value' => $settings['per_page'],
      '#options' => _comment_per_page(),
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
      '#access' => user_access('post comments', drupal_anonymous_user()),
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
  public function applyDefaultValue($notify = TRUE) {
    // Retrieve the configured default value for the instance.
    $defaults = $this->getInstance()->default_value;
    $this->setValue(reset($defaults), $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // We always want the values saved so we can rely on them.
    return FALSE;
  }
}
