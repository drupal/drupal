<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\field\formatter\LinkFormatter.
 */

namespace Drupal\link\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "link",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "trim_length" = "80",
 *     "url_only" = "",
 *     "url_plain" = "",
 *     "rel" = "",
 *     "target" = ""
 *   }
 * )
 */
class LinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['trim_length'] = array(
      '#type' => 'number',
      '#title' => t('Trim link text length'),
      '#field_suffix' => t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#description' => t('Leave blank to allow unlimited link text lengths.'),
    );
    $elements['url_only'] = array(
      '#type' => 'checkbox',
      '#title' => t('URL only'),
      '#default_value' => $this->getSetting('url_only'),
      '#access' => $this->getPluginId() == 'link',
    );
    $elements['url_plain'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show URL as plain text'),
      '#default_value' => $this->getSetting('url_plain'),
      '#access' => $this->getPluginId() == 'link',
      '#states' => array(
        'visible' => array(
          ':input[name*="url_only"]' => array('checked' => TRUE),
        ),
      ),
    );
    $elements['rel'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('rel'),
    );
    $elements['target'] = array(
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    if (!empty($settings['trim_length'])) {
      $summary[] = t('Link text trimmed to @limit characters', array('@limit' => $settings['trim_length']));
    }
    else {
      $summary[] = t('Link text not trimmed');
    }
    if ($this->getPluginId() == 'link' && !empty($settings['url_only'])) {
      if (!empty($settings['url_plain'])) {
        $summary[] = t('Show URL only as plain-text');
      }
      else {
        $summary[] = t('Show URL only');
      }
    }
    if (!empty($settings['rel'])) {
      $summary[] = t('Add rel="@rel"', array('@rel' => $settings['rel']));
    }
    if (!empty($settings['target'])) {
      $summary[] = t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $link_title = $item->url;

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because $options['html'] is FALSE
        // by default in l().
        $link_title = \Drupal::token()->replace($item->title, array($entity->getEntityTypeId() => $entity), array('sanitize' => FALSE, 'clear' => TRUE));
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length'])) {
        $link_title = truncate_utf8($link_title, $settings['trim_length'], FALSE, TRUE);
      }

      if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
        $element[$delta] = array(
          '#markup' => String::checkPlain($link_title),
        );
      }
      else {
        $link = $this->buildLink($item);
        $element[$delta] = array(
          '#type' => 'link',
          '#title' => $link_title,
          '#href' => $link['path'],
          '#options' => $link['options'],
        );
      }
    }

    return $element;
  }

  /**
   * Builds the link information for a link field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The link field item being rendered.
   *
   * @return array
   *   An array with the following key/value pairs:
   *   - 'path': a string suitable for the $path parameter in l().
   *   - 'options': an array suitable for the $options parameter in l().
   */
  protected function buildLink(FieldItemInterface $item) {
    $settings = $this->getSettings();

    // Split out the link into the parts required for url(): path and options.
    $parsed_url = UrlHelper::parse($item->url);
    $result = array(
      'path' => $parsed_url['path'],
      'options' => array(
        'query' => $parsed_url['query'],
        'fragment' => $parsed_url['fragment'],
        'attributes' => $item->attributes,
      ),
    );

    // Add optional 'rel' attribute to link options.
    if (!empty($settings['rel'])) {
      $result['options']['attributes']['rel'] = $settings['rel'];
    }
    // Add optional 'target' attribute to link options.
    if (!empty($settings['target'])) {
      $result['options']['attributes']['target'] = $settings['target'];
    }

    return $result;
  }

}

