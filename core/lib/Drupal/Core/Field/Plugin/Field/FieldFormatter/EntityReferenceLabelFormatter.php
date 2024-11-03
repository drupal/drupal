<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 */
#[FieldFormatter(
  id: 'entity_reference_label',
  label: new TranslatableMarkup('Label'),
  description: new TranslatableMarkup('Display the label of the referenced entities.'),
  field_types: [
    'entity_reference',
  ],
)]
class EntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = [
      '#title' => $this->t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? $this->t('Link to the referenced entity') : $this->t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $elements[$delta] = ['#entity' => $entity];
      $label = $entity->label();
      $cacheability = CacheableMetadata::createFromObject($entity);
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->toUrl();
        }
        catch (UndefinedLinkTemplateException) {
          // This exception is thrown by
          // \Drupal\Core\Entity\EntityInterface::toUrl() and it means that the
          // entity type doesn't have a link template nor a valid
          // "uri_callback", so don't bother trying to output a link for the
          // rest of the referenced entities.
          $elements[$delta]['#plain_text'] = $label;
          $cacheability->applyTo($elements[$delta]);
          continue;
        }

        $uri_access = $uri->access(return_as_object: TRUE);
        $cacheability->addCacheableDependency($uri_access);
        if ($uri_access->isAllowed()) {
          $elements[$delta] += [
            '#type' => 'link',
            '#title' => $label,
            '#url' => $uri,
            '#options' => $uri->getOptions(),
          ];

          if (!empty($items[$delta]->_attributes)) {
            $elements[$delta]['#options'] += ['attributes' => []];
            $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and shouldn't be rendered in the field template.
            unset($items[$delta]->_attributes);
          }
        }
        else {
          $elements[$delta]['#plain_text'] = $label;
        }
      }
      else {
        $elements[$delta]['#plain_text'] = $label;
      }

      $cacheability->applyTo($elements[$delta]);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
