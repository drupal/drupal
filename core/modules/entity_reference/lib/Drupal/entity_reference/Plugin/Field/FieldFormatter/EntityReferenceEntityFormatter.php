<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
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
 *   },
 *   settings = {
 *     "view_mode" = "default",
 *     "link" = FALSE
 *   }
 * )
 */
class EntityReferenceEntityFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $view_modes = entity_get_view_modes($this->getFieldSetting('target_type'));
    $options = array('default' => t('Default'));
    foreach ($view_modes as $view_mode => $view_mode_settings) {
      $options[$view_mode] = $view_mode_settings['label'];
    }

    $elements['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
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

    $view_modes = entity_get_view_modes($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    if ($view_mode == 'default') {
      $view_mode = t('Default');
    }
    $summary[] = t('Rendered as @mode', array('@mode' => isset($view_modes[$view_mode]['label']) ? $view_modes[$view_mode]['label'] : $view_mode));
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
        $entity = clone $item->entity;
        unset($entity->content);
        $elements[$delta] = entity_view($entity, $view_mode, $item->getLangcode());

        if (empty($links) && isset($result[$delta][$target_type][$item->target_id]['links'])) {
          // Hide the element links.
          $elements[$delta][$target_type][$item->target_id]['links']['#access'] = FALSE;
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
