<?php

/**
 * @file
 * Contains \Drupal\email\Plugin\field\field_type\LinkItem.
 */

namespace Drupal\link\Plugin\field\field_type;

use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'link' field type.
 *
 * @FieldType(
 *   id = "link",
 *   label = @Translation("Link"),
 *   description = @Translation("Stores a URL string, optional varchar link text, and optional blob of attributes to assemble a link."),
 *   instance_settings = {
 *     "title" = "1"
 *   },
 *   default_widget = "link_default",
 *   default_formatter = "link"
 * )
 */
class LinkItem extends ConfigFieldItemBase {

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
      static::$propertyDefinitions['url'] = array(
        'type' => 'uri',
        'label' => t('URL'),
      );
      static::$propertyDefinitions['title'] = array(
        'type' => 'string',
        'label' => t('Link text'),
      );
      static::$propertyDefinitions['attributes'] = array(
        'type' => 'map',
        'label' => t('Attributes'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'url' => array(
          'description' => 'The URL of the link.',
          'type' => 'varchar',
          'length' => 2048,
          'not null' => FALSE,
        ),
        'title' => array(
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'attributes' => array(
          'description' => 'Serialized array of attributes for the link.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    $element['title'] = array(
      '#type' => 'radios',
      '#title' => t('Allow link text'),
      '#default_value' => $this->getFieldSetting('title'),
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
  public function preSave() {
    $item = $this->getValue();
    // Trim any spaces around the URL and link text.
    $this->url = trim($this->url);
    $this->title = trim($this->title);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }

}
