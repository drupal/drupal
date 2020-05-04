<?php

namespace Drupal\comment\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_default",
 *   module = "comment",
 *   label = @Translation("Comment list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'pager_id' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The comment render controller.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Constructs a new CommentDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->viewBuilder = $entity_type_manager->getViewBuilder('comment');
    $this->storage = $entity_type_manager->getStorage('comment');
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
    $this->routeMatch = $route_match;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output = [];

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();

    $status = $items->status;

    if ($status != CommentItemInterface::HIDDEN && empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, ['search_result', 'search_index'])) {
      $comment_settings = $this->getFieldSettings();

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      $elements['#cache']['contexts'][] = 'user.permissions';
      if ($this->currentUser->hasPermission('access comments') || $this->currentUser->hasPermission('administer comments')) {
        $output['comments'] = [];

        if ($entity->get($field_name)->comment_count || $this->currentUser->hasPermission('administer comments')) {
          $mode = $comment_settings['default_mode'];
          $comments_per_page = $comment_settings['per_page'];
          $comments = $this->storage->loadThread($entity, $field_name, $mode, $comments_per_page, $this->getSetting('pager_id'));
          if ($comments) {
            $build = $this->viewBuilder->viewMultiple($comments, $this->getSetting('view_mode'));
            $build['pager']['#type'] = 'pager';
            // CommentController::commentPermalink() calculates the page number
            // where a specific comment appears and does a subrequest pointing to
            // that page, we need to pass that subrequest route to our pager to
            // keep the pager working.
            $build['pager']['#route_name'] = $this->routeMatch->getRouteObject();
            $build['pager']['#route_parameters'] = $this->routeMatch->getRawParameters()->all();
            if ($this->getSetting('pager_id')) {
              $build['pager']['#element'] = $this->getSetting('pager_id');
            }
            $output['comments'] += $build;
          }
        }
      }

      // Append comment form if the comments are open and the form is set to
      // display below the entity. Do not show the form for the print view mode.
      if ($status == CommentItemInterface::OPEN && $comment_settings['form_location'] == CommentItemInterface::FORM_BELOW && $this->viewMode != 'print') {
        // Only show the add comment form if the user has permission.
        $elements['#cache']['contexts'][] = 'user.roles';
        if ($this->currentUser->hasPermission('post comments')) {
          $output['comment_form'] = [
            '#lazy_builder' => [
              'comment.lazy_builders:renderForm',
              [
                $entity->getEntityTypeId(),
                $entity->id(),
                $field_name,
                $this->getFieldSetting('comment_type'),
              ],
            ],
            '#create_placeholder' => TRUE,
          ];
        }
      }

      $elements[] = $output + [
        '#comment_type' => $this->getFieldSetting('comment_type'),
        '#comment_display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => [],
        'comment_form' => [],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $view_modes = $this->getViewModes();
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Comments view mode'),
      '#description' => $this->t('Select the view mode used to show the list of comments.'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => $view_modes,
      // Only show the select element when there are more than one options.
      '#access' => count($view_modes) > 1,
    ];
    $element['pager_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Pager ID'),
      '#options' => range(0, 10),
      '#default_value' => $this->getSetting('pager_id'),
      '#description' => $this->t("Unless you're experiencing problems with pagers related to this field, you should leave this at 0. If using multiple pagers on one page you may need to set this number to a higher value so as not to conflict within the ?page= array. Large values will add a lot of commas to your URLs, so avoid if possible."),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $view_mode = $this->getSetting('view_mode');
    $view_modes = $this->getViewModes();
    $view_mode_label = isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : 'default';
    $summary = [$this->t('Comment view mode: @mode', ['@mode' => $view_mode_label])];
    if ($pager_id = $this->getSetting('pager_id')) {
      $summary[] = $this->t('Pager ID: @id', ['@id' => $pager_id]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($mode = $this->getSetting('view_mode')) {
      if ($bundle = $this->getFieldSetting('comment_type')) {
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
        if ($display = EntityViewDisplay::load("comment.$bundle.$mode")) {
          $dependencies[$display->getConfigDependencyKey()][] = $display->getConfigDependencyName();
        }
      }
    }
    return $dependencies;
  }

  /**
   * Provides a list of comment view modes for the configured comment type.
   *
   * @return array
   *   Associative array keyed by view mode key and having the view mode label
   *   as value.
   */
  protected function getViewModes() {
    return $this->entityDisplayRepository->getViewModeOptionsByBundle('comment', $this->getFieldSetting('comment_type'));
  }

}
