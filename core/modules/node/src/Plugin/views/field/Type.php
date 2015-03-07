<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Type.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_type")
 */
class Type extends Node {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * Constructs a new Node Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->nodeTypeStorage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_manager->getStorage('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide machine_name option for to node type display.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => $this->t('Output machine name'),
      '#description' => $this->t('Display field as the content type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
   * Render node type as human readable name, unless using machine_name option.
   */
  function render_name($data, $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      $type = $this->nodeTypeStorage->load($data);
      return $type ? $this->t($this->sanitizeValue($type->label())) : '';
    }
    return $this->sanitizeValue($data);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->render_name($value, $values), $values);
  }

}
