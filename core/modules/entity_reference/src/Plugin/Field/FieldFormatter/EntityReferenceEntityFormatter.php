<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference\RecursiveRenderingException;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_view",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceEntityFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'view_mode' => 'default',
      'link' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = array(
      '#type' => 'select',
      '#options' => \Drupal::entityManager()->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    );

    $elements['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show links'),
      '#default_value' => $this->getSetting('links'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $view_modes = \Drupal::entityManager()->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = t('Rendered as @mode', array('@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode));
    $summary[] = $this->getSetting('links') ? t('Display links') : t('Do not display links');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $view_mode = $this->getSetting('view_mode');
    $links = $this->getSetting('links');

    $target_type = $this->getFieldSetting('target_type');

    $elements = array();

    foreach ($items as $delta => $item) {
      if (!$item->access) {
        // User doesn't have access to the referenced entity.
        continue;
      }
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        throw new RecursiveRenderingException(format_string('Recursive rendering detected when rendering entity @entity_type(@entity_id). Aborting rendering.', array('@entity_type' => $item->entity->getEntityTypeId(), '@entity_id' => $item->target_id)));
      }

      if (!empty($item->target_id)) {
        // The viewElements() method of entity field formatters is run
        // during the #pre_render phase of rendering an entity. A formatter
        // builds the content of the field in preparation for theming.
        // All entity cache tags must be available after the #pre_render phase.
        // This field formatter is highly exceptional: it renders *another*
        // entity and this referenced entity has its own #pre_render
        // callbacks. In order collect the cache tags associated with the
        // referenced entity it must be passed to drupal_render() so that its
        // #pre_render callbacks are invoked and its full build array is
        // assembled. Rendering the referenced entity in place here will allow
        // its cache tags to be bubbled up and included with those of the
        // main entity when cache tags are collected for a renderable array
        // in drupal_render().
        // @todo remove this work-around, see https://drupal.org/node/2273277
        $referenced_entity_build = entity_view($item->entity, $view_mode, $item->getLangcode());
        drupal_render($referenced_entity_build, TRUE);
        $elements[$delta] = $referenced_entity_build;

        if (empty($links) && isset($result[$delta][$target_type][$item->target_id]['links'])) {
          // Hide the element links.
          $elements[$delta][$target_type][$item->target_id]['links']['#access'] = FALSE;
        }
        // Add a resource attribute to set the mapping property's value to the
        // entity's url. Since we don't know what the markup of the entity will
        // be, we shouldn't rely on it for structured data such as RDFa.
        if (!empty($item->_attributes)) {
          $item->_attributes += array('resource' => $item->entity->url());
        }
      }
      else {
        // This is an "auto_create" item.
        $elements[$delta] = array('#markup' => $item->entity->label());
      }
      $depth = 0;
    }

    return $elements;
  }

}
