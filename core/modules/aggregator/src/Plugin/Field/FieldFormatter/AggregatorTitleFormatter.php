<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Field\FieldFormatter\AggregatorTitleFormatter.
 */

namespace Drupal\aggregator\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'aggregator_title' formatter.
 *
 * @FieldFormatter(
 *   id = "aggregator_title",
 *   label = @Translation("Aggregator title"),
 *   description = @Translation("Formats an aggregator item or feed title with an optional link."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class AggregatorTitleFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['display_as_link'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['display_as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to URL'),
      '#default_value' => $this->getSetting('display_as_link'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($items->getEntity()->getEntityTypeId() == 'aggregator_feed') {
      $url_string = $items->getEntity()->getUrl();
    }
    else {
      $url_string = $items->getEntity()->getLink();
    }

    foreach ($items as $delta => $item) {
        if ($this->getSetting('display_as_link') && $url_string) {
          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $item->value,
            '#url' => Url::fromUri($url_string),
          ];
        }
        else {
          $elements[$delta] = ['#markup' => $item->value];
        }
      }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return (($field_definition->getTargetEntityTypeId() === 'aggregator_item' || $field_definition->getTargetEntityTypeId() === 'aggregator_feed') && $field_definition->getName() === 'title');
  }

}
