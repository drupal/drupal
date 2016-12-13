<?php

namespace Drupal\content_moderation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'content_moderation_state' formatter.
 *
 * @FieldFormatter(
 *   id = "content_moderation_state",
 *   label = @Translation("Content moderation state"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class ContentModerationStateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $entity = $items->getEntity();
    $workflow = $entity->workflow->entity;
    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#markup' => $workflow->getState($item->value)->label(),
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === 'moderation_state';
  }

}
