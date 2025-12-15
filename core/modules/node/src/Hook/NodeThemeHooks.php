<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\node\Form\NodePreviewForm;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Theme hook implementations for the node module.
 */
class NodeThemeHooks {

  public function __construct(
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly RendererInterface $renderer,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    #[AutowireServiceClosure('form_builder')]
    protected readonly \Closure $formBuilderClosure,
    protected readonly ThemeSettingsProvider $themeSettingsProvider,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'node' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessNode',
      ],
      'node_add_list' => [
        'variables' => [
          'content' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessNodeAddList',
      ],
      'node_edit_form' => [
        'render element' => 'form',
      ],
      // @todo Delete the next three entries as part of
      // https://www.drupal.org/node/3015623
      'field__node__title' => [
        'base hook' => 'field',
      ],
      'field__node__uid' => [
        'base hook' => 'field',
      ],
      'field__node__created' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_node')]
  public function themeSuggestionsNode(array $variables): array {
    $suggestions = [];
    $node = $variables['elements']['#node'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

    $suggestions[] = 'node__' . $sanitized_view_mode;
    $suggestions[] = 'node__' . $node->bundle();
    $suggestions[] = 'node__' . $node->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'node__' . $node->id();
    $suggestions[] = 'node__' . $node->id() . '__' . $sanitized_view_mode;

    return $suggestions;
  }

  /**
   * Implements hook_preprocess_HOOK() for node field templates.
   */
  #[Hook('preprocess_field__node')]
  public function preprocessFieldNode(&$variables): void {
    // Set a variable 'is_inline' in cases where inline markup is required,
    // without any block elements such as <div>.
    if ($variables['element']['#is_page_title'] ?? FALSE) {
      // Page title is always inline because it will be displayed inside <h1>.
      $variables['is_inline'] = TRUE;
    }
    elseif (in_array($variables['field_name'], ['created', 'uid', 'title'], TRUE)) {
      // Display created, uid and title fields inline because they will be
      // displayed inline by node.html.twig. Skip this if the field
      // display is configurable and skipping has been enabled.
      // @todo Delete as part of https://www.drupal.org/node/3015623

      /** @var \Drupal\node\NodeInterface $node */
      $node = $variables['element']['#object'];
      $skip_custom_preprocessing = $node->getEntityType()->get('enable_base_field_custom_preprocess_skipping');
      $variables['is_inline'] = !$skip_custom_preprocessing || !$node->getFieldDefinition($variables['field_name'])->isDisplayConfigurable('view');
    }
  }

  /**
   * Prepares variables for node templates.
   *
   * Default template: node.html.twig.
   *
   * Most themes use their own copy of node.html.twig. The default is located
   * inside "/core/modules/node/templates/node.html.twig". Look in there for the
   * full list of variables.
   *
   * By default this function performs special preprocessing of some base fields
   * so they are available as variables in the template. For example 'title'
   * appears as 'label'. This preprocessing is skipped if:
   * - a module makes the field's display configurable via the field UI by means
   *   of BaseFieldDefinition::setDisplayConfigurable()
   * - AND the additional entity type property
   *   'enable_base_field_custom_preprocess_skipping' has been set using
   *   hook_entity_type_build().
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An array of elements to display in view mode.
   *   - node: The node object.
   *   - view_mode: View mode; e.g., 'full', 'teaser', etc.
   *
   * @see hook_entity_type_build()
   * @see \Drupal\Core\Field\BaseFieldDefinition::setDisplayConfigurable()
   */
  public function preprocessNode(&$variables): void {
    $variables['view_mode'] = $variables['elements']['#view_mode'];

    // The teaser variable is deprecated.
    $variables['deprecations']['teaser'] = "'teaser' is deprecated in drupal:11.1.0 and is removed in drupal:12.0.0. Use 'view_mode' instead. See https://www.drupal.org/node/3458185";
    $variables['teaser'] = $variables['view_mode'] == 'teaser';

    // The 'metadata' variable was originally added to support RDF, which has
    // now been moved to contrib. It was needed because it is not possible to
    // extend the markup of the 'submitted' variable generically.
    $variables['deprecations']['metadata'] = "'metadata' is deprecated in drupal:11.1.0 and is removed in drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3458638";

    $variables['node'] = $variables['elements']['#node'];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    $skip_custom_preprocessing = $node->getEntityType()->get('enable_base_field_custom_preprocess_skipping');

    // Make created, uid and title fields available separately. Skip this custom
    // preprocessing if the field display is configurable and skipping has been
    // enabled.
    // @todo https://www.drupal.org/project/drupal/issues/3015623
    //   Eventually delete this code and matching template lines. Using
    //   $variables['content'] is more flexible and consistent.
    $submitted_configurable = $node->getFieldDefinition('created')->isDisplayConfigurable('view') || $node->getFieldDefinition('uid')->isDisplayConfigurable('view');
    if (!$skip_custom_preprocessing || !$submitted_configurable) {
      $variables['date'] = !empty($variables['elements']['created']) ? $this->renderer->render($variables['elements']['created']) : '';
      $variables['author_name'] = !empty($variables['elements']['uid']) ? $this->renderer->render($variables['elements']['uid']) : '';
      unset($variables['elements']['created'], $variables['elements']['uid']);
    }

    if (isset($variables['elements']['title']) && (!$skip_custom_preprocessing || !$node->getFieldDefinition('title')->isDisplayConfigurable('view'))) {
      $variables['label'] = $variables['elements']['title'];
      unset($variables['elements']['title']);
    }

    $variables['url'] = !$node->isNew() ? $node->toUrl('canonical')->toString() : NULL;

    // The page variable is deprecated.
    $variables['deprecations']['page'] = "'page' is deprecated in drupal:11.3.0 and is removed in drupal:13.0.0. Use 'view_mode' instead. See https://www.drupal.org/node/3458593";
    // The 'page' variable is set to TRUE in two occasions:
    // - The view mode is 'full' and we are on the 'node.view' route.
    // - The node is in preview and view mode is either 'full' or 'default'.
    $variables['page'] = FALSE;
    if ($variables['view_mode'] == 'full'
      && ($this->routeMatch->getRouteName() == 'entity.node.canonical'
        && $this->routeMatch->getRawParameter('node') == $node->id() || (isset($node->in_preview)
          && in_array($node->preview_view_mode, ['full', 'default'])))) {
      $variables['page'] = TRUE;
    }

    // Helpful $content variable for templates.
    $variables += ['content' => []];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }

    if (isset($variables['date'])) {
      // Display post information on certain node types. This only occurs if
      // custom preprocessing occurred for both of the created and uid fields.
      // @todo https://www.drupal.org/project/drupal/issues/3015623
      //   Eventually delete this code and matching template lines. Using a
      //   field formatter is more flexible and consistent.
      $node_type = $node->type->entity;
      $variables['author_attributes'] = new Attribute();
      $variables['display_submitted'] = $node_type->displaySubmitted();
      if ($variables['display_submitted'] && $this->themeSettingsProvider->getSetting('features.node_user_picture')) {
        // To change user picture settings (e.g. image style), edit the
        // 'compact' view mode on the User entity. Note that the 'compact'
        // view mode might not be configured, so remember to always check the
        // theme setting first.
        $node_owner = $node->getOwner();
        if ($node_owner) {
          $variables['author_picture'] = $this->entityTypeManager
            ->getViewBuilder('user')
            ->view($node_owner, 'compact');
        }
      }
    }
  }

  /**
   * Prepares variables for list of available node type templates.
   *
   * Default template: node-add-list.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - content: An array of content types.
   *
   * @see \Drupal\node\Controller\NodeController::addPage()
   */
  public function preprocessNodeAddList(&$variables): void {
    $variables['types'] = [];
    if (!empty($variables['content'])) {
      foreach ($variables['content'] as $type) {
        $variables['types'][$type->id()] = [
          'type' => $type->id(),
          'add_link' => Link::fromTextAndUrl($type->label(), Url::fromRoute('entity.node.add_form', ['node_type' => $type->id()]))->toString(),
          'description' => [
            '#markup' => $type->getDescription(),
          ],
        ];
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    // If on an individual node page or node preview page, add the node type to
    // the body classes.
    if (($node = $this->routeMatch->getParameter('node')) || ($node = $this->routeMatch->getParameter('node_preview'))) {
      if ($node instanceof NodeInterface) {
        $variables['node_type'] = $node->getType();
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'node') {
      switch ($variables['elements']['#plugin_id']) {
        case 'node_syndicate_block':
          $variables['attributes']['role'] = 'complementary';
          break;
      }
    }
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    // Add 'Back to content editing' link on preview page.
    if ($this->routeMatch->getRouteName() == 'entity.node.preview') {
      $form_builder = ($this->formBuilderClosure)();
      assert($form_builder instanceof FormBuilderInterface);
      $page_top['node_preview'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'node-preview-container',
            'container-inline',
          ],
        ],
        'view_mode' => $form_builder->getForm(NodePreviewForm::class, $this->routeMatch->getParameter('node_preview')),
      ];
    }
  }

}
