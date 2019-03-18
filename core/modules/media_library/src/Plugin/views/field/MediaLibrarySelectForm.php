<?php

namespace Drupal\media_library\Plugin\views\field;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;

/**
 * Defines a field that outputs a checkbox and form for selecting media.
 *
 * @ViewsField("media_library_select_form")
 *
 * @internal
 *   Media Library is an experimental module and its internal code may be
 *   subject to change in minor releases. External code should not instantiate
 *   or extend this class.
 */
class MediaLibrarySelectForm extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ViewsRenderPipelineMarkup::create($this->getValue($values));
  }

  /**
   * Form constructor for the media library select form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    $form['#attributes'] = [
      'class' => ['media-library-views-form', 'js-media-library-views-form'],
    ];

    // Add the view to the form state so the opener ID can be fetched from the
    // view request object in ::updateWidget().
    $form_state->set('view', $this->view);

    // Render checkboxes for all rows.
    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      $entity = $this->getEntity($row);
      $form[$this->options['id']][$row_index] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Select @label', [
          '@label' => $entity->label(),
        ]),
        '#title_display' => 'invisible',
        '#return_value' => $entity->id(),
      ];
    }

    // The selection is persistent across different pages in the media library
    // and populated via JavaScript.
    $selection_field_id = $this->options['id'] . '_selection';
    $form[$selection_field_id] = [
      '#type' => 'hidden',
      '#attributes' => [
        // This is used to identify the hidden field in the form via JavaScript.
        'id' => 'media-library-modal-selection',
      ],
    ];

    // @todo Remove in https://www.drupal.org/project/drupal/issues/2504115
    // Currently the default URL for all AJAX form elements is the current URL,
    // not the form action. This causes bugs when this form is rendered from an
    // AJAX path like /views/ajax, which cannot process AJAX form submits.
    $url = parse_url($form['#action'], PHP_URL_PATH);
    $query = $this->view->getRequest()->query->all();
    $query[FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
    $form['actions']['submit']['#ajax'] = [
      'url' => Url::fromUserInput($url),
      'options' => [
        'query' => $query,
      ],
      'callback' => [static::class, 'updateWidget'],
    ];

    $form['actions']['submit']['#value'] = $this->t('Insert selected');
    $form['actions']['submit']['#button_type'] = 'primary';
    $form['actions']['submit']['#field_id'] = $selection_field_id;
    // By default, the AJAX system tries to move the focus back to the element
    // that triggered the AJAX request. Since the media library is closed after
    // clicking the select button, the focus can't be moved back. We need to set
    // the 'data-disable-refocus' attribute to prevent the AJAX system from
    // moving focus to a random element. The select button triggers an update in
    // the opener, and the opener should be responsible for moving the focus. An
    // example of this can be seen in MediaLibraryWidget::updateWidget().
    // @see \Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget::updateWidget()
    $form['actions']['submit']['#attributes'] = [
      'class' => ['media-library-select'],
      'data-disable-refocus' => 'true',
    ];
  }

  /**
   * Submit handler for the media library select form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A command to send the selection to the current field widget.
   */
  public static function updateWidget(array &$form, FormStateInterface $form_state) {
    $field_id = $form_state->getTriggeringElement()['#field_id'];
    $selected = array_filter(explode(',', $form_state->getValue($field_id, [])));

    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());

    $ids = implode(',', $selected);

    $opener_id = MediaLibraryState::fromRequest($form_state->get('view')->getRequest())->getOpenerId();
    if ($field_id = MediaLibraryWidget::getOpenerFieldId($opener_id)) {
      $response
        ->addCommand(new InvokeCommand("[data-media-library-widget-value=\"$field_id\"]", 'val', [$ids]))
        ->addCommand(new InvokeCommand("[data-media-library-widget-update=\"$field_id\"]", 'trigger', ['mousedown']));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(array &$form, FormStateInterface $form_state) {
    $selected = array_filter($form_state->getValue($this->options['id']));
    if (empty($selected)) {
      $form_state->setErrorByName('', $this->t('No items selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

}
