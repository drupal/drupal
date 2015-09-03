<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "string",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class StringFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entity_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_entity'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $entity_type = $this->entityManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the @entity_label', ['@entity_label' => $entity_type->getLabel()]),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('link_to_entity')) {
      $entity_type = $this->entityManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());
      $summary[] = $this->t('Linked to the @entity_label', ['@entity_label' => $entity_type->getLabel()]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $url = NULL;
    if ($this->getSetting('link_to_entity')) {
      // For the default revision this falls back to 'canonical'
      $url = $items->getEntity()->urlInfo('revision');
    }

    foreach ($items as $delta => $item) {
      $view_value = $this->viewValue($item);
      if ($url) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $view_value,
          '#url' => $url,
        ];
      }
      else {
        $elements[$delta] = is_array($view_value) ? $view_value : ['#markup' => $view_value];
      }
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#markup' => nl2br(Html::escape($item->value))
    ];
  }

}
