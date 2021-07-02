<?php

namespace Drupal\media_library\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements TrustedCallbacks for media_library.
 *
 * @package Drupal\media_library\Form
 */
class MediaLibraryTrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #after_build callback for media_library_form_alter().
   */
  public static function afterBuildViewsExposedForm(array &$form, FormStateInterface $form_state) {
    // Remove .form-actions from the view's exposed filter actions. This
    // prevents the "Apply filters" submit button from being moved into the
    // dialog's button area.
    // @see \Drupal\Core\Render\Element\Actions::processActions
    // @see Drupal.behaviors.dialog.prepareDialogButtons
    // @todo Remove this after
    //   https://www.drupal.org/project/drupal/issues/3089751 is fixed.
    if (($key = array_search('form-actions', $form['actions']['#attributes']['class'])) !== FALSE) {
      unset($form['actions']['#attributes']['class'][$key]);
    }
    return $form;
  }

  /**
   * Implements #validate callback for media_library.module alter hooks.
   */
  public static function filterFormatEditFormValidate($form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] !== 'op') {
      return;
    }

    // The "DrupalMediaLibrary" button is for the CKEditor text editor.
    if ($form_state->getValue(['editor', 'editor']) !== 'ckeditor') {
      return;
    }

    $button_group_path = [
      'editor',
      'settings',
      'toolbar',
      'button_groups',
    ];

    if ($button_groups = $form_state->getValue($button_group_path)) {
      $buttons = [];
      $button_groups = Json::decode($button_groups);

      foreach ($button_groups as $button_row) {
        foreach ($button_row as $button_group) {
          $buttons = array_merge($buttons, array_values($button_group['items']));
        }
      }

      $get_filter_label = function ($filter_plugin_id) use ($form) {
        return (string) $form['filters']['order'][$filter_plugin_id]['filter']['#markup'];
      };

      if (in_array('DrupalMediaLibrary', $buttons, TRUE)) {
        $media_embed_enabled = $form_state->getValue([
          'filters',
          'media_embed',
          'status',
        ]);

        if (!$media_embed_enabled) {
          $error_message = new TranslatableMarkup('The %media-embed-filter-label filter must be enabled to use the %drupal-media-library-button button.', [
            '%media-embed-filter-label' => $get_filter_label('media_embed'),
            '%drupal-media-library-button' => new TranslatableMarkup('Insert from Media Library'),
          ]);
          $form_state->setErrorByName('filters', $error_message);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'afterBuildViewsExposedForm',
      'filterFormatEditFormValidate',
    ];
  }

}
