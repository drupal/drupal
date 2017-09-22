<?php

namespace Drupal\views\Plugin\views\exposed_form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Base class for Views exposed filter form plugins.
 *
 * @ingroup views_exposed_form_plugins
 */
abstract class ExposedFormPluginBase extends PluginBase implements CacheableDependencyInterface, ExposedFormPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['submit_button'] = ['default' => $this->t('Apply')];
    $options['reset_button'] = ['default' => FALSE];
    $options['reset_button_label'] = ['default' => $this->t('Reset')];
    $options['exposed_sorts_label'] = ['default' => $this->t('Sort by')];
    $options['expose_sort_order'] = ['default' => TRUE];
    $options['sort_asc_label'] = ['default' => $this->t('Asc')];
    $options['sort_desc_label'] = ['default' => $this->t('Desc')];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['submit_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button text'),
      '#default_value' => $this->options['submit_button'],
      '#required' => TRUE,
    ];

    $form['reset_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include reset button (resets all applied exposed filters)'),
      '#default_value' => $this->options['reset_button'],
    ];

    $form['reset_button_label'] = [
     '#type' => 'textfield',
      '#title' => $this->t('Reset button label'),
      '#description' => $this->t('Text to display in the reset button of the exposed form.'),
      '#default_value' => $this->options['reset_button_label'],
      '#required' => TRUE,
      '#states' => [
        'invisible' => [
          'input[name="exposed_form_options[reset_button]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['exposed_sorts_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exposed sorts label'),
      '#default_value' => $this->options['exposed_sorts_label'],
      '#required' => TRUE,
    ];

    $form['expose_sort_order'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow people to choose the sort order'),
      '#description' => $this->t('If sort order is not exposed, the sort criteria settings for each sort will determine its order.'),
      '#default_value' => $this->options['expose_sort_order'],
    ];

    $form['sort_asc_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for ascending sort'),
      '#default_value' => $this->options['sort_asc_label'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          'input[name="exposed_form_options[expose_sort_order]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sort_desc_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for descending sort'),
      '#default_value' => $this->options['sort_desc_label'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          'input[name="exposed_form_options[expose_sort_order]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderExposedForm($block = FALSE) {
    // Deal with any exposed filters we may have, before building.
    $form_state = (new FormState())
      ->setStorage([
        'view' => $this->view,
        'display' => &$this->view->display_handler->display,
        'rerender' => TRUE,
      ])
      ->setMethod('get')
      ->setAlwaysProcess()
      ->disableRedirect();

    // Some types of displays (eg. attachments) may wish to use the exposed
    // filters of their parent displays instead of showing an additional
    // exposed filter form for the attachment as well as that for the parent.
    if (!$this->view->display_handler->displaysExposed() || (!$block && $this->view->display_handler->getOption('exposed_block'))) {
      $form_state->set('rerender', NULL);
    }

    if (!empty($this->ajax)) {
      $form_state->set('ajax', TRUE);
    }

    $form = \Drupal::formBuilder()->buildForm('\Drupal\views\Form\ViewsExposedForm', $form_state);
    $errors = $form_state->getErrors();

    // If the exposed form had errors, do not build the view.
    if (!empty($errors)) {
      $this->view->build_info['abort'] = TRUE;
    }

    if (!$this->view->display_handler->displaysExposed() || (!$block && $this->view->display_handler->getOption('exposed_block'))) {
      return [];
    }
    else {
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $view = $this->view;
    $exposed_data = isset($view->exposed_data) ? $view->exposed_data : [];
    $sort_by = isset($exposed_data['sort_by']) ? $exposed_data['sort_by'] : NULL;
    if (!empty($sort_by)) {
      // Make sure the original order of sorts is preserved
      // (e.g. a sticky sort is often first)
      if (isset($view->sort[$sort_by])) {
        $view->query->orderby = [];
        foreach ($view->sort as $key => $sort) {
          if (!$sort->isExposed()) {
            $sort->query();
          }
          elseif ($key == $sort_by) {
            if (isset($exposed_data['sort_order']) && in_array($exposed_data['sort_order'], ['ASC', 'DESC'])) {
              $sort->options['order'] = $exposed_data['sort_order'];
            }
            $sort->setRelationship();
            $sort->query();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($values) {}

  /**
   * {@inheritdoc}
   */
  public function postRender(&$output) {}

  /**
   * {@inheritdoc}
   */
  public function preExecute() {}

  /**
   * {@inheritdoc}
   */
  public function postExecute() {}

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    if (!empty($this->options['submit_button'])) {
      $form['actions']['submit']['#value'] = $this->options['submit_button'];
    }

    // Check if there is exposed sorts for this view
    $exposed_sorts = [];
    foreach ($this->view->sort as $id => $handler) {
      if ($handler->canExpose() && $handler->isExposed()) {
        $exposed_sorts[$id] = Html::escape($handler->options['expose']['label']);
      }
    }

    if (count($exposed_sorts)) {
      $form['sort_by'] = [
        '#type' => 'select',
        '#options' => $exposed_sorts,
        '#title' => $this->options['exposed_sorts_label'],
      ];
      $sort_order = [
        'ASC' => $this->options['sort_asc_label'],
        'DESC' => $this->options['sort_desc_label'],
      ];
      $user_input = $form_state->getUserInput();
      if (isset($user_input['sort_by']) && isset($this->view->sort[$user_input['sort_by']])) {
        $default_sort_order = $this->view->sort[$user_input['sort_by']]->options['order'];
      }
      else {
        $first_sort = reset($this->view->sort);
        $default_sort_order = $first_sort->options['order'];
      }

      if (!isset($user_input['sort_by'])) {
        $keys = array_keys($exposed_sorts);
        $user_input['sort_by'] = array_shift($keys);
        $form_state->setUserInput($user_input);
      }

      if ($this->options['expose_sort_order']) {
        $form['sort_order'] = [
          '#type' => 'select',
          '#options' => $sort_order,
          '#title' => $this->t('Order', [], ['context' => 'Sort order']),
          '#default_value' => $default_sort_order,
        ];
      }
      $form['submit']['#weight'] = 10;
    }

    if (!empty($this->options['reset_button'])) {
      $form['actions']['reset'] = [
        '#value' => $this->options['reset_button_label'],
        '#type' => 'submit',
        '#weight' => 10,
      ];

      // Get an array of exposed filters, keyed by identifier option.
      $exposed_filters = [];
      foreach ($this->view->filter as $id => $handler) {
        if ($handler->canExpose() && $handler->isExposed() && !empty($handler->options['expose']['identifier'])) {
          $exposed_filters[$handler->options['expose']['identifier']] = $id;
        }
      }
      $all_exposed = array_merge($exposed_sorts, $exposed_filters);

      // Set the access to FALSE if there is no exposed input.
      if (!array_intersect_key($all_exposed, $this->view->getExposedInput())) {
        $form['actions']['reset']['#access'] = FALSE;
      }
    }

    $pager = $this->view->display_handler->getPlugin('pager');
    if ($pager) {
      $pager->exposedFormAlter($form, $form_state);
      $form_state->set('pager_plugin', $pager);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormValidate(&$form, FormStateInterface $form_state) {
    if ($pager_plugin = $form_state->get('pager_plugin')) {
      $pager_plugin->exposedFormValidate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormSubmit(&$form, FormStateInterface $form_state, &$exclude) {
    if (!$form_state->isValueEmpty('op') && $form_state->getValue('op') == $this->options['reset_button_label']) {
      $this->resetForm($form, $form_state);
    }
    if ($pager_plugin = $form_state->get('pager_plugin')) {
      $pager_plugin->exposedFormSubmit($form, $form_state, $exclude);
      $exclude[] = 'pager_plugin';
    }
  }

  /**
   * Resets all the states of the exposed form.
   *
   * This method is called when the "Reset" button is triggered. Clears
   * user inputs, stored session, and the form state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(&$form, FormStateInterface $form_state) {
    // _SESSION is not defined for users who are not logged in.

    // If filters are not overridden, store the 'remember' settings on the
    // default display. If they are, store them on this display. This way,
    // multiple displays in the same view can share the same filters and
    // remember settings.
    $display_id = ($this->view->display_handler->isDefaulted('filters')) ? 'default' : $this->view->current_display;

    if (isset($_SESSION['views'][$this->view->storage->id()][$display_id])) {
      unset($_SESSION['views'][$this->view->storage->id()][$display_id]);
    }

    // Set the form to allow redirect.
    if (empty($this->view->live_preview) && !\Drupal::request()->isXmlHttpRequest()) {
      $form_state->disableRedirect(FALSE);
    }
    else {
      $form_state->setRebuild();
      $this->view->exposed_data = [];
    }

    $form_state->setRedirect('<current>');
    $form_state->setValues([]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];
    if ($this->options['expose_sort_order']) {
      // The sort order query arg is just important in case there is a exposed
      // sort order.
      $has_exposed_sort_handler = FALSE;
      /** @var \Drupal\views\Plugin\views\sort\SortPluginBase $sort_handler */
      foreach ($this->displayHandler->getHandlers('sort') as $sort_handler) {
        if ($sort_handler->isExposed()) {
          $has_exposed_sort_handler = TRUE;
        }
      }

      if ($has_exposed_sort_handler) {
        $contexts[] = 'url.query_args:sort_order';
      }
    }

    // Merge in cache contexts for all exposed filters to prevent display of
    // cached forms.
    foreach ($this->displayHandler->getHandlers('filter') as $filter_hander) {
      if ($filter_hander->isExposed()) {
        $contexts = Cache::mergeContexts($contexts, $filter_hander->getCacheContexts());
      }
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
