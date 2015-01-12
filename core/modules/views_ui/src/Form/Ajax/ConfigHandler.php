<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ConfigHandler.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for configuring an item in the Views UI.
 */
class ConfigHandler extends ViewsFormBase {

  /**
   * Constucts a new ConfigHandler object.
   */
  public function __construct($type = NULL, $id = NULL) {
    $this->setType($type);
    $this->setID($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'handler';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL, $id = NULL) {
    $this->setType($type);
    $this->setID($id);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_config_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');

    $form = array(
      'options' => array(
        '#tree' => TRUE,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
      ),
    );
    $executable = $view->getExecutable();
    $save_ui_cache = FALSE;
    $executable->setDisplay($display_id);
    $item = $executable->getHandler($display_id, $type, $id);

    if ($item) {
      $handler = $executable->display_handler->getHandler($type, $id);
      if (empty($handler)) {
        $form['markup'] = array('#markup' => $this->t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
      }
      else {
        $types = ViewExecutable::getHandlerTypes();

        // If this item can come from the default display, show a dropdown
        // that lets the user choose which display the changes should apply to.
        if ($executable->display_handler->defaultableSections($types[$type]['plural'])) {
          $section = $types[$type]['plural'];
          $form_state->set('section', $section);
          views_ui_standard_display_dropdown($form, $form_state, $section);
        }

        // A whole bunch of code to figure out what relationships are valid for
        // this item.
        $relationships = $executable->display_handler->getOption('relationships');
        $relationship_options = array();

        foreach ($relationships as $relationship) {
          // relationships can't link back to self. But also, due to ordering,
          // relationships can only link to prior relationships.
          if ($type == 'relationship' && $id == $relationship['id']) {
            break;
          }
          $relationship_handler = Views::handlerManager('relationship')->getHandler($relationship);
          // ignore invalid/broken relationships.
          if (empty($relationship_handler)) {
            continue;
          }

          // If this relationship is valid for this type, add it to the list.
          $data = Views::viewsData()->get($relationship['table']);
          if (isset($data[$relationship['field']]['relationship']['base']) && $base = $data[$relationship['field']]['relationship']['base']) {
            $base_fields = Views::viewsDataHelper()->fetchFields($base, $type, $executable->display_handler->useGroupBy());
            if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
              $relationship_handler->init($executable, $executable->display_handler, $relationship);
              $relationship_options[$relationship['id']] = $relationship_handler->adminLabel();
            }
          }
        }

        if (!empty($relationship_options)) {
          // Make sure the existing relationship is even valid. If not, force
          // it to none.
          $base_fields = Views::viewsDataHelper()->fetchFields($view->get('base_table'), $type, $executable->display_handler->useGroupBy());
          if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
            $relationship_options = array_merge(array('none' => $this->t('Do not use a relationship')), $relationship_options);
          }
          $rel = empty($item['relationship']) ? 'none' : $item['relationship'];
          if (empty($relationship_options[$rel])) {
            // Pick the first relationship.
            $rel = key($relationship_options);
            // We want this relationship option to get saved even if the user
            // skips submitting the form.
            $executable->setHandlerOption($display_id, $type, $id, 'relationship', $rel);
            $save_ui_cache = TRUE;
          }

          $form['options']['relationship'] = array(
            '#type' => 'select',
            '#title' => $this->t('Relationship'),
            '#options' => $relationship_options,
            '#default_value' => $rel,
            '#weight' => -500,
          );
        }
        else {
          $form['options']['relationship'] = array(
            '#type' => 'value',
            '#value' => 'none',
          );
        }

        $form['#title'] = $this->t('Configure @type: @item', array('@type' => $types[$type]['lstitle'], '@item' => $handler->adminLabel()));

        if (!empty($handler->definition['help'])) {
          $form['options']['form_description'] = array(
            '#markup' => $handler->definition['help'],
            '#theme_wrappers' => array('container'),
            '#attributes' => array('class' => array('form-item description')),
            '#weight' => -1000,
          );
        }

        $form['#section'] = $display_id . '-' . $type . '-' . $id;

        // Get form from the handler.
        $handler->buildOptionsForm($form['options'], $form_state);
        $form_state->set('handler', $handler);
      }

      $name = $form_state->get('update_name');

      $view->getStandardButtons($form, $form_state, 'views_ui_config_item_form', $name);
      // Add a 'remove' button.
      $form['actions']['remove'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => array(array($this, 'remove')),
        '#limit_validation_errors' => array(array('override')),
        '#ajax' => array(
          'url' => Url::fromRoute('<current>'),
        ),
      );
    }

    if ($save_ui_cache) {
      $view->cacheSet();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->get('handler')->validateOptionsForm($form['options'], $form_state);

    if ($form_state->getErrors()) {
      $form_state->set('rerender', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $id = $form_state->get('id');
    $handler = $form_state->get('handler');

    // Run it through the handler's submit function.
    $handler->submitOptionsForm($form['options'], $form_state);
    $item = $handler->options;
    $types = ViewExecutable::getHandlerTypes();

    // For footer/header $handler_type is area but $type is footer/header.
    // For all other handle types it's the same.
    $handler_type = $type = $form_state->get('type');
    if (!empty($types[$type]['type'])) {
      $handler_type = $types[$type]['type'];
    }

    $override = NULL;
    $executable = $view->getExecutable();
    if ($executable->display_handler->useGroupBy() && !empty($item['group_type'])) {
      if (empty($executable->query)) {
        $executable->initQuery();
      }
      $aggregate = $executable->query->getAggregationInfo();
      if (!empty($aggregate[$item['group_type']]['handler'][$type])) {
        $override = $aggregate[$item['group_type']]['handler'][$type];
      }
    }

    // Create a new handler and unpack the options from the form onto it. We
    // can use that for storage.
    $handler = Views::handlerManager($handler_type)->getHandler($item, $override);
    $handler->init($executable, $executable->display_handler, $item);

    // Add the incoming options to existing options because items using
    // the extra form may not have everything in the form here.
    $options = $form_state->getValue('options') + $handler->options;

    // This unpacks only options that are in the definition, ensuring random
    // extra stuff on the form is not sent through.
    $handler->unpackOptions($handler->options, $options, NULL, FALSE);

    // Store the item back on the view
    $executable->setHandler($display_id, $type, $id, $handler->options);

    // Ensure any temporary options are removed.
    if (isset($view->temporary_options[$type][$id])) {
      unset($view->temporary_options[$type][$id]);
    }

    // Write to cache
    $view->cacheSet();
  }

  /**
   * Submit handler for removing an item from a view
   */
  public function remove(&$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');
    // Store the item back on the view
    list($was_defaulted, $is_defaulted) = $view->getOverrideValues($form, $form_state);
    $executable = $view->getExecutable();
    // If the display selection was changed toggle the override value.
    if ($was_defaulted != $is_defaulted) {
      $display = &$executable->displayHandlers->get($display_id);
      $display->optionsOverride($form, $form_state);
    }
    $executable->removeHandler($display_id, $type, $id);

    // Write to cache
    $view->cacheSet();
  }

}
