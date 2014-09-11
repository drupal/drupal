<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Field\FieldType\LinkItem.
 */

namespace Drupal\link\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
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
 *   constraints = {"LinkType" = {}}
 * )
 */
class LinkItem extends FieldItemBase implements LinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultInstanceSettings() {
    return array(
      'title' => DRUPAL_OPTIONAL,
      'link_type' => LinkItemInterface::LINK_GENERIC
    ) + parent::defaultInstanceSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(t('URL'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Link text'));

    $properties['route_name'] = DataDefinition::create('string')
      ->setLabel(t('Route name'));

    $properties['route_parameters'] = MapDataDefinition::create()
      ->setLabel(t('Route parameters'));

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
        'route_name' => array(
          'description' => 'The machine name of a defined Route this link represents.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'route_parameters' => array(
          'description' => 'Serialized array of route parameters of the link.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
        'options' => array(
          'description' => 'Serialized array of options for the link.',
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
  public function instanceSettingsForm(array $form, FormStateInterface $form_state) {
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
    $values['url'] = 'http://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (sizeof($tlds)-1))];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    // External links don't have a route_name value.
    return empty($this->route_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'url';
  }
}
