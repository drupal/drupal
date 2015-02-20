<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Field\FieldType\LinkItem.
 */

namespace Drupal\link\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'link' field type.
 *
 * @FieldType(
 *   id = "link",
 *   label = @Translation("Link"),
 *   description = @Translation("Stores a URL string, optional varchar link text, and optional blob of attributes to assemble a link."),
 *   default_widget = "link_default",
 *   default_formatter = "link",
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}}
 * )
 */
class LinkItem extends FieldItemBase implements LinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'title' => DRUPAL_OPTIONAL,
      'link_type' => LinkItemInterface::LINK_GENERIC
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uri'] = DataDefinition::create('uri')
      ->setLabel(t('URI'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Link text'));

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Options'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'uri' => array(
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ),
        'title' => array(
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ),
        'options' => array(
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ),
      ),
      'indexes' => array(
        'uri' => array(array('uri', 30)),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $element['link_type'] = array(
      '#type' => 'radios',
      '#title' => t('Allowed link type'),
      '#default_value' => $this->getSetting('link_type'),
      '#options' => array(
        static::LINK_INTERNAL => t('Internal links only'),
        static::LINK_EXTERNAL => t('External links only'),
        static::LINK_GENERIC => t('Both internal and external links'),
      ),
    );

    $element['title'] = array(
      '#type' => 'radios',
      '#title' => t('Allow link text'),
      '#default_value' => $this->getSetting('title'),
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
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Set of possible top-level domains.
    $tlds = array('com', 'net', 'gov', 'org', 'edu', 'biz', 'info');
    // Set random length for the domain name.
    $domain_length = mt_rand(7, 15);
    $random = new Random();

    switch ($field_definition->getSetting('title')) {
      case DRUPAL_DISABLED:
        $values['title'] = '';
        break;
      case DRUPAL_REQUIRED:
        $values['title'] = $random->sentences(4);
        break;
      case DRUPAL_OPTIONAL:
        // In case of optional title, randomize its generation.
        $values['title'] = mt_rand(0,1) ? $random->sentences(4) : '';
        break;
    }
    $values['uri'] = 'http://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (sizeof($tlds)-1))];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('uri')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return $this->getUrl()->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'uri';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri($this->uri);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   SqlContentEntityStorage::loadFieldItems, see
    //   https://www.drupal.org/node/2414835
    if (isset($values['options']) && is_string($values['options'])) {
      $values['options'] = unserialize($values['options']);
    }
    parent::setValue($values, $notify);
  }

}
