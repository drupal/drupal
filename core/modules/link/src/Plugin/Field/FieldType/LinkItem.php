<?php

namespace Drupal\link\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
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
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class LinkItem extends FieldItemBase implements LinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'title' => DRUPAL_OPTIONAL,
      'link_type' => LinkItemInterface::LINK_GENERIC,
    ] + parent::defaultFieldSettings();
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
    return [
      'columns' => [
        'uri' => [
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ],
        'title' => [
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'options' => [
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'uri' => [['uri', 30]],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['link_type'] = [
      '#type' => 'radios',
      '#title' => t('Allowed link type'),
      '#default_value' => $this->getSetting('link_type'),
      '#options' => [
        static::LINK_INTERNAL => t('Internal links only'),
        static::LINK_EXTERNAL => t('External links only'),
        static::LINK_GENERIC => t('Both internal and external links'),
      ],
    ];

    $element['title'] = [
      '#type' => 'radios',
      '#title' => t('Allow link text'),
      '#default_value' => $this->getSetting('title'),
      '#options' => [
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    if ($field_definition->getItemDefinition()->getSetting('link_type') & LinkItemInterface::LINK_EXTERNAL) {
      // Set of possible top-level domains.
      $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
      // Set random length for the domain name.
      $domain_length = mt_rand(7, 15);

      switch ($field_definition->getSetting('title')) {
        case DRUPAL_DISABLED:
          $values['title'] = '';
          break;
        case DRUPAL_REQUIRED:
          $values['title'] = $random->sentences(4);
          break;
        case DRUPAL_OPTIONAL:
          // In case of optional title, randomize its generation.
          $values['title'] = mt_rand(0, 1) ? $random->sentences(4) : '';
          break;
      }
      $values['uri'] = 'http://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (count($tlds) - 1))];
    }
    else {
      $values['uri'] = 'base:' . $random->name(mt_rand(1, 64));
    }
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
    return Url::fromUri($this->uri, (array) $this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'options' => [],
      ];
    }
    // Unserialize the values, this is deprecated as the storage takes care of
    // this, options must not be passed as a string anymore.
    if (is_string($values['options'])) {
      @trigger_error('Support for passing options as a serialized string is deprecated in 8.7.0 and will be removed before Drupal 9.0.0. Pass them as an array instead. See https://www.drupal.org/node/2961643.', E_USER_DEPRECATED);
      if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
        $values['options'] = unserialize($values['options'], ['allowed_classes' => FALSE]);
      }
      else {
        $values['options'] = unserialize($values['options']);
      }
    }
    parent::setValue($values, $notify);
  }

}
