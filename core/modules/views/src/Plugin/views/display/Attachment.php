<?php

namespace Drupal\views\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * The plugin that handles an attachment display.
 *
 * Attachment displays are secondary displays that are 'attached' to a primary
 * display. Effectively they are a simple way to get multiple views within
 * the same view. They can share some information.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "attachment",
 *   title = @Translation("Attachment"),
 *   help = @Translation("Attachments added to other displays to achieve multiple views in the same view."),
 *   theme = "views_view",
 *   contextual_links_locations = {""}
 * )
 */
class Attachment extends DisplayPluginBase {

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = FALSE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = ['default' => []];
    $options['attachment_position'] = ['default' => 'before'];
    $options['inherit_arguments'] = ['default' => TRUE];
    $options['inherit_exposed_filters'] = ['default' => FALSE];
    $options['inherit_pager'] = ['default' => FALSE];
    $options['render_pager'] = ['default' => FALSE];

    return $options;
  }

  public function execute() {
    return $this->view->render($this->display['id']);
  }

  public function attachmentPositions($position = NULL) {
    $positions = [
      'before' => $this->t('Before'),
      'after' => $this->t('After'),
      'both' => $this->t('Both'),
    ];

    if ($position) {
      return $positions[$position];
    }

    return $positions;
  }

  /**
   * Provide the summary for attachment options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::optionsSummary($categories, $options);

    $categories['attachment'] = [
      'title' => $this->t('Attachment settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      if ($display = $this->view->storage->getDisplay($display)) {
        $attach_to = $display['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('Not defined');
    }

    $options['displays'] = [
      'category' => 'attachment',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    ];

    $options['attachment_position'] = [
      'category' => 'attachment',
      'title' => $this->t('Attachment position'),
      'value' => $this->attachmentPositions($this->getOption('attachment_position')),
    ];

    $options['inherit_arguments'] = [
      'category' => 'attachment',
      'title' => $this->t('Inherit contextual filters'),
      'value' => $this->getOption('inherit_arguments') ? $this->t('Yes') : $this->t('No'),
    ];

    $options['inherit_exposed_filters'] = [
      'category' => 'attachment',
      'title' => $this->t('Inherit exposed filters'),
      'value' => $this->getOption('inherit_exposed_filters') ? $this->t('Yes') : $this->t('No'),
    ];

    $options['inherit_pager'] = [
      'category' => 'pager',
      'title' => $this->t('Inherit pager'),
      'value' => $this->getOption('inherit_pager') ? $this->t('Yes') : $this->t('No'),
    ];

    $options['render_pager'] = [
      'category' => 'pager',
      'title' => $this->t('Render pager'),
      'value' => $this->getOption('render_pager') ? $this->t('Yes') : $this->t('No'),
    ];

  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here:
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'inherit_arguments':
        $form['#title'] .= $this->t('Inherit contextual filters');
        $form['inherit_arguments'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Inherit'),
          '#description' => $this->t('Should this display inherit its contextual filter values from the parent display to which it is attached?'),
          '#default_value' => $this->getOption('inherit_arguments'),
        ];
        break;

      case 'inherit_exposed_filters':
        $form['#title'] .= $this->t('Inherit exposed filters');
        $form['inherit_exposed_filters'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Inherit'),
          '#description' => $this->t('Should this display inherit its exposed filter values from the parent display to which it is attached?'),
          '#default_value' => $this->getOption('inherit_exposed_filters'),
        ];
        break;

      case 'inherit_pager':
        $form['#title'] .= $this->t('Inherit pager');
        $form['inherit_pager'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Inherit'),
          '#description' => $this->t('Should this display inherit its paging values from the parent display to which it is attached?'),
          '#default_value' => $this->getOption('inherit_pager'),
        ];
        break;

      case 'render_pager':
        $form['#title'] .= $this->t('Render pager');
        $form['render_pager'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Render'),
          '#description' => $this->t('Should this display render the pager values? This is only meaningful if inheriting a pager.'),
          '#default_value' => $this->getOption('render_pager'),
        ];
        break;

      case 'attachment_position':
        $form['#title'] .= $this->t('Position');
        $form['attachment_position'] = [
          '#title' => $this->t('Position'),
          '#type' => 'radios',
          '#description' => $this->t('Attach before or after the parent display?'),
          '#options' => $this->attachmentPositions(),
          '#default_value' => $this->getOption('attachment_position'),
        ];
        break;

      case 'displays':
        $form['#title'] .= $this->t('Attach to');
        $displays = [];
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          if ($this->view->displayHandlers->has($display_id) && $this->view->displayHandlers->get($display_id)->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = [
          '#title' => $this->t('Displays'),
          '#type' => 'checkboxes',
          '#description' => $this->t('Select which display or displays this should attach to.'),
          '#options' => array_map('\Drupal\Component\Utility\Html::escape', $displays),
          '#default_value' => $this->getOption('displays'),
        ];
        break;
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here:
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'displays':
        $form_state->setValue($section, array_filter($form_state->getValue($section)));
      case 'inherit_arguments':
      case 'inherit_pager':
      case 'render_pager':
      case 'inherit_exposed_filters':
      case 'attachment_position':
        $this->setOption($section, $form_state->getValue($section));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build) {
    $displays = $this->getOption('displays');

    if (empty($displays[$display_id])) {
      return;
    }

    if (!$this->access()) {
      return;
    }

    $args = $this->getOption('inherit_arguments') ? $this->view->args : [];
    $view->setArguments($args);
    $view->setDisplay($this->display['id']);
    if ($this->getOption('inherit_pager')) {
      $view->display_handler->usesPager = $this->view->displayHandlers->get($display_id)->usesPager();
      $view->display_handler->setOption('pager', $this->view->displayHandlers->get($display_id)->getOption('pager'));
    }

    $attachment = $view->buildRenderable($this->display['id'], $args);

    switch ($this->getOption('attachment_position')) {
      case 'before':
        $this->view->attachment_before[] = $attachment;
        break;

      case 'after':
        $this->view->attachment_after[] = $attachment;
        break;

      case 'both':
        $this->view->attachment_before[] = $attachment;
        $this->view->attachment_after[] = $attachment;
        break;
    }

  }

  /**
   * Attachment displays only use exposed widgets if
   * they are set to inherit the exposed filter settings
   * of their parent display.
   */
  public function usesExposed() {
    if (!empty($this->options['inherit_exposed_filters']) && parent::usesExposed()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * If an attachment is set to inherit the exposed filter
   * settings from its parent display, then don't render and
   * display a second set of exposed filter widgets.
   */
  public function displaysExposed() {
    return $this->options['inherit_exposed_filters'] ? FALSE : TRUE;
  }

  public function renderPager() {
    return $this->usesPager() && $this->getOption('render_pager');
  }

}
