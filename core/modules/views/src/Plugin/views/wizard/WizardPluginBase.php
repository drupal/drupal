<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\wizard\WizardPluginBase.
 */

namespace Drupal\views\Plugin\views\wizard;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Plugin\views\wizard\WizardInterface;

/**
 * @defgroup views_wizard_plugins Views wizard plugins
 * @{
 * Plugins for Views wizards.
 *
 * Wizard handlers implement \Drupal\views\Plugin\views\wizard\WizardInterface,
 * and usually extend \Drupal\views\Plugin\views\wizard\WizardPluginBase. They
 * must be annotated with \Drupal\views\Annotation\ViewsWizard annotation,
 * and they must be in namespace directory Plugin\views\wizard.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base class for Views wizard plugins.
 *
 * This is a very generic Views Wizard class that can be constructed for any
 * base table.
 */
abstract class WizardPluginBase extends PluginBase implements WizardInterface {

  use UrlGeneratorTrait;

  /**
   * The base table connected with the wizard.
   *
   * @var string
   */
  protected $base_table;

  /**
   * The entity type connected with the wizard.
   *
   * There might be base tables connected with entity types, if not this would
   * be empty.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Contains the information from entity_get_info of the $entity_type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * An array of validated view objects, keyed by a hash.
   *
   * @var array
   */
  protected $validated_views = array();

  /**
   * The table column used for sorting by create date of this wizard.
   *
   * @var string
   */
  protected $createdColumn;

  /**
   * Views items configuration arrays for filters added by the wizard.
   *
   * @var array
   */
  protected $filters = array();

  /**
   * Views items configuration arrays for sorts added by the wizard.
   *
   * @var array
   */
  protected $sorts = array();

  /**
   * The available store criteria.
   *
   * @var array
   */
  protected $availableSorts = array();

  /**
   * Default values for filters.
   *
   * By default, filters are not exposed and added to the first non-reserved
   * filter group.
   *
   * @var array()
   */
  protected $filter_defaults = array(
    'id' => NULL,
    'expose' => array('operator' => FALSE),
    'group' => 1,
  );

  /**
   * Constructs a WizardPluginBase object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->base_table = $this->definition['base_table'];

    $entity_types = \Drupal::entityManager()->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($this->base_table == $entity_type->getBaseTable() || $this->base_table == $entity_type->getDataTable()) {
        $this->entityType = $entity_type;
        $this->entityTypeId = $entity_type_id;
      }
    }
  }

  /**
   * Gets the createdColumn property.
   *
   * @return string
   *   The name of the column containing the created date.
   */
  public function getCreatedColumn() {
    return $this->createdColumn;
  }

  /**
   * Gets the filters property.
   *
   * @return array
   */
  public function getFilters() {
    $filters = array();

    $default = $this->filter_defaults;

    foreach ($this->filters as $name => $info) {
      $default['id'] = $name;
      $filters[$name] = $info + $default;
    }

    return $filters;
  }

  /**
   * Gets the availableSorts property.
   *
   * @return array
   */
  public function getAvailableSorts() {
    return $this->availableSorts;
  }

  /**
   * Gets the sorts property.
   *
   * @return array
   */
  public function getSorts() {
    return $this->sorts;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $style_options = Views::fetchPluginNames('style', 'normal', array($this->base_table));
    $feed_row_options = Views::fetchPluginNames('row', 'feed', array($this->base_table));
    $path_prefix = $this->url('<none>', [], ['absolute' => TRUE]);

    // Add filters and sorts which apply to the view as a whole.
    $this->buildFilters($form, $form_state);
    $this->buildSorts($form, $form_state);

    $form['displays']['page'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Page settings'),
      '#attributes' => array('class' => array('views-attachment', 'fieldset-no-legend')),
      '#tree' => TRUE,
    );
    $form['displays']['page']['create'] = array(
      '#title' => $this->t('Create a page'),
      '#type' => 'checkbox',
      '#attributes' => array('class' => array('strong')),
      '#default_value' => FALSE,
      '#id' => 'edit-page-create',
    );

    // All options for the page display are included in this container so they
    // can be hidden as a group when the "Create a page" checkbox is unchecked.
    $form['displays']['page']['options'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('options-set')),
      '#states' => array(
        'visible' => array(
          ':input[name="page[create]"]' => array('checked' => TRUE),
        ),
      ),
      '#prefix' => '<div><div id="edit-page-wrapper">',
      '#suffix' => '</div></div>',
      '#parents' => array('page'),
    );

    $form['displays']['page']['options']['title'] = array(
      '#title' => $this->t('Page title'),
      '#type' => 'textfield',
      '#maxlength' => 255,
    );
    $form['displays']['page']['options']['path'] = array(
      '#title' => $this->t('Path'),
      '#type' => 'textfield',
      '#field_prefix' => $path_prefix,
      // Account for the leading backslash.
      '#maxlength' => 254,
    );
    $form['displays']['page']['options']['style'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Page display settings'),
      '#attributes' => array('class' => array('container-inline', 'fieldset-no-legend')),
    );

