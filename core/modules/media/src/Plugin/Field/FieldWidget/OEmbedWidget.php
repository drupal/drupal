<?php

namespace Drupal\media\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\OEmbedInterface;

/**
 * Plugin implementation of the 'oembed_textfield' widget.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 *
 * @FieldWidget(
 *   id = "oembed_textfield",
 *   label = @Translation("oEmbed URL"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class OEmbedWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\media\Plugin\media\Source\OEmbedInterface $source */
    $source = $items->getEntity()->getSource();
    $message = $this->t('You can link to media from the following services: @providers', ['@providers' => implode(', ', $source->getProviders())]);

    if (!empty($element['value']['#description'])) {
      $element['value']['#description'] = [
        '#theme' => 'item_list',
        '#items' => [$element['value']['#description'], $message],
      ];
    }
    else {
      $element['value']['#description'] = $message;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_bundle = $field_definition->getTargetBundle();

    if (!parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media' || !$target_bundle) {
      return FALSE;
    }
    return MediaType::load($target_bundle)->getSource() instanceof OEmbedInterface;
  }

}
