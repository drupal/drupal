<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Field\FieldWidget\LinkWidget.
 */

namespace Drupal\link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_default",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'placeholder_url' => '',
      'placeholder_title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $default_url_value = NULL;
    if (isset($items[$delta]->url)) {
      if ($url = \Drupal::pathValidator()->getUrlIfValid($items[$delta]->url)) {
        $url->setOptions($items[$delta]->options);
        $default_url_value = ltrim($url->toString(), '/');
      }
    }
    $element['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_url_value,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );

    // If the field is configured to support internal links, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($this->supportsInternalLinks()) {
      $element['url']['#type'] = 'textfield';
    }

    // If the field is configured to allow only internal links, add a useful
    // element prefix.
    if (!$this->supportsExternalLinks()) {
      $element['url']['#field_prefix'] = \Drupal::url('<front>', array(), array('absolute' => TRUE));
    }
    // If the field is configured to allow both internal and external links,
    // show a useful description.
    elseif ($this->supportsExternalLinks() && $this->supportsInternalLinks()) {
      $element['url']['#description'] = $this->t('This can be an internal Drupal path such as %add-node or an external URL such as %drupal. Enter %front to link to the front page.', array('%front' => '<front>', '%add-node' => 'node/add', '%drupal' => 'http://drupal.org'));
    }

    $element['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
    );
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'][] = array($this, 'validateTitle');
    }

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['attributes'] = array(
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->options['attributes']) ? $items[$delta]->options['attributes'] : array(),
      '#attributes' => array('class' => array('link-field-widget-attributes')),
    );

    // If cardinality is 1, ensure a label is output for the field by wrapping it
    // in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    return $element;
  }

  /**
   * Indicates enabled support for link to routes.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   routes, otherwise FALSE.
   */
  protected function supportsInternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & LinkItemInterface::LINK_INTERNAL);
  }

  /**
   * Indicates enabled support for link to external URLs.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   external URLs, otherwise FALSE.
   */
  protected function supportsExternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & LinkItemInterface::LINK_EXTERNAL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    $elements['placeholder_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for link text'),
      '#default_value' => $this->getSetting('placeholder_title'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
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
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = $this->t('Title placeholder: @placeholder_title', array('@placeholder_title' => $placeholder_title));
      }
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }

  /**
   * Form element validation handler; Validates the title property.
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  public function validateTitle(&$element, FormStateInterface $form_state, $form) {
    if ($element['url']['#value'] !== '' && $element['title']['#value'] === '') {
      $element['title']['#required'] = TRUE;
      $form_state->setError($element['title'], $this->t('!name field is required.', array('!name' => $element['title']['#title'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (!empty($value['url'])) {
        $url = \Drupal::pathValidator()->getUrlIfValid($value['url']);
        if (!$url) {
          return $values;
        }

        // @todo Don't use the toArray method here. Removed once it is
        //   deprecated.
        $value += $url->toArray();

        // Reset the URL value to contain only the path.
        if (!$url->isExternal() && $this->supportsInternalLinks()) {
          $value['url'] = substr($url->toString(), strlen(\Drupal::request()->getBasePath() . '/'));
        }
      }
    }
    return $values;
  }

}
