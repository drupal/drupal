<?php

namespace Drupal\file\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;

// cspell:ignore filefield

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 */
#[FormElement('managed_file')]
class ManagedFile extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [static::class, 'processManagedFile'],
      ],
      '#element_validate' => [
        [static::class, 'validateManagedFile'],
      ],
      '#pre_render' => [
        [static::class, 'preRenderManagedFile'],
      ],
      '#theme' => 'file_managed_file',
      '#theme_wrappers' => ['form_element'],
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#upload_validators' => [],
      '#upload_location' => NULL,
      '#size' => 22,
      '#multiple' => FALSE,
      '#extended' => FALSE,
      '#attached' => [
        'library' => ['file/drupal.file'],
      ],
      '#accept' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Find the current value of this field.
    $fids = !empty($input['fids']) ? explode(' ', $input['fids']) : [];
    foreach ($fids as $key => $fid) {
      $fids[$key] = (int) $fid;
    }
    $force_default = FALSE;

    // Process any input and save new uploads.
    if ($input !== FALSE) {
      $input['fids'] = $fids;
      $return = $input;

      // Uploads take priority over all other values.
      if ($files = file_managed_file_save_upload($element, $form_state)) {
        if ($element['#multiple']) {
          $fids = array_merge($fids, array_keys($files));
        }
        else {
          $fids = array_keys($files);
        }
      }
      else {
        // Check for #filefield_value_callback values.
        // Because FAPI does not allow multiple #value_callback values like it
        // does for #element_validate and #process, this fills the missing
        // functionality to allow File fields to be extended through FAPI.
        if (isset($element['#file_value_callbacks'])) {
          foreach ($element['#file_value_callbacks'] as $callback) {
            $callback($element, $input, $form_state);
          }
        }

        // Load files if the FIDs have changed to confirm they exist.
        if (!empty($input['fids'])) {
          $fids = [];
          foreach ($input['fids'] as $fid) {
            if ($file = File::load($fid)) {
              $fids[] = $file->id();
              if (!$file->access('download')) {
                $force_default = TRUE;
                break;
              }
              // Temporary files that belong to other users should never be
              // allowed.
              if ($file->isTemporary()) {
                if ($file->getOwnerId() != \Drupal::currentUser()->id()) {
                  $force_default = TRUE;
                  break;
                }
                // Since file ownership can't be determined for anonymous users,
                // they are not allowed to reuse temporary files at all. But
                // they do need to be able to reuse their own files from earlier
                // submissions of the same form, so to allow that, check for the
                // token added by $this->processManagedFile().
                elseif (\Drupal::currentUser()->isAnonymous()) {
                  $token = NestedArray::getValue($form_state->getUserInput(), array_merge($element['#parents'], ['file_' . $file->id(), 'fid_token']));
                  $file_hmac = Crypt::hmacBase64('file-' . $file->id(), \Drupal::service('private_key')->get() . Settings::getHashSalt());
                  if ($token === NULL || !hash_equals($file_hmac, $token)) {
                    $force_default = TRUE;
                    break;
                  }
                }
              }
            }
          }
          if ($force_default) {
            $fids = [];
          }
        }
      }
    }

    // If there is no input or if the default value was requested above, use the
    // default value.
    if ($input === FALSE || $force_default) {
      if ($element['#extended']) {
        $default_fids = $element['#default_value']['fids'] ?? [];
        $return = $element['#default_value'] ?? ['fids' => []];
      }
      else {
        $default_fids = $element['#default_value'] ?? [];
        $return = ['fids' => []];
      }

      // Confirm that the file exists when used as a default value.
      if (!empty($default_fids)) {
        $fids = [];
        foreach ($default_fids as $fid) {
          if ($file = File::load($fid)) {
            $fids[] = $file->id();
          }
        }
      }
    }

    $return['fids'] = $fids;
    return $return;
  }

  /**
   * The #ajax callback for managed_file upload forms.
   *
   * This ajax callback takes care of the following things:
   *   - Ensures that broken requests due to too big files are caught.
   *   - Adds a class to the response to be able to highlight in the UI, that a
   *     new file got uploaded.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    // Add the special AJAX class if a new file was added.
    $current_file_count = $form_state->get('file_upload_delta_initial');
    if (isset($form['#file_upload_delta']) && $current_file_count < $form['#file_upload_delta']) {
      $form[$current_file_count]['#attributes']['class'][] = 'ajax-new-content';
    }

    $status_messages = ['#type' => 'status_messages'];
    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

  /**
   * Render API callback: Expands the managed_file element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   */
  public static function processManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {

    // This is used sometimes so let's implode it just once.
    $parents_prefix = implode('_', $element['#parents']);

    $fids = $element['#value']['fids'] ?? [];

    // Set some default element properties.
    $element['#progress_indicator'] = empty($element['#progress_indicator']) ? 'none' : $element['#progress_indicator'];
    $element['#files'] = !empty($fids) ? File::loadMultiple($fids) : [];
    $element['#tree'] = TRUE;

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    $ajax_settings = [
      'callback' => [static::class, 'uploadAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => $element['#progress_indicator'],
        'message' => $element['#progress_message'],
      ],
    ];

    // Set up the buttons first since we need to check if they were clicked.
    $element['upload_button'] = [
      '#name' => $parents_prefix . '_upload_button',
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => [[static::class, 'submit']],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => -5,
    ];

    // Force the progress indicator for the remove button to be either 'none' or
    // 'throbber', even if the upload button is using something else.
    $ajax_settings['progress']['type'] = ($element['#progress_indicator'] == 'none') ? 'none' : 'throbber';
    $ajax_settings['progress']['message'] = NULL;
    $ajax_settings['effect'] = 'none';
    $element['remove_button'] = [
      '#name' => $parents_prefix . '_remove_button',
      '#type' => 'submit',
      '#value' => $element['#multiple'] ? t('Remove selected') : t('Remove'),
      '#validate' => [],
      '#submit' => [[static::class, 'submit']],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => 1,
    ];

    $element['fids'] = [
      '#type' => 'hidden',
      '#value' => $fids,
    ];

    // Add progress bar support to the upload if possible.
    if ($element['#progress_indicator'] == 'bar' && extension_loaded('uploadprogress')) {
      $upload_progress_key = mt_rand();

      $element['UPLOAD_IDENTIFIER'] = [
        '#type' => 'hidden',
        '#value' => $upload_progress_key,
        '#attributes' => ['class' => ['file-progress']],
        // Uploadprogress extension requires this field to be at the top of
        // the form.
        '#weight' => -20,
      ];

      // Add the upload progress callback.
      $element['upload_button']['#ajax']['progress']['url'] = Url::fromRoute('file.ajax_progress', ['key' => $upload_progress_key]);

      // Set a custom submit event so we can modify the upload progress
      // identifier element before the form gets submitted.
      $element['upload_button']['#ajax']['event'] = 'fileUpload';
    }

    // Use a manually generated ID for the file upload field so the desired
    // field label can be associated with it below. Use the same method for
    // setting the ID that the form API autogenerator does.
    // @see \Drupal\Core\Form\FormBuilder::doBuildForm()
    $id = Html::getUniqueId('edit-' . implode('-', array_merge($element['#parents'], ['upload'])));

    // The file upload field itself.
    $element['upload'] = [
      '#name' => 'files[' . $parents_prefix . ']',
      '#type' => 'file',
      // This #title will not actually be used as the upload field's HTML label,
      // since the theme function for upload fields never passes the element
      // through theme('form_element'). Instead the parent element's #title is
      // used as the label (see below). That is usually a more meaningful label
      // anyway.
      '#title' => t('Choose a file'),
      '#title_display' => 'invisible',
      '#id' => $id,
      '#size' => $element['#size'],
      '#multiple' => $element['#multiple'],
      '#theme_wrappers' => [],
      '#weight' => -10,
      '#error_no_message' => TRUE,
    ];

    if (!empty($element['#description'])) {
      $element['upload']['#attributes']['aria-describedby'] = $element['#id'] . '--description';
    }

    if (!empty($element['#accept'])) {
      $element['upload']['#attributes']['accept'] = $element['#accept'];
    }

    // Indicate that $element['#title'] should be used as the HTML label for the
    // file upload field.
    $element['#label_for'] = $element['upload']['#id'];

    if (!empty($fids) && $element['#files']) {
      foreach ($element['#files'] as $delta => $file) {
        $file_link = [
          '#theme' => 'file_link',
          '#file' => $file,
        ];
        if ($element['#multiple']) {
          $element['file_' . $delta]['selected'] = [
            '#type' => 'checkbox',
            '#title' => \Drupal::service('renderer')->renderInIsolation($file_link),
          ];
        }
        else {
          $element['file_' . $delta]['filename'] = $file_link + ['#weight' => -10];
        }
        // Anonymous users who have uploaded a temporary file need a
        // non-session-based token added so $this->valueCallback() can check
        // that they have permission to use this file on subsequent submissions
        // of the same form (for example, after an Ajax upload or form
        // validation error).
        if ($file->isTemporary() && \Drupal::currentUser()->isAnonymous()) {
          $element['file_' . $delta]['fid_token'] = [
            '#type' => 'hidden',
            '#value' => Crypt::hmacBase64('file-' . $delta, \Drupal::service('private_key')->get() . Settings::getHashSalt()),
          ];
        }
      }
    }

    // Add the extension list to the page as JavaScript settings.
    if (isset($element['#upload_validators']['FileExtension']['extensions'])) {
      $allowed_extensions = $element['#upload_validators']['FileExtension']['extensions'];
      $extension_list = implode(',', array_filter(explode(' ', $allowed_extensions)));
      $element['upload']['#attached']['drupalSettings']['file']['elements']['#' . $id] = $extension_list;
    }

    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Render API callback: Hides display of the upload or remove controls.
   *
   * Upload controls are hidden when a file is already uploaded. Remove controls
   * are hidden when there is no file attached. Controls are hidden here instead
   * of in \Drupal\file\Element\ManagedFile::processManagedFile(), because
   * #access for these buttons depends on the managed_file element's #value. See
   * the documentation of \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   * for more detailed information about the relationship between #process,
   * #value, and #access.
   *
   * Because #access is set here, it affects display only and does not prevent
   * JavaScript or other untrusted code from submitting the form as though
   * access were enabled. The form processing functions for these elements
   * should not assume that the buttons can't be "clicked" just because they are
   * not displayed.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   * @see \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   */
  public static function preRenderManagedFile($element) {
    // If we already have a file, we don't want to show the upload controls.
    if (!empty($element['#value']['fids'])) {
      if (!$element['#multiple']) {
        $element['upload']['#access'] = FALSE;
        $element['upload_button']['#access'] = FALSE;
      }
    }
    // If we don't already have a file, there is nothing to remove.
    else {
      $element['remove_button']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Render API callback: Validates the managed_file element.
   */
  public static function validateManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    $clicked_button = isset($triggering_element['#parents']) ? end($triggering_element['#parents']) : '';
    if ($clicked_button != 'remove_button' && !empty($element['fids']['#value'])) {
      $fids = $element['fids']['#value'];
      foreach ($fids as $fid) {
        if ($file = File::load($fid)) {
          // If referencing an existing file, only allow if there are existing
          // references. This prevents unmanaged files from being deleted if
          // this item were to be deleted. When files that are no longer in use
          // are automatically marked as temporary (now disabled by default),
          // it is not safe to reference a permanent file without usage. Adding
          // a usage and then later on removing it again would delete the file,
          // but it is unknown if and where it is currently referenced. However,
          // when files are not marked temporary (and then removed)
          // automatically, it is safe to add and remove usages, as it would
          // simply return to the current state.
          // @see https://www.drupal.org/node/2891902
          if ($file->isPermanent() && \Drupal::config('file.settings')->get('make_unused_managed_files_temporary')) {
            $references = static::fileUsage()->listUsage($file);
            if (empty($references)) {
              // We expect the field name placeholder value to be wrapped in t()
              // here, so it won't be escaped again as it's already marked safe.
              $form_state->setError($element, t('The file used in the @name field may not be referenced.', ['@name' => $element['#title']]));
            }
          }
        }
        else {
          // We expect the field name placeholder value to be wrapped in t()
          // here, so it won't be escaped again as it's already marked safe.
          $form_state->setError($element, t('The file referenced by the @name field does not exist.', ['@name' => $element['#title']]));
        }
      }
    }

    // Check required property based on the FID.
    if ($element['#required'] && empty($element['fids']['#value']) && !in_array($clicked_button, ['upload_button', 'remove_button'])) {
      // We expect the field name placeholder value to be wrapped in t()
      // here, so it won't be escaped again as it's already marked safe.
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Consolidate the array value of this field to array of FIDs.
    if (!$element['#extended']) {
      $form_state->setValueForElement($element, $element['fids']['#value']);
    }
  }

  /**
   * Wraps the file usage service.
   *
   * @return \Drupal\file\FileUsage\FileUsageInterface
   *   The file usage service.
   */
  protected static function fileUsage() {
    return \Drupal::service('file.usage');
  }

  /**
   * Form submission handler for upload/remove buttons of managed_file elements.
   *
   * @param array<string,mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   */
  public static function submit(array $form, FormStateInterface $form_state): void {
    // Determine whether it was the upload or the remove button that was clicked
    // and set $element to the managed_file element that contains that button.
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    // No action is needed here for the upload button, because all file uploads
    // on the form are processed by
    // \Drupal\file\Element\ManagedFile::valueCallback() regardless of which
    // button was clicked. Action is needed here for the remove button, because
    // we only remove a file in response to its remove button being clicked.
    if ($button_key == 'remove_button') {
      $fids = array_keys($element['#files']);
      // Get files that will be removed.
      if ($element['#multiple']) {
        $remove_fids = [];
        foreach (Element::children($element) as $name) {
          if (str_starts_with($name, 'file_') && $element[$name]['selected']['#value']) {
            $remove_fids[] = (int) substr($name, 5);
          }
        }
        $fids = array_diff($fids, $remove_fids);
      }
      else {
        // If we deal with single upload element remove the file and set
        // element's value to empty array (file could not be removed from
        // element if we don't do that).
        $remove_fids = $fids;
        $fids = [];
      }

      foreach ($remove_fids as $fid) {
        // If it's a temporary file we can safely remove it immediately,
        // otherwise it's up to the implementing module to remove usages of
        // files to have them removed.
        if ($element['#files'][$fid] && $element['#files'][$fid]->isTemporary()) {
          $element['#files'][$fid]->delete();
        }
      }
      // Update both $form_state->getValues() and FormState::$input to reflect
      // that the file has been removed, so that the form is rebuilt correctly.
      // $form_state->getValues() must be updated in case additional submit
      // handlers run, and for form building functions that run during the
      // rebuild, such as when the managed_file element is part of a field
      // widget. FormState::$input must be updated so that
      // \Drupal\file\Element\ManagedFile::valueCallback() has correct
      // information during the rebuild.
      $form_state->setValueForElement($element['fids'], implode(' ', $fids));
      NestedArray::setValue($form_state->getUserInput(), $element['fids']['#parents'], implode(' ', $fids));
    }

    // Set the form to rebuild so that $form is correctly updated in response to
    // processing the file removal. Since this function did not change
    // $form_state if the upload button was clicked, a rebuild isn't necessary
    // in that situation and calling $form_state->disableRedirect() would
    // suffice. However, we choose to always rebuild, to keep the form
    // processing workflow consistent between the two buttons.
    $form_state->setRebuild();
  }

}
