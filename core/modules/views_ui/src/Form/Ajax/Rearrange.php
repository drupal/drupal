<?php

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Provides a rearrange form for Views handlers.
 *
 * @internal
 */
class Rearrange extends ViewsFormBase {

  /**
   * Constructs a new Rearrange object.
   */
  public function __construct($type = NULL) {
    $this->setType($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'rearrange';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_rearrange_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');

    $types = ViewExecutable::getHandlerTypes();
    $executable = $view->getExecutable();
    if (!$executable->setDisplay($display_id)) {
      $form['markup'] = ['#markup' => $this->t('Invalid display id @display', ['@display' => $display_id])];
      return $form;
    }
    $display = &$executable->displayHandlers->get($display_id);
    $form['#title'] = $this->t('Rearrange @type', ['@type' => $types[$type]['ltitle']]);
    $form['#section'] = $display_id . 'rearrange-item';

    if ($display->defaultableSections($types[$type]['plural'])) {
      $section = $types[$type]['plural'];
      $form_state->set('section', $section);
      views_ui_standard_display_dropdown($form, $form_state, $section);
    }

    $count = 0;

    // Get relationship labels
    $relationships = [];
    foreach ($display->getHandlers('relationship') as $id => $handler) {
      $relationships[$id] = $handler->adminLabel();
    }

    $form['fields'] = [
      '#type' => 'table',
      '#header' => ['', $this->t('Weight'), $this->t('Remove')],
      '#empty' => $this->t('No fields available.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#tree' => TRUE,
      '#prefix' => '<div class="scroll" data-drupal-views-scroll>',
      '#suffix' => '</div>',
    ];

    foreach ($display->getOption($types[$type]['plural']) as $id => $field) {
      $form['fields'][$id] = [];

      $form['fields'][$id]['#attributes'] = ['class' => ['draggable'], 'id' => 'views-row-' . $id];

      $handler = $display->getHandler($type, $id);
      if ($handler) {
        $name = $handler->adminLabel() . ' ' . $handler->adminSummary();
        if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
          $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
        }
        $markup = $name;
      }
      else {
        $name = $id;
        $markup = $this->t('Broken field @id', ['@id' => $id]);
      }
      $form['fields'][$id]['name'] = ['#markup' => $markup];

      $form['fields'][$id]['weight'] = [
        '#type' => 'textfield',
        '#default_value' => ++$count,
        '#attributes' => ['class' => ['weight']],
        '#title' => t('Weight for @title', ['@title' => $name]),
        '#title_display' => 'invisible',
      ];

      $form['fields'][$id]['removed'] = [
        '#type' => 'checkbox',
        '#title' => t('Remove @title', ['@title' => $name]),
        '#title_display' => 'invisible',
        '#id' => 'views-removed-' . $id,
        '#attributes' => ['class' => ['views-remove-checkbox']],
        '#default_value' => 0,
        '#suffix' => Link::fromTextAndUrl(new FormattableMarkup('<span>@text</span>', ['@text' => $this->t('Remove')]),
          Url::fromRoute('<none>', [], [
            'attributes' => [
              'id' => 'views-remove-link-' . $id,
              'class' => ['views-hidden', 'views-button-remove', 'views-remove-link'],
              'alt' => $this->t('Remove this item'),
              'title' => $this->t('Remove this item'),
            ],
          ])
        )->toString(),
      ];
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_rearrange_form');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');

    $types = ViewExecutable::getHandlerTypes();
    $display = &$view->getExecutable()->displayHandlers->get($display_id);

    $old_fields = $display->getOption($types[$type]['plural']);
    $new_fields = $order = [];

    // Make an array with the weights
    foreach ($form_state->getValue('fields') as $field => $info) {
      // add each value that is a field with a weight to our list, but only if
      // it has had its 'removed' checkbox checked.
      if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
        $order[$field] = $info['weight'];
      }
    }

    // Sort the array
    asort($order);

    // Create a new list of fields in the new order.
    foreach (array_keys($order) as $field) {
      $new_fields[$field] = $old_fields[$field];
    }
    $display->setOption($types[$type]['plural'], $new_fields);

    // Store in cache
    $view->cacheSet();
  }

}
