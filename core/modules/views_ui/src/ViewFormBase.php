<?php

namespace Drupal\views_ui;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Base form for Views forms.
 */
abstract class ViewFormBase extends EntityForm {

  /**
   * The name of the display used by the form.
   *
   * @var string
   */
  protected $displayID;

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);

    // @todo Remove the need for this.
    $form_state->loadInclude('views_ui', 'inc', 'admin');
    $form_state->set('view', $this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $display_id = NULL) {
    if (isset($display_id) && $form_state->has('display_id') && ($display_id !== $form_state->get('display_id'))) {
      throw new \InvalidArgumentException('Mismatch between $form_state->get(\'display_id\') and $display_id.');
    }
    $this->displayID = $form_state->has('display_id') ? $form_state->get('display_id') : $display_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    // Determine the displays available for editing.
    if ($tabs = $this->getDisplayTabs($this->entity)) {
      if (empty($this->displayID)) {
        // If a display isn't specified, use the first one after sorting by
        // #weight.
        uasort($tabs, 'Drupal\Component\Utility\SortArray::sortByWeightProperty');
        foreach ($tabs as $id => $tab) {
          if (!isset($tab['#access']) || $tab['#access']) {
            $this->displayID = $id;
            break;
          }
        }
      }
      // If a display is specified, but we don't have access to it, return
      // an access denied page.
      if ($this->displayID && !isset($tabs[$this->displayID])) {
        throw new NotFoundHttpException();
      }
      elseif ($this->displayID && (isset($tabs[$this->displayID]['#access']) && !$tabs[$this->displayID]['#access'])) {
        throw new AccessDeniedHttpException();
      }

    }
    elseif ($this->displayID) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Adds tabs for navigating across Displays when editing a View.
   *
   * This function can be called from hook_menu_local_tasks_alter() to implement
   * these tabs as secondary local tasks, or it can be called from elsewhere if
   * having them as secondary local tasks isn't desired. The caller is responsible
   * for setting the active tab's #active property to TRUE.
   *
   * @param \Drupal\views_ui\ViewUI $view
   *   The ViewUI entity.
   *
   * @return array
   *   An array of tab definitions.
   */
  public function getDisplayTabs(ViewUI $view) {
    $executable = $view->getExecutable();
    $executable->initDisplay();
    $display_id = $this->displayID;
    $tabs = [];

    // Create a tab for each display.
    foreach ($view->get('display') as $id => $display) {
      // Get an instance of the display plugin, to make sure it will work in the
      // UI.
      $display_plugin = $executable->displayHandlers->get($id);
      if (empty($display_plugin)) {
        continue;
      }

      $tabs[$id] = [
        '#theme' => 'menu_local_task',
        '#weight' => $display['position'],
        '#link' => [
          'title' => $this->getDisplayLabel($view, $id),
          'localized_options' => [],
          'url' => $view->toUrl('edit-display-form')->setRouteParameter('display_id', $id),
        ],
      ];
      if (!empty($display['deleted'])) {
        $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'views-display-deleted-link';
      }
      if (isset($display['display_options']['enabled']) && !$display['display_options']['enabled']) {
        $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'views-display-disabled-link';
      }
    }

    // If the default display isn't supposed to be shown, don't display its tab, unless it's the only display.
    if ((!$this->isDefaultDisplayShown($view) && $display_id != 'default') && count($tabs) > 1) {
      $tabs['default']['#access'] = FALSE;
    }

    // Mark the display tab as red to show validation errors.
    $errors = $executable->validate();
    foreach ($view->get('display') as $id => $display) {
      if (!empty($errors[$id])) {
        // Always show the tab.
        $tabs[$id]['#access'] = TRUE;
        // Add a class to mark the error and a title to make a hover tip.
        $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'error';
        $tabs[$id]['#link']['localized_options']['attributes']['title'] = $this->t('This display has one or more validation errors.');
      }
    }

    return $tabs;
  }

  /**
   * Controls whether or not the default display should have its own tab on edit.
   */
  public function isDefaultDisplayShown(ViewUI $view) {
    // Always show the default display for advanced users who prefer that mode.
    $advanced_mode = \Drupal::config('views.settings')->get('ui.show.default_display');
    // For other users, show the default display only if there are no others, and
    // hide it if there's at least one "real" display.
    $additional_displays = (count($view->getExecutable()->displayHandlers) == 1);

    return $advanced_mode || $additional_displays;
  }

  /**
   * Placeholder function for overriding $display['display_title'].
   *
   * @todo Remove this function once editing the display title is possible.
   */
  public function getDisplayLabel(ViewUI $view, $display_id, $check_changed = TRUE) {
    $display = $view->get('display');
    $title = $display_id == 'default' ? $this->t('Default') : $display[$display_id]['display_title'];
    $title = Unicode::truncate($title, 25, FALSE, TRUE);

    if ($check_changed && !empty($view->changed_display[$display_id])) {
      $changed = '*';
      $title = $title . $changed;
    }

    return $title;
  }

}
