<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\PathPluginBase;

/**
 * Views area display_link handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("display_link")
 */
class DisplayLink extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['display_id'] = ['default' => NULL];
    $options['label'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $allowed_displays = [];
    $displays = $this->view->storage->get('display');
    foreach ($displays as $display_id => $display) {
      if (!$this->isPathBasedDisplay($display_id)) {
        unset($displays[$display_id]);
        continue;
      }
      $allowed_displays[$display_id] = $display['display_title'];
    }

    $form['description'] = [
      [
        '#markup' => $this->t('To make sure the results are the same when switching to the other display, it is recommended to make sure the display:'),
      ],
      [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Has a path.'),
          $this->t('Has the same filter criteria.'),
          $this->t('Has the same sort criteria.'),
          $this->t('Has the same pager settings.'),
          $this->t('Has the same contextual filters.'),
        ],
      ],
    ];

    if (!$allowed_displays) {
      $form['empty_message'] = [
        '#markup' => '<p><em>' . $this->t('There are no path-based displays available.') . '</em></p>',
      ];
    }
    else {
      $form['display_id'] = [
        '#title' => $this->t('Display'),
        '#type' => 'select',
        '#options' => $allowed_displays,
        '#default_value' => $this->options['display_id'],
        '#required' => TRUE,
      ];
      $form['label'] = [
        '#title' => $this->t('Label'),
        '#description' => $this->t('The text of the link.'),
        '#type' => 'textfield',
        '#default_value' => $this->options['label'],
        '#required' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();

    // Do not add errors for the default display if it is not displayed in the
    // UI.
    if ($this->displayHandler->isDefaultDisplay() && !\Drupal::config('views.settings')->get('ui.show.default_display')) {
      return $errors;
    }

    // Ajax errors can cause the plugin to be added without any settings.
    $linked_display_id = !empty($this->options['display_id']) ? $this->options['display_id'] : NULL;
    if (!$linked_display_id) {
      $errors[] = $this->t('%current_display: The link in the %area area has no configured display.', [
        '%current_display' => $this->displayHandler->display['display_title'],
        '%area' => $this->areaType,
      ]);
      return $errors;
    }

    // Check if the linked display hasn't been removed.
    if (!$this->view->displayHandlers->get($linked_display_id)) {
      $errors[] = $this->t('%current_display: The link in the %area area points to the %linked_display display which no longer exists.', [
        '%current_display' => $this->displayHandler->display['display_title'],
        '%area' => $this->areaType,
        '%linked_display' => $this->options['display_id'],
      ]);
      return $errors;
    }

    // Check if the linked display is a path-based display.
    if (!$this->isPathBasedDisplay($linked_display_id)) {
      $errors[] = $this->t('%current_display: The link in the %area area points to the %linked_display display which does not have a path.', [
        '%current_display' => $this->displayHandler->display['display_title'],
        '%area' => $this->areaType,
        '%linked_display' => $this->view->displayHandlers->get($linked_display_id)->display['display_title'],
      ]);
      return $errors;
    }

    // Check if options of the linked display are equal to the options of the
    // current display. We "only" show a warning here, because even though we
    // recommend keeping the display options equal, we do not want to enforce
    // this.
    $unequal_options = [
      'filters' => $this->t('Filter criteria'),
      'sorts' => $this->t('Sort criteria'),
      'pager' => $this->t('Pager'),
      'arguments' => $this->t('Contextual filters'),
    ];
    foreach (array_keys($unequal_options) as $option) {
      if ($this->hasEqualOptions($linked_display_id, $option)) {
        unset($unequal_options[$option]);
      }
    }

    if ($unequal_options) {
      $warning = $this->t('%current_display: The link in the %area area points to the %linked_display display which uses different settings than the %current_display display for: %unequal_options. To make sure users see the exact same result when clicking the link, please check that the settings are the same.', [
        '%current_display' => $this->displayHandler->display['display_title'],
        '%area' => $this->areaType,
        '%linked_display' => $this->view->displayHandlers->get($linked_display_id)->display['display_title'],
        '%unequal_options' => implode(', ', $unequal_options),
      ]);
      $this->messenger()->addWarning($warning);
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (($empty && empty($this->options['empty'])) || empty($this->options['display_id'])) {
      return [];
    }

    if (!$this->isPathBasedDisplay($this->options['display_id'])) {
      return [];
    }

    // Get query parameters from the exposed input and pager.
    $query = $this->view->getExposedInput();
    if ($current_page = $this->view->getCurrentPage()) {
      $query['page'] = $current_page;
    }

    // @todo Remove this parsing once these are removed from the request in
    //   https://www.drupal.org/node/2504709.
    foreach ([
      'view_name',
      'view_display_id',
      'view_args',
      'view_path',
      'view_dom_id',
      'pager_element',
      'view_base_path',
      AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER,
      FormBuilderInterface::AJAX_FORM_REQUEST,
      MainContentViewSubscriber::WRAPPER_FORMAT,
    ] as $key) {
      unset($query[$key]);
    }

    // Set default classes.
    $classes = [
      'views-display-link',
      'views-display-link-' . $this->options['display_id'],
    ];
    if ($this->options['display_id'] === $this->view->current_display) {
      $classes[] = 'is-active';
    }

    return [
      '#type' => 'link',
      '#title' => $this->options['label'],
      '#url' => $this->view->getUrl($this->view->args, $this->options['display_id'])->setOptions(['query' => $query]),
      '#options' => [
        'view' => $this->view,
        'target_display_id' => $this->options['display_id'],
        'attributes' => ['class' => $classes],
      ],
    ];
  }

  /**
   * Check if a views display is a path-based display.
   *
   * @param string $display_id
   *   The display ID to check.
   *
   * @return bool
   *   Whether the display ID is an allowed display or not.
   */
  protected function isPathBasedDisplay($display_id) {
    $loaded_display = $this->view->displayHandlers->get($display_id);
    return $loaded_display instanceof PathPluginBase;
  }

  /**
   * Check if the options of a views display are equal to the current display.
   *
   * @param string $display_id
   *   The display ID to check.
   * @param string $option
   *   The option to check.
   *
   * @return bool
   *   Whether the option of the view display are equal to the current display
   *   or not.
   */
  protected function hasEqualOptions($display_id, $option) {
    $loaded_display = $this->view->displayHandlers->get($display_id);
    return $loaded_display->getOption($option) === $this->displayHandler->getOption($option);
  }

}
