<?php

declare(strict_types=1);

namespace Drupal\ajax_forms_test;

use Drupal\Core\Ajax\AddCssCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AnnounceCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple object for testing methods as Ajax callbacks.
 */
class Callbacks {

  /**
   * Ajax callback triggered by select.
   */
  public static function selectCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_selected_color', $form_state->getValue('select')));
    $response->addCommand(new DataCommand('#ajax_selected_color', 'form_state_value_select', $form_state->getValue('select')));
    return $response;
  }

  /**
   * Ajax callback triggered by date.
   */
  public static function dateCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $date = $form_state->getValue('date');
    $response->addCommand(new HtmlCommand('#ajax_date_value', sprintf('<div>%s</div>', $date)));
    $response->addCommand(new DataCommand('#ajax_date_value', 'form_state_value_date', $form_state->getValue('date')));
    return $response;
  }

  /**
   * Ajax callback triggered by datetime.
   */
  public static function datetimeCallback($form, FormStateInterface $form_state): AjaxResponse {
    $datetime = $form_state->getValue('datetime')['date'] . ' ' . $form_state->getValue('datetime')['time'];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_datetime_value', sprintf('<div>%s</div>', $datetime)));
    $response->addCommand(new DataCommand('#ajax_datetime_value', 'form_state_value_datetime', $datetime));
    return $response;
  }

  /**
   * Ajax callback triggered by checkbox.
   */
  public static function checkboxCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_checkbox_value', $form_state->getValue('checkbox') ? 'checked' : 'unchecked'));
    $response->addCommand(new DataCommand('#ajax_checkbox_value', 'form_state_value_select', (int) $form_state->getValue('checkbox')));
    return $response;
  }

  /**
   * Ajax callback to confirm image button was submitted.
   */
  public static function imageButtonCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax_image_button_result', "<div id='ajax-1-more-div'>Something witty!</div>"));
    return $response;
  }

  /**
   * Ajax callback triggered by the checkbox in a #group.
   */
  public static function checkboxGroupCallback($form, FormStateInterface $form_state): array {
    return $form['checkbox_in_group_wrapper'];
  }

  /**
   * Ajax form callback: Selects 'after'.
   */
  public static function advancedCommandsAfterCallback($form, FormStateInterface $form_state): AjaxResponse {
    $selector = '#after_div';

    $response = new AjaxResponse();
    $response->addCommand(new AfterCommand($selector, "This will be placed after"));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'alert'.
   */
  public static function advancedCommandsAlertCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new AlertCommand('Alert'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'announce' with no priority specified.
   */
  public static function advancedCommandsAnnounceCallback($form, FormStateInterface $form_state): AjaxResponse {
    return (new AjaxResponse())->addCommand(new AnnounceCommand('Default announcement.'));
  }

  /**
   * Ajax form callback: Selects 'announce' with 'polite' priority.
   */
  public static function advancedCommandsAnnouncePoliteCallback($form, FormStateInterface $form_state): AjaxResponse {
    return (new AjaxResponse())->addCommand(new AnnounceCommand('Polite announcement.', 'polite'));
  }

  /**
   * Ajax form callback: Selects 'announce' with 'assertive' priority.
   */
  public static function advancedCommandsAnnounceAssertiveCallback($form, FormStateInterface $form_state): AjaxResponse {
    return (new AjaxResponse())->addCommand(new AnnounceCommand('Assertive announcement.', 'assertive'));
  }

  /**
   * Ajax form callback: Selects 'announce' with two announce commands returned.
   */
  public static function advancedCommandsDoubleAnnounceCallback($form, FormStateInterface $form_state): AjaxResponse {
    return (new AjaxResponse())->addCommand(new AnnounceCommand('Assertive announcement.', 'assertive'))
      ->addCommand(new AnnounceCommand('Another announcement.'));
  }

  /**
   * Ajax form callback: Selects 'append'.
   */
  public static function advancedCommandsAppendCallback($form, FormStateInterface $form_state): AjaxResponse {
    $selector = '#append_div';
    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand($selector, "Appended text"));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'before'.
   */
  public static function advancedCommandsBeforeCallback($form, FormStateInterface $form_state): AjaxResponse {
    $selector = '#before_div';
    $response = new AjaxResponse();
    $response->addCommand(new BeforeCommand($selector, "Before text"));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'changed'.
   */
  public static function advancedCommandsChangedCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ChangedCommand('#changed_div'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'changed' with asterisk marking inner div.
   */
  public static function advancedCommandsChangedAsteriskCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ChangedCommand('#changed_div', '#changed_div_mark_this'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'css'.
   */
  public static function advancedCommandsCssCallback($form, FormStateInterface $form_state): AjaxResponse {
    $selector = '#css_div';
    $color = 'blue';

    $response = new AjaxResponse();
    $response->addCommand(new CssCommand($selector, ['background-color' => $color]));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'data'.
   */
  public static function advancedCommandsDataCallback($form, FormStateInterface $form_state): AjaxResponse {
    $selector = '#data_div';
    $response = new AjaxResponse();
    $response->addCommand(new DataCommand($selector, 'test_key', 'test_value'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'invoke'.
   */
  public static function advancedCommandsInvokeCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('#invoke_div', 'addClass', ['error']));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'html'.
   */
  public static function advancedCommandsHtmlCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#html_div', 'replacement text'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'insert'.
   */
  public static function advancedCommandsInsertCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand('#insert_div', 'insert replacement text'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'prepend'.
   */
  public static function advancedCommandsPrependCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new PrependCommand('#prepend_div', "prepended text"));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'remove'.
   */
  public static function advancedCommandsRemoveCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('#remove_text'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'restripe'.
   */
  public static function advancedCommandsRestripeCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new RestripeCommand('#restripe_table'));
    return $response;
  }

  /**
   * Ajax form callback: Selects 'settings'.
   */
  public static function advancedCommandsSettingsCallback($form, FormStateInterface $form_state): AjaxResponse {
    $setting['ajax_forms_test']['foo'] = 42;
    $response = new AjaxResponse();
    $response->addCommand(new SettingsCommand($setting));
    return $response;
  }

  /**
   * Ajax callback for 'add_css'.
   */
  public static function advancedCommandsAddCssCallback($form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new AddCssCommand([
      [
        'href' => 'my/file.css',
        'media' => 'all',
      ],
      [
        'href' => 'https://example.com/css?family=Open+Sans',
        'media' => 'all',
      ],
    ]));
    return $response;
  }

  /**
   * Ajax form callback: Selects the 'driver_text' element of the validation form.
   */
  public static function validationFormCallback($form, FormStateInterface $form_state): array {
    \Drupal::messenger()->addStatus("ajax_forms_test_validation_form_callback invoked");
    \Drupal::messenger()
      ->addStatus(t("Callback: driver_text=%driver_text, spare_required_field=%spare_required_field", [
        '%driver_text' => $form_state->getValue('driver_text'),
        '%spare_required_field' => $form_state->getValue('spare_required_field'),
      ]));
    return ['#markup' => '<div id="message_area">ajax_forms_test_validation_form_callback at ' . date('c') . '</div>'];
  }

  /**
   * Ajax form callback: Selects the 'driver_number' element of the validation form.
   */
  public static function validationNumberFormCallback($form, FormStateInterface $form_state): array {
    \Drupal::messenger()->addStatus("ajax_forms_test_validation_number_form_callback invoked");
    \Drupal::messenger()
      ->addStatus(t("Callback: driver_number=%driver_number, spare_required_field=%spare_required_field", [
        '%driver_number' => $form_state->getValue('driver_number'),
        '%spare_required_field' => $form_state->getValue('spare_required_field'),
      ]));
    return ['#markup' => '<div id="message_area_number">ajax_forms_test_validation_number_form_callback at ' . date('c') . '</div>'];
  }

  /**
   * AJAX form callback: Selects for the ajax_forms_test_lazy_load_form() form.
   */
  public static function lazyLoadFormAjax($form, FormStateInterface $form_state): array {
    $build = [
      '#markup' => 'new content',
    ];

    if ($form_state->getValue('add_files')) {
      $build['#attached']['library'][] = 'system/admin';
      $build['#attached']['library'][] = 'system/drupal.system';
      $build['#attached']['drupalSettings']['ajax_forms_test_lazy_load_form_submit'] = 'executed';
    }

    return $build;
  }

}
