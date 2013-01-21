<?php

/**
 * @file
 * Contains \Drupal\telephone\Plugin\field\formatter\TelephoneLinkFormatter.
 */

namespace Drupal\telephone\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 *
 * @Plugin(
 *   id = "telephone_link",
 *   module = "telephone",
 *   label = @Translation("Telephone link"),
 *   field_types = {
 *     "telephone"
 *   },
 *   settings = {
 *     "title" = ""
 *   }
 * )
 */
class TelephoneLinkFormatter extends FormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title to replace basic numeric telephone number display.'),
      '#default_value' => $this->getSetting('title'),
    );

    return $elements;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsSummary().
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    if (!empty($settings['title'])) {
      $summary = t('Link using text: @title', array('@title' => $settings['title']));
    }
    else {
      $summary = t('Link using provided telephone number.');
    }

    return $summary;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::prepareView().
   */
  public function prepareView(array $entities, $langcode, array &$items) {
    $settings = $this->getSettings();

    foreach ($entities as $id => $entity) {
      foreach ($items[$id] as &$item) {
        // If available, set custom link text.
        if (!empty($settings['title'])) {
          $item['title'] = $settings['title'];
        }
        // Otherwise, use telephone number itself as title.
        else {
          $item['title'] = $item['value'];
        }
      }
    }
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $element = array();

    foreach ($items as $delta => $item) {
      // Prepend 'tel:' to the telephone number.
      $href = 'tel:' . rawurlencode(preg_replace('/\s+/', '', $item['value']));

      // Render each element as link.
      $element[$delta] = array(
        '#type' => 'link',
        '#title' => $item['title'],
        '#href' => $href,
        '#options' => array('external' => TRUE),
      );
    }

    return $element;
  }
}
