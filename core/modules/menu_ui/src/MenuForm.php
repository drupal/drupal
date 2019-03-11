<?php

namespace Drupal\menu_ui;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\menu_link_content\MenuLinkContentStorageInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for menu edit forms.
 *
 * @internal
 */
class MenuForm extends EntityForm {

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The menu_link_content storage handler.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentStorageInterface
   */
  protected $menuLinkContentStorage;

  /**
   * The overview tree form.
   *
   * @var array
   */
  protected $overviewTreeForm = ['#tree' => TRUE];

  /**
   * Constructs a MenuForm object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\menu_link_content\MenuLinkContentStorageInterface $menu_link_content_storage
   *   The menu link content storage handler.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, MenuLinkTreeInterface $menu_tree, LinkGeneratorInterface $link_generator, MenuLinkContentStorageInterface $menu_link_content_storage) {
    $this->menuLinkManager = $menu_link_manager;
    $this->menuTree = $menu_tree;
    $this->linkGenerator = $link_generator;
    $this->menuLinkContentStorage = $menu_link_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.link_tree'),
      $container->get('link_generator'),
      $container->get('entity_type.manager')->getStorage('menu_link_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $menu = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit menu %label', ['%label' => $menu->label()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $menu->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Menu name'),
      '#default_value' => $menu->id(),
      '#maxlength' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '#description' => $this->t('A unique name to construct the URL for the menu. It must only contain lowercase letters, numbers and hyphens.'),
      '#machine_name' => [
        'exists' => [$this, 'menuNameExists'],
        'source' => ['label'],
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ],
      // A menu's machine name cannot be changed.
      '#disabled' => !$menu->isNew() || $menu->isLocked(),
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => t('Administrative summary'),
      '#maxlength' => 512,
      '#default_value' => $menu->getDescription(),
    ];

    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => t('Menu language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $menu->language()->getId(),
    ];

    // Add menu links administration form for existing menus.
    if (!$menu->isNew() || $menu->isLocked()) {
      // Form API supports constructing and validating self-contained sections
      // within forms, but does not allow handling the form section's submission
      // equally separated yet. Therefore, we use a $form_state key to point to
      // the parents of the form section.
      // @see self::submitOverviewForm()
      $form_state->set('menu_overview_form_parents', ['links']);
      $form['links'] = [];
      $form['links'] = $this->buildOverviewForm($form['links'], $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * Returns whether a menu name already exists.
   *
   * @param string $value
   *   The name of the menu.
   *
   * @return bool
   *   Returns TRUE if the menu already exists, FALSE otherwise.
   */
  public function menuNameExists($value) {
    // Check first to see if a menu with this ID exists.
    if ($this->entityTypeManager->getStorage('menu')->getQuery()->condition('id', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }

    // Check for a link assigned to this menu.
    return $this->menuLinkManager->menuNameInUse($value);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $menu = $this->entity;
    $status = $menu->save();
    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Menu %label has been updated.', ['%label' => $menu->label()]));
      $this->logger('menu')->notice('Menu %label has been updated.', ['%label' => $menu->label(), 'link' => $edit_link]);
    }
    else {
      $this->messenger()->addStatus($this->t('Menu %label has been added.', ['%label' => $menu->label()]));
      $this->logger('menu')->notice('Menu %label has been added.', ['%label' => $menu->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if (!$this->entity->isNew() || $this->entity->isLocked()) {
      $this->submitOverviewForm($form, $form_state);
    }
  }

  /**
   * Form constructor to edit an entire menu tree at once.
   *
   * Shows for one menu the menu links accessible to the current user and
   * relevant operations.
   *
   * This form constructor can be integrated as a section into another form. It
   * relies on the following keys in $form_state:
   * - menu: A menu entity.
   * - menu_overview_form_parents: An array containing the parent keys to this
   *   form.
   * Forms integrating this section should call menu_overview_form_submit() from
   * their form submit handler.
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    $tree = $this->menuTree->load($this->entity->id(), new MenuTreeParameters());

    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    // Determine the delta; the number of weights to be made available.
    $count = function (array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };
    $delta = max($count($tree), 50);

    $form['links'] = [
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => [
        $this->t('Menu link'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#attributes' => [
        'id' => 'menu-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => \Drupal::menuTree()->maxDepth() - 1,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ],
      ],
    ];

    $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => $this->url('entity.menu.add_link_form', ['menu' => $this->entity->id()], [
        'query' => ['destination' => $this->entity->toUrl('edit-form')->toString()],
      ]),
    ]);
    $links = $this->buildOverviewTreeForm($tree, $delta);

    // Get the menu links which have pending revisions, and disable the
    // tabledrag if there are any.
    $edited_ids = array_filter(array_map(function ($element) {
      return is_array($element) && isset($element['#item']) && $element['#item']->link instanceof MenuLinkContent ? $element['#item']->link->getMetaData()['entity_id'] : NULL;
    }, $links));
    $pending_menu_link_ids = array_intersect($this->menuLinkContentStorage->getMenuLinkIdsWithPendingRevisions(), $edited_ids);
    if ($pending_menu_link_ids) {
      $form['help'] = [
        '#type' => 'container',
        'message' => [
          '#markup' => $this->formatPlural(
            count($pending_menu_link_ids),
            '%capital_name contains 1 menu link with pending revisions. Manipulation of a menu tree having links with pending revisions is not supported, but you can re-enable manipulation by getting each menu link to a published state.',
            '%capital_name contains @count menu links with pending revisions. Manipulation of a menu tree having links with pending revisions is not supported, but you can re-enable manipulation by getting each menu link to a published state.',
            [
              '%capital_name' => $this->entity->label(),
            ]
          ),
        ],
        '#attributes' => ['class' => ['messages', 'messages--warning']],
        '#weight' => -10,
      ];

      unset($form['links']['#tabledrag']);
      unset($form['links']['#header'][2]);
    }

    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $is_pending_menu_link = isset($element['#item']->link->getMetaData()['entity_id'])
          && in_array($element['#item']->link->getMetaData()['entity_id'], $pending_menu_link_ids);

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        if ($is_pending_menu_link) {
          $form['links'][$id]['#attributes']['class'][] = 'color-warning';
          $form['links'][$id]['#attributes']['class'][] = 'menu-link-content--pending-revision';
        }

        // TableDrag: Sort the table row according to its existing/configured weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = ['menu-parent'];
        $element['weight']['#attributes']['class'] = ['menu-weight'];
        $element['id']['#attributes']['class'] = ['menu-id'];

        $form['links'][$id]['title'] = [
          [
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ],
          $element['title'],
        ];
        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = ['checkbox', 'menu-enabled'];

        // Disallow changing the publishing status of a pending revision.
        if ($is_pending_menu_link) {
          $form['links'][$id]['enabled']['#access'] = FALSE;
        }

        if (!$pending_menu_link_ids) {
          $form['links'][$id]['weight'] = $element['weight'];
        }

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    }

    return $form;
  }

  /**
   * Recursive helper function for buildOverviewForm().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The tree retrieved by \Drupal\Core\Menu\MenuLinkTreeInterface::load().
   * @param int $delta
   *   The default number of menu items used in the menu weight selector is 50.
   *
   * @return array
   *   The overview tree form.
   */
  protected function buildOverviewTreeForm($tree, $delta) {
    $form = &$this->overviewTreeForm;
    $tree_access_cacheability = new CacheableMetadata();
    foreach ($tree as $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($link) {
        $id = 'menu_plugin_id:' . $link->getPluginId();
        $form[$id]['#item'] = $element;
        $form[$id]['#attributes'] = $link->isEnabled() ? ['class' => ['menu-enabled']] : ['class' => ['menu-disabled']];
        $form[$id]['title'] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
        if (!$link->isEnabled()) {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif ($id === 'menu_plugin_id:user.logout') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('<q>Log in</q> for anonymous users') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif (($url = $link->getUrlObject()) && $url->isRouted() && $url->getRouteName() == 'user.page') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('logged in users only') . ')';
        }

        $form[$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', ['@title' => $link->getTitle()]),
          '#title_display' => 'invisible',
          '#default_value' => $link->isEnabled(),
        ];
        $form[$id]['weight'] = [
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $link->getWeight(),
          '#title' => $this->t('Weight for @title', ['@title' => $link->getTitle()]),
          '#title_display' => 'invisible',
        ];
        $form[$id]['id'] = [
          '#type' => 'hidden',
          '#value' => $link->getPluginId(),
        ];
        $form[$id]['parent'] = [
          '#type' => 'hidden',
          '#default_value' => $link->getParent(),
        ];
        // Build a list of operations.
        $operations = [];
        $operations['edit'] = [
          'title' => $this->t('Edit'),
        ];
        // Allow for a custom edit link per plugin.
        $edit_route = $link->getEditRoute();
        if ($edit_route) {
          $operations['edit']['url'] = $edit_route;
          // Bring the user back to the menu overview.
          $operations['edit']['query'] = $this->getDestinationArray();
        }
        else {
          // Fall back to the standard edit link.
          $operations['edit'] += [
            'url' => Url::fromRoute('menu_ui.link_edit', ['menu_link_plugin' => $link->getPluginId()]),
          ];
        }
        // Links can either be reset or deleted, not both.
        if ($link->isResettable()) {
          $operations['reset'] = [
            'title' => $this->t('Reset'),
            'url' => Url::fromRoute('menu_ui.link_reset', ['menu_link_plugin' => $link->getPluginId()]),
          ];
        }
        elseif ($delete_link = $link->getDeleteRoute()) {
          $operations['delete']['url'] = $delete_link;
          $operations['delete']['query'] = $this->getDestinationArray();
          $operations['delete']['title'] = $this->t('Delete');
        }
        if ($link->isTranslatable()) {
          $operations['translate'] = [
            'title' => $this->t('Translate'),
            'url' => $link->getTranslateRoute(),
          ];
        }
        $form[$id]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
      }

      if ($element->subtree) {
        $this->buildOverviewTreeForm($element->subtree, $delta);
      }
    }

    $tree_access_cacheability
      ->merge(CacheableMetadata::createFromRenderArray($form))
      ->applyTo($form);

    return $form;
  }

  /**
   * Submit handler for the menu overview form.
   *
   * This function takes great care in saving parent items first, then items
   * underneath them. Saving items in the incorrect order can break the tree.
   */
  protected function submitOverviewForm(array $complete_form, FormStateInterface $form_state) {
    // Form API supports constructing and validating self-contained sections
    // within forms, but does not allow to handle the form section's submission
    // equally separated yet. Therefore, we use a $form_state key to point to
    // the parents of the form section.
    $parents = $form_state->get('menu_overview_form_parents');
    $input = NestedArray::getValue($form_state->getUserInput(), $parents);
    $form = &NestedArray::getValue($complete_form, $parents);

    // When dealing with saving menu items, the order in which these items are
    // saved is critical. If a changed child item is saved before its parent,
    // the child item could be saved with an invalid path past its immediate
    // parent. To prevent this, save items in the form in the same order they
    // are sent, ensuring parents are saved first, then their children.
    // See https://www.drupal.org/node/181126#comment-632270.
    $order = is_array($input) ? array_flip(array_keys($input)) : [];
    // Update our original form with the new order.
    $form = array_intersect_key(array_merge($order, $form), $form);

    $fields = ['weight', 'parent', 'enabled'];
    $form_links = $form['links'];
    foreach (Element::children($form_links) as $id) {
      if (isset($form_links[$id]['#item'])) {
        $element = $form_links[$id];
        $updated_values = [];
        // Update any fields that have changed in this menu item.
        foreach ($fields as $field) {
          if (isset($element[$field]['#value']) && $element[$field]['#value'] != $element[$field]['#default_value']) {
            $updated_values[$field] = $element[$field]['#value'];
          }
        }
        if ($updated_values) {
          // Use the ID from the actual plugin instance since the hidden value
          // in the form could be tampered with.
          $this->menuLinkManager->updateDefinition($element['#item']->link->getPLuginId(), $updated_values);
        }
      }
    }
  }

}
