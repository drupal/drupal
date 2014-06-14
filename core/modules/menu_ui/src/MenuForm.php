<?php

/**
 * @file
 * Contains \Drupal\menu_ui\MenuForm.
 */

namespace Drupal\menu_ui;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for menu edit forms.
 */
class MenuForm extends EntityForm {

  /**
   * The factory for entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

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
   * The overview tree form.
   *
   * @var array
   */
  protected $overviewTreeForm = array('#tree' => TRUE);

  /**
   * Constructs a MenuForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query_factory
   *   The factory for entity queries.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   */
  public function __construct(QueryFactory $entity_query_factory, MenuLinkManagerInterface $menu_link_manager, MenuLinkTreeInterface $menu_tree) {
    $this->entityQueryFactory = $entity_query_factory;
    $this->menuLinkManager = $menu_link_manager;
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $menu = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit menu %label', array('%label' => $menu->label()));
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $menu->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Menu name'),
      '#default_value' => $menu->id(),
      '#maxlength' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '#description' => $this->t('A unique name to construct the URL for the menu. It must only contain lowercase letters, numbers and hyphens.'),
      '#machine_name' => array(
        'exists' => array($this, 'menuNameExists'),
        'source' => array('label'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ),
      // A menu's machine name cannot be changed.
      '#disabled' => !$menu->isNew() || $menu->isLocked(),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Administrative summary'),
      '#maxlength' => 512,
      '#default_value' => $menu->description,
    );

    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Menu language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $menu->langcode,
    );

    // Add menu links administration form for existing menus.
    if (!$menu->isNew() || $menu->isLocked()) {
      // Form API supports constructing and validating self-contained sections
      // within forms, but does not allow handling the form section's submission
      // equally separated yet. Therefore, we use a $form_state key to point to
      // the parents of the form section.
      // @see self::submitOverviewForm()
      $form_state['menu_overview_form_parents'] = array('links');
      $form['links'] = array();
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
    if ($this->entityQueryFactory->get('menu')->condition('id', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }

    // Check for a link assigned to this menu.
    return $this->menuLinkManager->menuNameInUse($value);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $menu = $this->entity;
    if (!$menu->isNew() || $menu->isLocked()) {
      $this->submitOverviewForm($form, $form_state);
    }

    $status = $menu->save();

    $edit_link = \Drupal::linkGenerator()->generateFromUrl($this->t('Edit'), $this->entity->urlInfo());
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Menu %label has been updated.', array('%label' => $menu->label())));
      watchdog('menu', 'Menu %label has been updated.', array('%label' => $menu->label()), WATCHDOG_NOTICE, $edit_link);
    }
    else {
      drupal_set_message($this->t('Menu %label has been added.', array('%label' => $menu->label())));
      watchdog('menu', 'Menu %label has been added.', array('%label' => $menu->label()), WATCHDOG_NOTICE, $edit_link);
    }

    $form_state['redirect_route'] = $this->entity->urlInfo('edit-form');
  }

  /**
   * Recursively count the number of menu links in a tree.
   */
  protected function countElements($tree, $count = 0) {
    foreach ($tree as $element) {
      $count++;
      if (!empty($element['below'])) {
        $this->countElements($element['below'], $count);
      }
    }
    return $count;
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
  protected function buildOverviewForm(array &$form, array &$form_state) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    $form['#tree'] = TRUE;
    $form['#theme'] = 'menu_overview_form';
    $form_state += array('menu_overview_form_parents' => array());

    $form['#attached']['css'] = array(drupal_get_path('module', 'menu') . '/css/menu.admin.css');
    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);

    $tree = $this->menuTree->buildAllData($this->entity->id());

    $count = $this->countElements($tree);
    $delta = max($count, 50);

    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    $form = array_merge($form, $this->buildOverviewTreeForm($tree, $delta));
    $form['#empty_text'] = $this->t('There are no menu links yet. <a href="@link">Add link</a>.', array('@link' => url('admin/structure/menu/manage/' . $this->entity->id() .'/add')));

