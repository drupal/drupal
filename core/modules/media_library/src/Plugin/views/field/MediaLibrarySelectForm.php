<?php

namespace Drupal\media_library\Plugin\views\field;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\Url;
use Drupal\media_library\MediaLibraryState;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a field that outputs a checkbox and form for selecting media.
 *
 * @internal
 *   Plugin classes are internal.
 */
#[ViewsField("media_library_select_form")]
class MediaLibrarySelectForm extends FieldPluginBase implements WorkspaceSafeFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->mid . '-->';
  }

  /**
   * Return the name of a form field.
   *
   * @see \Drupal\views\Form\ViewsFormMainForm
   *
   * @return string
   *   The form field name.
   */
  public function form_element_name(): string {
    return $this->field;
  }

  /**
   * Return a media entity ID from a views result row.
   *
   * @see \Drupal\views\Form\ViewsFormMainForm
   *
   * @param int $row_id
   *   The index of a views result row.
   *
   * @return string
   *   The ID of a media entity.
   */
  public function form_element_row_id(int $row_id): string {
    return $this->view->result[$row_id]->mid;
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
    $form['#attributes']['class'] = ['js-media-library-views-form'];
    // Add target for AJAX messages.
    $form['media_library_messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'media-library-messages',
      ],
      '#weight' => -10,
    ];

    // Add an attribute that identifies the media type displayed in the form.
    if (isset($this->view->args[0])) {
      $form['#attributes']['data-drupal-media-type'] = $this->view->args[0];
    }

    // Render checkboxes for all rows.
    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      $entity = $this->getEntity($row);
      if (!$entity) {
        $form[$this->options['id']][$row_index] = [];
        continue;
      }
      $form[$this->options['id']][$row->mid] = [
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
    $query = $this->view->getRequest()->query->all();
    $query[FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
    $query['views_display_id'] = $this->view->getDisplay()->display['id'];
    $form['actions']['submit']['#ajax'] = [
      'url' => Url::fromRoute('media_library.ui'),
      'options' => [
        'query' => $query,
      ],
      'callback' => [static::class, 'updateWidget'],
    ];

    $form['actions']['submit']['#value'] = $this->t('Insert selected');
    $form['actions']['submit']['#button_type'] = 'primary';
    $form['actions']['submit']['#field_id'] = $selection_field_id;
  }

  /**
   * Submit handler for the media library select form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A command to send the selection to the current field widget.
   */
  public static function updateWidget(array &$form, FormStateInterface $form_state, Request $request) {
    $field_id = $form_state->getTriggeringElement()['#field_id'];
    $selected_ids = $form_state->getValue($field_id);
    $selected_ids = $selected_ids ? array_filter(explode(',', $selected_ids)) : [];

    // Allow the opener service to handle the selection.
    $state = MediaLibraryState::fromRequest($request);

    $current_selection = $form_state->getValue('media_library_select_form_selection');
    $available_slots = $state->getAvailableSlots();
    $selected_count = count(explode(',', $current_selection));
    if ($available_slots > 0 && $selected_count > $available_slots) {
      $response = new AjaxResponse();
      $error = \Drupal::translation()->formatPlural($selected_count - $available_slots, 'There are currently @total items selected. The maximum number of items for the field is @max. Remove @count item from the selection.', 'There are currently @total items selected. The maximum number of items for the field is @max. Remove @count items from the selection.', [
        '@total' => $selected_count,
        '@max' => $available_slots,
      ]);
      $response->addCommand(new MessageCommand($error, '#media-library-messages', ['type' => 'error']));
      return $response;
    }

    return \Drupal::service('media_library.opener_resolver')
      ->get($state)
      ->getSelectionResponse($state, $selected_ids)
      ->addCommand(new CloseDialogCommand());
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
