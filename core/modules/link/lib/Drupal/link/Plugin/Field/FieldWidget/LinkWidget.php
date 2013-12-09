<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Field\FieldWidget\LinkWidget.
 */

namespace Drupal\link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_default",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "placeholder_url" = "",
 *     "placeholder_title" = ""
 *   }
 * )
 */
class LinkWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element['url'] = array(
      '#type' => 'url',
      '#title' => t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url : NULL,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );
    $element['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
    );
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    $is_field_edit_form = ($element['#entity'] === NULL);
    if (!$is_field_edit_form && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'] = array(array($this, 'validateTitle'));
    }

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['attributes'] = array(
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->attributes) ? $items[$delta]->attributes : array(),
      '#attributes' => array('class' => array('link-field-widget-attributes')),
    );

    // If cardinality is 1, ensure a label is output for the field by wrapping it
    // in a details element.
    if ($this->fieldDefinition->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    $elements['placeholder_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder for link text'),
      '#default_value' => $this->getSetting('placeholder_title'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#states' => array(
        'invisible' => array(
          ':input[name="instance[settings][title]"]' => array('value' => DRUPAL_DISABLED),
        ),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder_title = $this->getSetting('placeholder_title');
    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_title) && empty($placeholder_url)) {
      $summary[] = t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = t('Title placeholder: @placeholder_title', array('@placeholder_title' => $placeholder_title));
      }
      if (!empty($placeholder_url)) {
        $summary[] = t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }


  /**
   * Form element validation handler for link_field_widget_form().
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  function validateTitle(&$element, &$form_state, $form) {
    if ($element['url']['#value'] !== '' && $element['title']['#value'] === '') {
      $element['title']['#required'] = TRUE;
      form_error($element['title'], $form_state, t('!name field is required.', array('!name' => $element['title']['#title'])));
    }
  }
}

