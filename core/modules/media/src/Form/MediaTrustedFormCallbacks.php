<?php

namespace Drupal\media\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements Trusted Callbacks for media module.
 *
 * @package Drupal\media\Form
 */
class MediaTrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #validate callback for media.module alter hooks.
   */
  public static function filterFormatEditFormValidate($form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] !== 'op') {
      return;
    }

    $allowed_html_path = [
      'filters',
      'filter_html',
      'settings',
      'allowed_html',
    ];

    $filter_html_settings_path = [
      'filters',
      'filter_html',
      'settings',
    ];

    $filter_html_enabled = $form_state->getValue([
      'filters',
      'filter_html',
      'status',
    ]);

    $media_embed_enabled = $form_state->getValue([
      'filters',
      'media_embed',
      'status',
    ]);

    if (!$media_embed_enabled) {
      return;
    }

    $get_filter_label = function ($filter_plugin_id) use ($form) {
      return (string) $form['filters']['order'][$filter_plugin_id]['filter']['#markup'];
    };

    if ($filter_html_enabled && $form_state->getValue($allowed_html_path)) {
      /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
      $filter_format = $form_state->getFormObject()->getEntity();

      $filter_html = clone $filter_format->filters()->get('filter_html');
      $filter_html->setConfiguration(['settings' => $form_state->getValue($filter_html_settings_path)]);
      $restrictions = $filter_html->getHTMLRestrictions();
      $allowed = $restrictions['allowed'];

      // Require `<drupal-media>` HTML tag if filter_html is enabled.
      if (!isset($allowed['drupal-media'])) {
        $form_state->setError($form['filters']['settings']['filter_html']['allowed_html'], t('The %media-embed-filter-label filter requires <code>&lt;drupal-media&gt;</code> among the allowed HTML tags.', [
          '%media-embed-filter-label' => $get_filter_label('media_embed'),
        ]));
      }
      else {
        $required_attributes = [
          'data-entity-type',
          'data-entity-uuid',
        ];

        // If there are no attributes, the allowed item is set to FALSE,
        // otherwise, it is set to an array.
        if ($allowed['drupal-media'] === FALSE) {
          $missing_attributes = $required_attributes;
        }
        else {
          $missing_attributes = array_diff($required_attributes, array_keys($allowed['drupal-media']));
        }

        if ($missing_attributes) {
          $form_state->setError($form['filters']['settings']['filter_html']['allowed_html'], t('The <code>&lt;drupal-media&gt;</code> tag in the allowed HTML tags is missing the following attributes: <code>%list</code>.', [
            '%list' => implode(', ', $missing_attributes),
          ]));
        }
      }
    }

    $filters = $form_state->getValue('filters');

    // The "media_embed" filter must run after "filter_align", "filter_caption",
    // and "filter_html_image_secure".
    $precedents = [
      'filter_align',
      'filter_caption',
      'filter_html_image_secure',
    ];

    $error_filters = [];
    foreach ($precedents as $filter_name) {
      // A filter that should run before media embed filter.
      $precedent = $filters[$filter_name];

      if (empty($precedent['status']) || !isset($precedent['weight'])) {
        continue;
      }

      if ($precedent['weight'] >= $filters['media_embed']['weight']) {
        $error_filters[$filter_name] = $get_filter_label($filter_name);
      }
    }

    if (!empty($error_filters)) {
      $error_message = \Drupal::translation()->formatPlural(
        count($error_filters),
        'The %media-embed-filter-label filter needs to be placed after the %filter filter.',
        'The %media-embed-filter-label filter needs to be placed after the following filters: %filters.',
        [
          '%media-embed-filter-label' => $get_filter_label('media_embed'),
          '%filter' => reset($error_filters),
          '%filters' => implode(', ', $error_filters),
        ]
      );

      $form_state->setErrorByName('filters', $error_message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['filterFormatEditFormValidate'];
  }

}
