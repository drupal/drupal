<?php

namespace Drupal\node\Plugin\views\wizard;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating node views with the wizard.
 *
 * @ViewsWizard(
 *   id = "node",
 *   base_table = "node_field_data",
 *   title = @Translation("Content")
 * )
 */
class Node extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
   */
  protected $createdColumn = 'node_field_data-created';

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Node constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service
   *   The entity bundle info service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $parent_form_selector
   *   The parent form selector service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $bundle_info_service, EntityDisplayRepositoryInterface $entity_display_repository, EntityFieldManagerInterface $entity_field_manager, MenuParentFormSelectorInterface $parent_form_selector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $bundle_info_service, $parent_form_selector);

    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('menu.parent_form_selector')
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::getAvailableSorts().
   *
   * @return array
   *   An array whose keys are the available sort options and whose
   *   corresponding values are human readable labels.
   */
  public function getAvailableSorts() {
    // You can't execute functions in properties, so override the method
    return [
      'node_field_data-title:ASC' => $this->t('Title'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function rowStyleOptions() {
    $options = [];
    $options['teasers'] = $this->t('teasers');
    $options['full_posts'] = $this->t('full posts');
    $options['titles'] = $this->t('titles');
    $options['titles_linked'] = $this->t('titles (linked)');
    $options['fields'] = $this->t('fields');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access content';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    // Add the title field, so that the display has content if the user switches
    // to a row style that uses fields.
    /* Field: Content: Title */
    $display_options['fields']['title']['id'] = 'title';
    $display_options['fields']['title']['table'] = 'node_field_data';
    $display_options['fields']['title']['field'] = 'title';
    $display_options['fields']['title']['entity_type'] = 'node';
    $display_options['fields']['title']['entity_field'] = 'title';
    $display_options['fields']['title']['label'] = '';
    $display_options['fields']['title']['alter']['alter_text'] = 0;
    $display_options['fields']['title']['alter']['make_link'] = 0;
    $display_options['fields']['title']['alter']['absolute'] = 0;
    $display_options['fields']['title']['alter']['trim'] = 0;
    $display_options['fields']['title']['alter']['word_boundary'] = 0;
    $display_options['fields']['title']['alter']['ellipsis'] = 0;
    $display_options['fields']['title']['alter']['strip_tags'] = 0;
    $display_options['fields']['title']['alter']['html'] = 0;
    $display_options['fields']['title']['hide_empty'] = 0;
    $display_options['fields']['title']['empty_zero'] = 0;
    $display_options['fields']['title']['settings']['link_to_entity'] = 1;
    $display_options['fields']['title']['plugin_id'] = 'field';

    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayFiltersUser(array $form, FormStateInterface $form_state) {
    $filters = parent::defaultDisplayFiltersUser($form, $form_state);

    $tids = [];
    if ($values = $form_state->getValue(['show', 'tagged_with'])) {
      foreach ($values as $value) {
        $tids[] = $value['target_id'];
      }
    }
    if (!empty($tids)) {
      $vid = reset($form['displays']['show']['tagged_with']['#selection_settings']['target_bundles']);
      $filters['tid'] = [
        'id' => 'tid',
        'table' => 'taxonomy_index',
        'field' => 'tid',
        'value' => $tids,
        'vid' => $vid,
        'plugin_id' => 'taxonomy_index_tid',
      ];
      // If the user entered more than one valid term in the autocomplete
      // field, they probably intended both of them to be applied.
      if (count($tids) > 1) {
        $filters['tid']['operator'] = 'and';
        // Sort the terms so the filter will be displayed as it normally would
        // on the edit screen.
        sort($filters['tid']['value']);
      }
    }

    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  protected function pageDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::pageDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(['page', 'style', 'row_plugin']);
    $row_options = $form_state->getValue(['page', 'style', 'row_options'], []);
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::blockDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(['block', 'style', 'row_plugin']);
    $row_options = $form_state->getValue(['block', 'style', 'row_options'], []);
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Set the row style and row style plugins to the display_options.
   */
  protected function display_options_row(&$display_options, $row_plugin, $row_options) {
    switch ($row_plugin) {
      case 'full_posts':
        $display_options['row']['type'] = 'entity:node';
        $display_options['row']['options']['view_mode'] = 'full';
        break;

      case 'teasers':
        $display_options['row']['type'] = 'entity:node';
        $display_options['row']['options']['view_mode'] = 'teaser';
        break;

      case 'titles_linked':
      case 'titles':
        $display_options['row']['type'] = 'fields';
        $display_options['fields']['title']['id'] = 'title';
        $display_options['fields']['title']['table'] = 'node_field_data';
        $display_options['fields']['title']['field'] = 'title';
        $display_options['fields']['title']['settings']['link_to_entity'] = $row_plugin === 'titles_linked';
        $display_options['fields']['title']['plugin_id'] = 'field';
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::buildFilters().
   *
   * Add some options for filter by taxonomy terms.
   */
  protected function buildFilters(&$form, FormStateInterface $form_state) {
    parent::buildFilters($form, $form_state);

    if (isset($form['displays']['show']['type'])) {
      $selected_bundle = static::getSelected($form_state, ['show', 'type'], 'all', $form['displays']['show']['type']);
    }

    // Add the "tagged with" filter to the view.

    // We construct this filter using taxonomy_index.tid (which limits the
    // filtering to a specific vocabulary) rather than
    // taxonomy_term_field_data.name (which matches terms in any vocabulary).
    // This is because it is a more commonly-used filter that works better with
    // the autocomplete UI, and also to avoid confusion with other vocabularies
    // on the site that may have terms with the same name but are not used for
    // free tagging.

    // The downside is that if there *is* more than one vocabulary on the site
    // that is used for free tagging, the wizard will only be able to make the
    // "tagged with" filter apply to one of them (see below for the method it
    // uses to choose).

    // Find all "tag-like" taxonomy fields associated with the view's
    // entities. If a particular entity type (i.e., bundle) has been
    // selected above, then we only search for taxonomy fields associated
    // with that bundle. Otherwise, we use all bundles.
    $bundles = array_keys($this->bundleInfoService->getBundleInfo($this->entityTypeId));
    // Double check that this is a real bundle before using it (since above
    // we added a dummy option 'all' to the bundle list on the form).
    if (isset($selected_bundle) && in_array($selected_bundle, $bundles)) {
      $bundles = [$selected_bundle];
    }
    $tag_fields = [];
    foreach ($bundles as $bundle) {
      $display = $this->entityDisplayRepository->getFormDisplay($this->entityTypeId, $bundle);
      $taxonomy_fields = array_filter($this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $bundle), function (FieldDefinitionInterface $field_definition) {
        return $field_definition->getType() == 'entity_reference' && $field_definition->getSetting('target_type') == 'taxonomy_term';
      });
      foreach ($taxonomy_fields as $field_name => $field) {
        $widget = $display->getComponent($field_name);
        // We define "tag-like" taxonomy fields as ones that use the
        // "Autocomplete (Tags style)" widget.
        if (!empty($widget) && $widget['type'] == 'entity_reference_autocomplete_tags') {
          $tag_fields[$field_name] = $field;
        }
      }
    }
    if (!empty($tag_fields)) {
      // If there is more than one "tag-like" taxonomy field available to
      // the view, we can only make our filter apply to one of them (as
      // described above). We choose 'field_tags' if it is available, since
      // that is created by the Standard install profile in core and also
      // commonly used by contrib modules; thus, it is most likely to be
      // associated with the "main" free-tagging vocabulary on the site.
      if (array_key_exists('field_tags', $tag_fields)) {
        $tag_field_name = 'field_tags';
      }
      else {
        $tag_field_name = key($tag_fields);
      }
      // Add the autocomplete textfield to the wizard.
      $form['displays']['show']['tagged_with'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('tagged with'),
        '#target_type' => 'taxonomy_term',
        '#tags' => TRUE,
        '#size' => 30,
        '#maxlength' => 1024,
      ];
      $target_bundles = $tag_fields[$tag_field_name]->getSetting('handler_settings')['target_bundles'] ?? FALSE;
      if (!$target_bundles) {
        $target_bundles = array_keys($this->bundleInfoService->getBundleInfo('taxonomy_term'));
      }
      $form['displays']['show']['tagged_with']['#selection_settings']['target_bundles'] = $target_bundles;
    }
  }

}
