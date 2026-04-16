<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'string' formatter.
 */
#[FieldFormatter(
  id: 'string',
  label: new TranslatableMarkup('Plain text'),
  field_types: [
    'string',
    'uri',
  ],
)]
class StringFormatter extends FormatterBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
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
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_entity'] = FALSE;
    $options['link_rel'] = 'canonical';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $entity_type = $this->entityTypeManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());

    $variables = [
      '@entity_label' => $entity_type->getLabel(),
    ];
    if ($entity_type->hasLinkTemplate('canonical')) {
      $form['link_to_entity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Link to the @entity_label', $variables),
        '#default_value' => $this->getSetting('link_to_entity'),
      ];

      $field_name = $this->fieldDefinition->getName();
      $form['link_rel'] = [
        '#type' => 'radios',
        '#title' => $this->t('Link destination'),
        '#options' => [
          'canonical' => $this->t('Link to view the @entity_label', $variables),
        ],
        // Only show this option when linking to the entity is enabled at all.
        '#states' => [
          'visible' => [
            'input[name="fields[' . $field_name . '][settings_edit_form][settings][link_to_entity]"]' => ['checked' => TRUE],
          ],
        ],
        '#default_value' => $this->getSetting('link_rel'),
        // No point in showing this option if there's only one link template.
        '#access' => FALSE,
      ];
      if ($entity_type->hasLinkTemplate('edit-form')) {
        $form['link_rel']['#options']['edit-form'] = $this->t('Link to edit the @entity_label', $variables);
        $form['link_rel']['#access'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('link_to_entity')) {
      $label = $this->entityTypeManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId())
        ->getSingularLabel();
      $variables = ['@entity_type' => $label];
      $link_rel = $this->getSetting('link_rel');
      $summary[] = match ($link_rel) {
        'canonical' => $this->t('Link to view the @entity_type', $variables),
        'edit-form' => $this->t('Link to edit the @entity_type', $variables),
        default => $this->t('Linked to @link_template', ['@link_template' => $link_rel]),
      };
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityType();

    $render_as_link = FALSE;
    if ($this->getSetting('link_to_entity') && !$entity->isNew() && $entity_type->hasLinkTemplate($this->getSetting('link_rel'))) {
      $url = $this->getEntityUrl($entity);
      $access = $url->access(return_as_object: TRUE);
      (new CacheableMetadata())
        ->addCacheableDependency($access)
        ->applyTo($elements);
      $render_as_link = $access->isAllowed();
    }

    foreach ($items as $delta => $item) {
      if ($render_as_link) {
        assert(isset($url));
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $this->viewValue($item),
          '#url' => $url,
        ];
      }
      else {
        $elements[$delta] = $this->viewValue($item);
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
      '#type' => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => $item->value],
    ];
  }

  /**
   * Gets the URI elements of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\Core\Url
   *   The URI elements of the entity.
   */
  protected function getEntityUrl(EntityInterface $entity) {
    // For the default revision, the 'revision' link template falls back to
    // 'canonical'.
    // @see \Drupal\Core\Entity\EntityBase::toUrl()
    $rel = $this->getSetting('link_rel');
    if ($rel === 'canonical' && $entity->getEntityType()->hasLinkTemplate('revision')) {
      $rel = 'revision';
    }
    return $entity->toUrl($rel);
  }

}
