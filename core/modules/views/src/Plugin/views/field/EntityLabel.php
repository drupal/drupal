<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display entity label optionally linked to entity page.
 */
#[ViewsField("entity_label")]
class EntityLabel extends FieldPluginBase {

  /**
   * Array of entities that reference to file.
   *
   * @var array
   */
  protected $loadedReferencers = [];

  /**
   * EntityTypeManager class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityLabel object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields[$this->definition['entity type field']] = $this->definition['entity type field'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_entity'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity'] = [
      '#title' => $this->t('Link to entity'),
      '#description' => $this->t('Make entity label a link to entity page.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_entity']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $type = $this->getValue($values, $this->definition['entity type field']);
    $value = $this->getValue($values);

    if (empty($this->loadedReferencers[$type][$value])) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->loadedReferencers[$type][$value];

    if (!empty($this->options['link_to_entity'])) {
      try {
        $this->options['alter']['url'] = $entity->toUrl();
        $this->options['alter']['make_link'] = TRUE;
      }
      catch (UndefinedLinkTemplateException) {
        $this->options['alter']['make_link'] = FALSE;
      }
      catch (EntityMalformedException) {
        $this->options['alter']['make_link'] = FALSE;
      }
    }

    return $this->sanitizeValue($entity->label());
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    $entity_ids_per_type = [];
    foreach ($values as $value) {
      if ($type = $this->getValue($value, 'type')) {
        $entity_ids_per_type[$type][] = $this->getValue($value);
      }
    }

    foreach ($entity_ids_per_type as $type => $ids) {
      $this->loadedReferencers[$type] = $this->entityTypeManager->getStorage($type)->loadMultiple($ids);
    }
  }

}