    // Create the dropdown for choosing the display format.
    $form['displays']['page']['options']['style']['style_plugin'] = array(
      '#title' => $this->t('Display format'),
      '#type' => 'select',
      '#options' => $style_options,
    );
    $style_form = &$form['displays']['page']['options']['style'];
    $style_form['style_plugin']['#default_value'] = static::getSelected($form_state, array('page', 'style', 'style_plugin'), 'default', $style_form['style_plugin']);
    // Changing this dropdown updates $form['displays']['page']['options'] via
    // AJAX.
    views_ui_add_ajax_trigger($style_form, 'style_plugin', array('displays', 'page', 'options'));

    $this->buildFormStyle($form, $form_state, 'page');
    $form['displays']['page']['options']['items_per_page'] = array(
      '#title' => $this->t('Items to display'),
      '#type' => 'number',
      '#default_value' => 10,
      '#min' => 0,
    );
    $form['displays']['page']['options']['pager'] = array(
      '#title' => $this->t('Use a pager'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    );
    $form['displays']['page']['options']['link'] = array(
      '#title' => $this->t('Create a menu link'),
      '#type' => 'checkbox',
      '#id' => 'edit-page-link',
    );
    $form['displays']['page']['options']['link_properties'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(
          ':input[name="page[link]"]' => array('checked' => TRUE),
        ),
      ),
      '#prefix' => '<div id="edit-page-link-properties-wrapper">',
      '#suffix' => '</div>',
    );
    if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
      $menu_options = menu_ui_get_menus();
    }
    else {
      // These are not yet translated.
      $menu_options = menu_list_system_menus();
      foreach ($menu_options as $name => $title) {
        $menu_options[$name] = $this->t($title);
      }
    }
    $form['displays']['page']['options']['link_properties']['menu_name'] = array(
      '#title' => $this->t('Menu'),
      '#type' => 'select',
      '#options' => $menu_options,
    );
    $form['displays']['page']['options']['link_properties']['title'] = array(
      '#title' => $this->t('Link text'),
      '#type' => 'textfield',
    );
    // Only offer a feed if we have at least one available feed row style.
    if ($feed_row_options) {
      $form['displays']['page']['options']['feed'] = array(
        '#title' => $this->t('Include an RSS feed'),
        '#type' => 'checkbox',
        '#id' => 'edit-page-feed',
      );
      $form['displays']['page']['options']['feed_properties'] = array(
        '#type' => 'container',
        '#states' => array(
          'visible' => array(
            ':input[name="page[feed]"]' => array('checked' => TRUE),
          ),
        ),
        '#prefix' => '<div id="edit-page-feed-properties-wrapper">',
        '#suffix' => '</div>',
      );
      $form['displays']['page']['options']['feed_properties']['path'] = array(
        '#title' => $this->t('Feed path'),
        '#type' => 'textfield',
        '#field_prefix' => $path_prefix,
        // Account for the leading backslash.
        '#maxlength' => 254,
      );
      // This will almost never be visible.
      $form['displays']['page']['options']['feed_properties']['row_plugin'] = array(
        '#title' => $this->t('Feed row style'),
        '#type' => 'select',
        '#options' => $feed_row_options,
        '#default_value' => key($feed_row_options),
        '#access' => (count($feed_row_options) > 1),
        '#states' => array(
          'visible' => array(
            ':input[name="page[feed]"]' => array('checked' => TRUE),
          ),
        ),
        '#prefix' => '<div id="edit-page-feed-properties-row-plugin-wrapper">',
        '#suffix' => '</div>',
      );
    }

    // Only offer the block settings if the module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      $form['displays']['block'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Block settings'),
        '#attributes' => array('class' => array('views-attachment', 'fieldset-no-legend')),
        '#tree' => TRUE,
      );
      $form['displays']['block']['create'] = array(
        '#title' => $this->t('Create a block'),
        '#type' => 'checkbox',
        '#attributes' => array('class' => array('strong')),
        '#id' => 'edit-block-create',
      );

      // All options for the block display are included in this container so
      // they can be hidden as a group when the "Create a block" checkbox is
      // unchecked.
      $form['displays']['block']['options'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('options-set')),
        '#states' => array(
          'visible' => array(
            ':input[name="block[create]"]' => array('checked' => TRUE),
          ),
        ),
        '#prefix' => '<div id="edit-block-wrapper">',
        '#suffix' => '</div>',
        '#parents' => array('block'),
      );

      $form['displays']['block']['options']['title'] = array(
        '#title' => $this->t('Block title'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      );
      $form['displays']['block']['options']['style'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Block display settings'),
        '#attributes' => array('class' => array('container-inline', 'fieldset-no-legend')),
      );

      // Create the dropdown for choosing the display format.
      $form['displays']['block']['options']['style']['style_plugin'] = array(
        '#title' => $this->t('Display format'),
        '#type' => 'select',
        '#options' => $style_options,
      );
      $style_form = &$form['displays']['block']['options']['style'];
      $style_form['style_plugin']['#default_value'] = static::getSelected($form_state, array('block', 'style', 'style_plugin'), 'default', $style_form['style_plugin']);
      // Changing this dropdown updates $form['displays']['block']['options']
      // via AJAX.
      views_ui_add_ajax_trigger($style_form, 'style_plugin', array('displays', 'block', 'options'));

      $this->buildFormStyle($form, $form_state, 'block');
      $form['displays']['block']['options']['items_per_page'] = array(
        '#title' => $this->t('Items per block'),
        '#type' => 'number',
        '#default_value' => 5,
        '#min' => 0,
      );
      $form['displays']['block']['options']['pager'] = array(
        '#title' => $this->t('Use a pager'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      );
    }

    // Only offer the REST export settings if the module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('rest')) {
      $form['displays']['rest_export'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('REST export settings'),
        '#attributes' => array('class' => array('views-attachment', 'fieldset-no-legend')),
        '#tree' => TRUE,
      );
      $form['displays']['rest_export']['create'] = array(
        '#title' => $this->t('Provide a REST export'),
        '#type' => 'checkbox',
        '#attributes' => array('class' => array('strong')),
        '#id' => 'edit-rest-export-create',
      );

      // All options for the REST export display are included in this container
      // so they can be hidden as a group when the "Provide a REST export"
      // checkbox is unchecked.
      $form['displays']['rest_export']['options'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('options-set')),
        '#states' => array(
          'visible' => array(
            ':input[name="rest_export[create]"]' => array('checked' => TRUE),
          ),
        ),
        '#prefix' => '<div id="edit-rest-export-wrapper">',
        '#suffix' => '</div>',
        '#parents' => array('rest_export'),
      );

      $form['displays']['rest_export']['options']['path'] = array(
        '#title' => $this->t('REST export path'),
        '#type' => 'textfield',
        '#field_prefix' => $path_prefix,
        // Account for the leading backslash.
        '#maxlength' => 254,
      );
    }

    return $form;
  }

  /**
   * Gets the current value of a #select element, from within a form constructor function.
   *
   * This function is intended for use in highly dynamic forms (in particular the
   * add view wizard) which are rebuilt in different ways depending on which
   * triggering element (AJAX or otherwise) was most recently fired. For example,
   * sometimes it is necessary to decide how to build one dynamic form element
   * based on the value of a different dynamic form element that may not have
   * even been present on the form the last time it was submitted. This function
   * takes care of resolving those conflicts and gives you the proper current
   * value of the requested #select element.
   *
   * By necessity, this function sometimes uses non-validated user input from
   * FormState::$input in making its determination. Although it performs some
   * minor validation of its own, it is not complete. The intention is that the
   * return value of this function should only be used to help decide how to
   * build the current form the next time it is reloaded, not to be saved as if
   * it had gone through the normal, final form validation process. Do NOT use
   * the results of this function for any other purpose besides deciding how to
   * build the next version of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param $parents
   *   An array of parent keys that point to the part of the submitted form
   *   values that are expected to contain the element's value (in the case where
   *   this form element was actually submitted). In a simple case (assuming
   *   #tree is TRUE throughout the form), if the select element is located in
   *   $form['wrapper']['select'], so that the submitted form values would
   *   normally be found in $form_state->getValue(array('wrapper', 'select')),
   *   you would pass array('wrapper', 'select') for this parameter.
   * @param $default_value
   *   The default value to return if the #select element does not currently have
   *   a proper value set based on the submitted input.
   * @param $element
   *   An array representing the current version of the #select element within
   *   the form.
   *
   * @return
   *   The current value of the #select element. A common use for this is to feed
   *   it back into $element['#default_value'] so that the form will be rendered
   *   with the correct value selected.
   */
  public static function getSelected(FormStateInterface $form_state, $parents, $default_value, $element) {
    // For now, don't trust this to work on anything but a #select element.
    if (!isset($element['#type']) || $element['#type'] != 'select' || !isset($element['#options'])) {
      return $default_value;
    }

    // If there is a user-submitted value for this element that matches one of
    // the currently available options attached to it, use that. We need to check
    // FormState::$input rather than $form_state->getValues() here because the
    // triggering element often has the #limit_validation_errors property set to
    // prevent unwanted errors elsewhere on the form. This means that the
    // $form_state->getValues() array won't be complete. We could make it complete
    // by adding each required part of the form to the #limit_validation_errors
    // property individually as the form is being built, but this is difficult to
    // do for a highly dynamic and extensible form. This method is much simpler.
    $user_input = &$form_state->getUserInput();
    if (!empty($user_input)) {
      $key_exists = NULL;
      $submitted = NestedArray::getValue($user_input, $parents, $key_exists);
      // Check that the user-submitted value is one of the allowed options before
      // returning it. This is not a substitute for actual form validation;
      // rather it is necessary because, for example, the same select element
      // might have #options A, B, and C under one set of conditions but #options
      // D, E, F under a different set of conditions. So the form submission
      // might have occurred with option A selected, but when the form is rebuilt
      // option A is no longer one of the choices. In that case, we don't want to
      // use the value that was submitted anymore but rather fall back to the
      // default value.
      if ($key_exists && in_array($submitted, array_keys($element['#options']))) {
        return $submitted;
      }
    }

    // Fall back on returning the default value if nothing was returned above.
    return $default_value;
  }

  /**
   * Adds the style options to the wizard form.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   * @param string $type
   *   The display ID (e.g. 'page' or 'block').
   */
  protected function buildFormStyle(array &$form, FormStateInterface $form_state, $type) {
    $style_form = &$form['displays'][$type]['options']['style'];
    $style = $style_form['style_plugin']['#default_value'];
    $style_plugin = Views::pluginManager('style')->createInstance($style);
    if (isset($style_plugin) && $style_plugin->usesRowPlugin()) {
      $options = $this->rowStyleOptions();
      $style_form['row_plugin'] = array(
        '#type' => 'select',
        '#title' => $this->t('of'),
        '#options' => $options,
        '#access' => count($options) > 1,
      );
      // For the block display, the default value should be "titles (linked)",
      // if it's available (since that's the most common use case).
      $block_with_linked_titles_available = ($type == 'block' && isset($options['titles_linked']));
      $default_value = $block_with_linked_titles_available ? 'titles_linked' : key($options);
      $style_form['row_plugin']['#default_value'] = static::getSelected($form_state, array($type, 'style', 'row_plugin'), $default_value, $style_form['row_plugin']);
      // Changing this dropdown updates the individual row options via AJAX.
      views_ui_add_ajax_trigger($style_form, 'row_plugin', array('displays', $type, 'options', 'style', 'row_options'));

      // This is the region that can be updated by AJAX. The base class doesn't
      // add anything here, but child classes can.
      $style_form['row_options'] = array(
        '#theme_wrappers' => array('container'),
      );
    }
    elseif ($style_plugin->usesFields()) {
      $style_form['row_plugin'] = array('#markup' => '<span>' . $this->t('of fields') . '</span>');
    }
  }

  /**
   * Retrieves row style plugin names.
   *
   * @return array
   *   Returns the plugin names available for the base table of the wizard.
   */
  protected function rowStyleOptions() {
    // Get all available row plugins by default.
    $options = Views::fetchPluginNames('row', 'normal', array($this->base_table));
    return $options;
  }

  /**
   * Builds the form structure for selecting the view's filters.
   *
   * By default, this adds "of type" and "tagged with" filters (when they are
   * available).
   */
  protected function buildFilters(&$form, FormStateInterface $form_state) {
    module_load_include('inc', 'views_ui', 'admin');

    $bundles = entity_get_bundles($this->entityTypeId);
    // If the current base table support bundles and has more than one (like user).
    if (!empty($bundles) && $this->entityType && $this->entityType->hasKey('bundle')) {
      // Get all bundles and their human readable names.
      $options = array('all' => $this->t('All'));
      foreach ($bundles as $type => $bundle) {
        $options[$type] = $bundle['label'];
      }
      $form['displays']['show']['type'] = array(
        '#type' => 'select',
        '#title' => $this->t('of type'),
        '#options' => $options,
      );
      $selected_bundle = static::getSelected($form_state, array('show', 'type'), 'all', $form['displays']['show']['type']);
      $form['displays']['show']['type']['#default_value'] = $selected_bundle;
      // Changing this dropdown updates the entire content of $form['displays']
      // via AJAX, since each bundle might have entirely different fields
      // attached to it, etc.
      views_ui_add_ajax_trigger($form['displays']['show'], 'type', array('displays'));
    }
  }

  /**
   * Builds the form structure for selecting the view's sort order.
   *
   * By default, this adds a "sorted by [date]" filter (when it is available).
   */
  protected function buildSorts(&$form, FormStateInterface $form_state) {
    $sorts = array(
      'none' => $this->t('Unsorted'),
    );
    // Check if we are allowed to sort by creation date.
    $created_column = $this->getCreatedColumn();
    if ($created_column) {
      $sorts += array(
        $created_column . ':DESC' => $this->t('Newest first'),
        $created_column . ':ASC' => $this->t('Oldest first'),
      );
    }
    if ($available_sorts = $this->getAvailableSorts()) {
      $sorts += $available_sorts;
    }

    // If there is no sorts option available continue.
    if (!empty($sorts)) {
      $form['displays']['show']['sort'] = array(
        '#type' => 'select',
        '#title' => $this->t('sorted by'),
        '#options' => $sorts,
        '#default_value' => isset($created_column) ? $created_column . ':DESC' : 'none',
      );
    }
  }

  /**
   * Instantiates a view object from form values.
   *
   * @return \Drupal\views_ui\ViewUI
   *   The instantiated view UI object.
   */
  protected function instantiateView($form, FormStateInterface $form_state) {
    // Build the basic view properties and create the view.
    $values = array(
      'id' => $form_state->getValue('id'),
      'label' => $form_state->getValue('label'),
      'description' => $form_state->getValue('description'),
      'base_table' => $this->base_table,
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
    );

    $view = entity_create('view', $values);

    // Build all display options for this view.
    $display_options = $this->buildDisplayOptions($form, $form_state);

    // Allow the fully built options to be altered. This happens before adding
    // the options to the view, so that once they are eventually added we will
    // be able to get all the overrides correct.
    $this->alterDisplayOptions($display_options, $form, $form_state);

    $this->addDisplays($view, $display_options, $form, $form_state);

    return new ViewUI($view);
  }

  /**
   * Builds an array of display options for the view.
   *
   * @return array
   *   An array whose keys are the names of each display and whose values are
   *   arrays of options for that display.
   */
  protected function buildDisplayOptions($form, FormStateInterface $form_state) {
    // Display: Master
    $display_options['default'] = $this->defaultDisplayOptions();
    $display_options['default'] += array(
      'filters' => array(),
      'sorts' => array(),
    );
    $display_options['default']['filters'] += $this->defaultDisplayFilters($form, $form_state);
    $display_options['default']['sorts'] += $this->defaultDisplaySorts($form, $form_state);

    // Display: Page
    if (!$form_state->isValueEmpty(array('page', 'create'))) {
      $display_options['page'] = $this->pageDisplayOptions($form, $form_state);

      // Display: Feed (attached to the page)
      if (!$form_state->isValueEmpty(array('page', 'feed'))) {
        $display_options['feed'] = $this->pageFeedDisplayOptions($form, $form_state);
      }
    }

    // Display: Block
    if (!$form_state->isValueEmpty(array('block', 'create'))) {
      $display_options['block'] = $this->blockDisplayOptions($form, $form_state);
    }

    // Display: REST export.
    if (!$form_state->isValueEmpty(['rest_export', 'create'])) {
      $display_options['rest_export'] = $this->restExportDisplayOptions($form, $form_state);
    }

    return $display_options;
  }

  /**
   * Alters the full array of display options before they are added to the view.
   */
  protected function alterDisplayOptions(&$display_options, $form, FormStateInterface $form_state) {
    foreach ($display_options as $display_type => $options) {
      // Allow style plugins to hook in and provide some settings.
      $style_plugin = Views::pluginManager('style')->createInstance($options['style']['type']);
      $style_plugin->wizardSubmit($form, $form_state, $this, $display_options, $display_type);
    }
  }

  /**
   * Adds the array of display options to the view, with appropriate overrides.
   */
  protected function addDisplays(View $view, $display_options, $form, FormStateInterface $form_state) {
    // Initialize and store the view executable to get the display plugin
    // instances.
    $executable = $view->getExecutable();

    // Display: Master
    $default_display = $executable->newDisplay('default', 'Master', 'default');
    foreach ($display_options['default'] as $option => $value) {
      $default_display->setOption($option, $value);
    }

    // Display: Page
    if (isset($display_options['page'])) {
      $display = $executable->newDisplay('page', 'Page', 'page_1');
      // The page display is usually the main one (from the user's point of
      // view). Its options should therefore become the overall view defaults,
      // so that new displays which are added later automatically inherit them.
      $this->setDefaultOptions($display_options['page'], $display, $default_display);

      // Display: Feed (attached to the page).
      if (isset($display_options['feed'])) {
        $display = $executable->newDisplay('feed', 'Feed', 'feed_1');
        $this->setOverrideOptions($display_options['feed'], $display, $default_display);
      }
    }

    // Display: Block.
    if (isset($display_options['block'])) {
      $display = $executable->newDisplay('block', 'Block', 'block_1');
      // When there is no page, the block display options should become the
      // overall view defaults.
      if (!isset($display_options['page'])) {
        $this->setDefaultOptions($display_options['block'], $display, $default_display);
      }
      else {
        $this->setOverrideOptions($display_options['block'], $display, $default_display);
      }
    }

    // Display: REST export.
    if (isset($display_options['rest_export'])) {
      $display = $executable->newDisplay('rest_export', 'REST export', 'rest_export_1');
      // If there is no page or block, the REST export display options should
      // become the overall view defaults.
      if (!isset($display_options['page']) && !isset($display_options['block'])) {
        $this->setDefaultOptions($display_options['rest_export'], $display, $default_display);
      }
      else {
        $this->setOverrideOptions($display_options['rest_export'], $display, $default_display);
      }
    }

    // Initialize displays and merge all plugin default values.
    $executable->mergeDefaults();
  }

  /**
   * Assembles the default display options for the view.
   *
   * Most wizards will need to override this method to provide some fields
   * or a different row plugin.
   *
   * @return array
   *   Returns an array of display options.
   */
  protected function defaultDisplayOptions() {
    $display_options = array();
    $display_options['access']['type'] = 'none';
    $display_options['cache']['type'] = 'tag';
    $display_options['query']['type'] = 'views_query';
    $display_options['exposed_form']['type'] = 'basic';
    $display_options['pager']['type'] = 'full';
    $display_options['style']['type'] = 'default';
    $display_options['row']['type'] = 'fields';

    // Add default options array to each plugin type.
    foreach ($display_options as &$options) {
      $options['options'] = array();
    }

    // Add a least one field so the view validates and the user has a preview.
    // The base field can provide a default in its base settings; otherwise,
    // choose the first field with a field handler.
    $default_table = $this->base_table;
    $data = Views::viewsData()->get($default_table);
    if (isset($data['table']['base']['defaults']['field'])) {
      $default_field = $data['table']['base']['defaults']['field'];
      // If the table for the default field is different to the base table,
      // load the view table data for this table.
      if (isset($data['table']['base']['defaults']['table']) && $data['table']['base']['defaults']['table'] != $default_table) {
        $default_table = $data['table']['base']['defaults']['table'];
        $data = Views::viewsData()->get($default_table);
      }
    }
    else {
      foreach ($data as $default_field => $field_data) {
        if (isset($field_data['field']['id'])) {
          break;
        }
      }
    }
    // @todo Refactor the code to use ViewExecutable::addHandler. See
    //   https://www.drupal.org/node/2383157.
    $display_options['fields'][$default_field] = array(
      'table' => $default_table,
      'field' => $default_field,
      'id' => $default_field,
      'entity_type' => isset($data[$default_field]['entity type']) ? $data[$default_field]['entity type'] : NULL,
      'entity_field' => isset($data[$default_field]['entity field']) ? $data[$default_field]['entity field'] : NULL,
      'plugin_id' => $data[$default_field]['field']['id'],
    );

    return $display_options;
  }

  /**
   * Retrieves all filter information used by the default display.
   *
   * Additional to the one provided by the plugin this method takes care about
   * adding additional filters based on user input.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   An array of filter arrays keyed by ID. A sort array contains the options
   *   accepted by a filter handler.
   */
  protected function defaultDisplayFilters($form, FormStateInterface $form_state) {
    $filters = array();

    // Add any filters provided by the plugin.
    foreach ($this->getFilters() as $name => $info) {
      $filters[$name] = $info;
    }

    // Add any filters specified by the user when filling out the wizard.
    $filters = array_merge($filters, $this->defaultDisplayFiltersUser($form, $form_state));

    return $filters;
  }

  /**
   * Retrieves filter information based on user input for the default display.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   An array of filter arrays keyed by ID. A sort array contains the options
   *   accepted by a filter handler.
   */
  protected function defaultDisplayFiltersUser(array $form, FormStateInterface $form_state) {
    $filters = array();

    if (($type = $form_state->getValue(array('show', 'type'))) && $type != 'all') {
      $bundle_key = $this->entityType->getKey('bundle');
      // Figure out the table where $bundle_key lives. It may not be the same as
      // the base table for the view; the taxonomy vocabulary machine_name, for
      // example, is stored in taxonomy_vocabulary, not taxonomy_term_data.
      module_load_include('inc', 'views_ui', 'admin');
      $fields = Views::viewsDataHelper()->fetchFields($this->base_table, 'filter');
      if (isset($fields[$this->base_table . '.' . $bundle_key])) {
        $table = $this->base_table;
      }
      else {
        foreach ($fields as $field_name => $value) {
          if ($pos = strpos($field_name, '.' . $bundle_key)) {
            $table = substr($field_name, 0, $pos);
            break;
          }
        }
      }
      $table_data = Views::viewsData()->get($table);
      // If the 'in' operator is being used, map the values to an array.
      $handler = $table_data[$bundle_key]['filter']['id'];
      $handler_definition = Views::pluginManager('filter')->getDefinition($handler);
      if ($handler == 'in_operator' || is_subclass_of($handler_definition['class'], 'Drupal\\views\\Plugin\\views\\filter\\InOperator')) {
        $value = array($type => $type);
      }
      // Otherwise, use just a single value.
      else {
        $value = $type;
      }

      $filters[$bundle_key] = array(
        'id' => $bundle_key,
        'table' => $table,
        'field' => $bundle_key,
        'value' => $value,
        'entity_type' => isset($table_data['table']['entity type']) ? $table_data['table']['entity type'] : NULL,
        'entity_field' => isset($table_data[$bundle_key]['entity field']) ? $table_data[$bundle_key]['entity field'] : NULL,
        'plugin_id' => $handler,
      );
    }

    return $filters;
  }

  /**
   * Retrieves all sort information used by the default display.
   *
   * Additional to the one provided by the plugin this method takes care about
   * adding additional sorts based on user input.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   An array of sort arrays keyed by ID. A sort array contains the options
   *   accepted by a sort handler.
   */
  protected function defaultDisplaySorts($form, FormStateInterface $form_state) {
    $sorts = array();

    // Add any sorts provided by the plugin.
    foreach ($this->getSorts() as $name => $info) {
      $sorts[$name] = $info;
    }

    // Add any sorts specified by the user when filling out the wizard.
    $sorts = array_merge($sorts, $this->defaultDisplaySortsUser($form, $form_state));

    return $sorts;
  }

  /**
   * Retrieves sort information based on user input for the default display.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   An array of sort arrays keyed by ID. A sort array contains the options
   *   accepted by a sort handler.
   */
  protected function defaultDisplaySortsUser($form, FormStateInterface $form_state) {
    $sorts = array();

    // Don't add a sort if there is no form value or the user set the sort to
    // 'none'.
    if (($sort_type = $form_state->getValue(array('show', 'sort'))) && $sort_type != 'none') {
      list($column, $sort) = explode(':', $sort_type);
      // Column either be a column-name or the table-column-name.
      $column = explode('-', $column);
      if (count($column) > 1) {
        $table = $column[0];
        $column = $column[1];
      }
      else {
        $table = $this->base_table;
        $column = $column[0];
      }

      // If the input is invalid, for example when the #default_value contains
      // created from node, but the wizard type is another base table, make
      // sure it is not added. This usually don't happen if you have js
      // enabled.
      $data = Views::viewsData()->get($table);
      if (isset($data[$column]['sort'])) {
        $sorts[$column] = array(
          'id' => $column,
          'table' => $table,
          'field' => $column,
          'order' => $sort,
          'entity_type' => isset($data['table']['entity type']) ? $data['table']['entity type'] : NULL,
          'entity_field' => isset($data[$column]['entity field']) ? $data[$column]['entity field'] : NULL,
          'plugin_id' => $data[$column]['sort']['id'],
       );
      }
    }

    return $sorts;
  }

  /**
   * Retrieves the page display options.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   Returns an array of display options.
   */
  protected function pageDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = array();
    $page = $form_state->getValue('page');
    $display_options['title'] = $page['title'];
    $display_options['path'] = $page['path'];
    $display_options['style'] = array('type' => $page['style']['style_plugin']);
    // Not every style plugin supports row style plugins.
    // Make sure that the selected row plugin is a valid one.
    $options = $this->rowStyleOptions();
    $display_options['row'] = array('type' => (isset($page['style']['row_plugin']) && isset($options[$page['style']['row_plugin']])) ? $page['style']['row_plugin'] : 'fields');

    // If the specific 0 items per page, use no pager.
    if (empty($page['items_per_page'])) {
      $display_options['pager']['type'] = 'none';
    }
    // If the user checked the pager checkbox use a full pager.
    elseif (isset($page['pager'])) {
      $display_options['pager']['type'] = 'full';
    }
    // If the user doesn't have checked the checkbox use the pager which just
    // displays a certain amount of items.
    else {
      $display_options['pager']['type'] = 'some';
    }
    $display_options['pager']['options']['items_per_page'] = $page['items_per_page'];

    // Generate the menu links settings if the user checked the link checkbox.
    if (!empty($page['link'])) {
      $display_options['menu']['type'] = 'normal';
      $display_options['menu']['title'] = $page['link_properties']['title'];
      $display_options['menu']['menu_name'] = $page['link_properties']['menu_name'];
    }
    return $display_options;
  }

  /**
   * Retrieves the block display options.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   Returns an array of display options.
   */
  protected function blockDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = array();
    $block = $form_state->getValue('block');
    $display_options['title'] = $block['title'];
    $display_options['style'] = array('type' => $block['style']['style_plugin']);
    $display_options['row'] = array('type' => isset($block['style']['row_plugin']) ? $block['style']['row_plugin'] : 'fields');
    $display_options['pager']['type'] = $block['pager'] ? 'full' : (empty($block['items_per_page']) ? 'none' : 'some');
    $display_options['pager']['options']['items_per_page'] = $block['items_per_page'];
    return $display_options;
  }

  /**
   * Retrieves the REST export display options from the submitted form values.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   Returns an array of display options.
   */
  protected function restExportDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = array();
    $display_options['path'] = $form_state->getValue(['rest_export', 'path']);
    $display_options['style'] = array('type' => 'serializer');

    return $display_options;
  }

  /**
   * Retrieves the feed display options.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   Returns an array of display options.
   */
  protected function pageFeedDisplayOptions($form, FormStateInterface $form_state) {
    $display_options = array();
    $display_options['pager']['type'] = 'some';
    $display_options['style'] = array('type' => 'rss');
    $display_options['row'] = array('type' => $form_state->getValue(array('page', 'feed_properties', 'row_plugin')));
    $display_options['path'] = $form_state->getValue(array('page', 'feed_properties', 'path'));
    $display_options['title'] = $form_state->getValue(array('page', 'title'));
    $display_options['displays'] = array(
      'default' => 'default',
      'page_1' => 'page_1',
    );
    return $display_options;
  }

  /**
   * Sets options for a display and makes them the default options if possible.
   *
   * This function can be used to set options for a display when it is desired
   * that the options also become the defaults for the view whenever possible.
   * This should be done for the "primary" display created in the view wizard,
   * so that new displays which the user adds later will be similar to this
   * one.
   *
   * @param array $options
   *   An array whose keys are the name of each option and whose values are the
   *   desired values to set.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The display handler which the options will be applied to. The default
   *   display will actually be assigned the options (and this display will
   *   inherit them) when possible.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $default_display
   *   The default display handler, which will store the options when possible.
   */
  protected function setDefaultOptions($options, DisplayPluginBase $display, DisplayPluginBase $default_display) {
    foreach ($options as $option => $value) {
      // If the default display supports this option, set the value there.
      // Otherwise, set it on the provided display.
      $default_value = $default_display->getOption($option);
      if (isset($default_value)) {
        $default_display->setOption($option, $value);
      }
      else {
        $display->setOption($option, $value);
      }
    }
  }

  /**
   * Sets options for a display, inheriting from the defaults when possible.
   *
   * This function can be used to set options for a display when it is desired
   * that the options inherit from the default display whenever possible. This
   * avoids setting too many options as overrides, which will be harder for the
   * user to modify later. For example, if $this->setDefaultOptions() was
   * previously called on a page display and then this function is called on a
   * block display, and if the user entered the same title for both displays in
   * the views wizard, then the view will wind up with the title stored as the
   * default (with the page and block both inheriting from it).
   *
   * @param array $options
   *   An array whose keys are the name of each option and whose values are the
   *   desired values to set.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The display handler which the options will be applied to. The default
   *   display will actually be assigned the options (and this display will
   *   inherit them) when possible.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $default_display
   *   The default display handler, which will store the options when possible.
   */
  protected function setOverrideOptions(array $options, DisplayPluginBase $display, DisplayPluginBase $default_display) {
    foreach ($options as $option => $value) {
      // Only override the default value if it is different from the value that
      // was provided.
      $default_value = $default_display->getOption($option);
      if (!isset($default_value)) {
        $display->setOption($option, $value);
      }
      elseif ($default_value !== $value) {
        $display->overrideOption($option, $value);
      }
    }
  }

  /**
   * Retrieves a validated view for a form submission.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   * @param bool $unset
   *   Should the view be removed from the list of validated views.
   *
   * @return \Drupal\views_ui\ViewUI $view
   *   The validated view object.
   */
  protected function retrieveValidatedView(array $form, FormStateInterface $form_state, $unset = TRUE) {
    // @todo Figure out why all this hashing is done. Wouldn't it be easier to
    //   store a single entry and that's it?
    $key = hash('sha256', serialize($form_state->getValues()));
    $view = (isset($this->validated_views[$key]) ? $this->validated_views[$key] : NULL);
    if ($unset) {
      unset($this->validated_views[$key]);
    }
    return $view;
  }

  /**
   * Stores a validated view from a form submission.
   *
   * @param array $form
   *   The full wizard form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the wizard form.
   * @param \Drupal\views_ui\ViewUI $view
   *   The validated view object.
   */
  protected function setValidatedView(array $form, FormStateInterface $form_state, ViewUI $view) {
    $key = hash('sha256', serialize($form_state->getValues()));
    $this->validated_views[$key] = $view;
  }

  /**
   * Implements Drupal\views\Plugin\views\wizard\WizardInterface::validate().
   *
   * Instantiates the view from the form submission and validates its values.
   */
  public function validateView(array $form, FormStateInterface $form_state) {
    $view = $this->instantiateView($form, $form_state);
    $errors = $view->getExecutable()->validate();

    if (empty($errors)) {
      $this->setValidatedView($form, $form_state, $view);
    }

    return $errors;
  }

  /**
   * {@inheritDoc}
   */
  public function createView(array $form, FormStateInterface $form_state) {
    $view = $this->retrieveValidatedView($form, $form_state);
    if (empty($view)) {
      throw new WizardException('Attempted to create a view with values that have not been validated.');
    }
    return $view;
  }

}

/**
 * @}
 */