    return $form;
  }

  /**
   * Recursive helper function for buildOverviewForm().
   *
   * @param $tree
   *   The menu_tree retrieved by menu_tree_data.
   * @param $delta
   *   The default number of menu items used in the menu weight selector is 50.
   *
   * @return array
   *   The overview tree form.
   */
  protected function buildOverviewTreeForm($tree, $delta) {
    $form = &$this->overviewTreeForm;
    foreach ($tree as $data) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $item */
      $item = $data['link'];
      if ($item) {
        $id = 'menu_plugin_id:' . $item->getPluginId();
        $form[$id]['#item'] = $data;
        $form[$id]['#attributes'] = $item->isHidden() ? array('class' => array('menu-disabled')) : array('class' => array('menu-enabled'));
        $form[$id]['title']['#markup'] = \Drupal::linkGenerator()->generateFromUrl($item->getTitle(), $item->getUrlObject(), $item->getOptions());
        if ($item->isHidden()) {
          $form[$id]['title']['#markup'] .= ' (' . $this->t('disabled') . ')';
        }
        elseif (($url = $item->getUrlObject()) && !$url->isExternal() && $url->getRouteName() == 'user.page') {
          $form[$id]['title']['#markup'] .= ' (' . $this->t('logged in users only') . ')';
        }

        $form[$id]['enabled'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', array('@title' => $item->getTitle())),
          '#title_display' => 'invisible',
          '#default_value' => !$item->isHidden(),
        );
        $form[$id]['weight'] = array(
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $item->getWeight(),
          '#title' => $this->t('Weight for @title', array('@title' => $item->getTitle())),
          '#title_display' => 'invisible',
        );
        $form[$id]['id'] = array(
          '#type' => 'hidden',
          '#value' => $item->getPluginId(),
        );
        $form[$id]['parent'] = array(
          '#type' => 'hidden',
          '#default_value' => $item->getParent(),
        );
        // Build a list of operations.
        $operations = array();
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
        );
        // Allow for a custom edit link per plugin.
        $edit_route = $item->getEditRoute();
        if ($edit_route) {
          $operations['edit'] += $edit_route;
          // Bring the user back to the menu overview.
          $operations['edit']['query']['destination'] = $this->entity->url();
        }
        else {
          // Fall back to the standard edit link.
          $operations['edit'] += array(
            'route_name' => 'menu_ui.link_edit',
            'route_parameters' => array('menu_link_plugin' => $item->getPluginId()),
          );
        }
        // Links can either be reset or deleted, not both.
        if ($item->isResetable()) {
          $operations['reset'] = array(
            'title' => $this->t('Reset'),
            'route_name' => 'menu_ui.link_reset',
            'route_parameters' => array('menu_link_plugin' => $item->getPluginId()),
          );
        }
        elseif ($delete_link = $item->getDeleteRoute()) {
          $operations['delete'] = $delete_link;
          $operations['delete']['query']['destination'] = $this->entity->url();
          $operations['delete']['title'] = $this->t('Delete');
        }
        if ($item->isTranslatable()) {
          $operations['translate'] = array(
            'title' => $this->t('Translate'),
          ) + (array) $item->getTranslateRoute();
        }
        $form[$id]['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
      }

      if ($data['below']) {
        $this->buildOverviewTreeForm($data['below'], $delta);
      }
    }
    return $form;
  }

  /**
   * Submit handler for the menu overview form.
   *
   * This function takes great care in saving parent items first, then items
   * underneath them. Saving items in the incorrect order can break the tree.
   */
  protected function submitOverviewForm(array $complete_form, array &$form_state) {
    // Form API supports constructing and validating self-contained sections
    // within forms, but does not allow to handle the form section's submission
    // equally separated yet. Therefore, we use a $form_state key to point to
    // the parents of the form section.
    $parents = $form_state['menu_overview_form_parents'];
    $input = NestedArray::getValue($form_state['input'], $parents);
    $form = &NestedArray::getValue($complete_form, $parents);

    // When dealing with saving menu items, the order in which these items are
    // saved is critical. If a changed child item is saved before its parent,
    // the child item could be saved with an invalid path past its immediate
    // parent. To prevent this, save items in the form in the same order they
    // are sent, ensuring parents are saved first, then their children.
    // See http://drupal.org/node/181126#comment-632270
    $order = is_array($input) ? array_flip(array_keys($input)) : array();
    // Update our original form with the new order.
    $form = array_intersect_key(array_merge($order, $form), $form);

    $fields = array('weight', 'parent', 'enabled');
    foreach (Element::children($form) as $id) {
      if (isset($form[$id]['#item'])) {
        $element = $form[$id];
        $updated_values = array();
        // Update any fields that have changed in this menu item.
        foreach ($fields as $field) {
          if ($element[$field]['#value'] != $element[$field]['#default_value']) {
            // Hidden is a special case, the form value needs to be reversed.
            if ($field == 'enabled') {
              $updated_values['hidden'] = $element['enabled']['#value'] ? 0 : 1;
            }
            else {
              $updated_values[$field] = $element[$field]['#value'];
            }
          }
        }
        if ($updated_values) {
          // Use the ID from the actual plugin instance since the hidden value
          // in the form could be tampered with.
          $this->menuLinkManager->updateLink($element['#item']['link']->getPLuginId(), $updated_values);
        }
      }
    }
  }

}
