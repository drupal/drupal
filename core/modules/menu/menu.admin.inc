<?php

/**
 * @file
 * Administrative page callbacks for menu module.
 */

use Drupal\menu_link\Plugin\Core\Entity\MenuLink;
use Drupal\system\Plugin\Core\Entity\Menu;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Menu callback which shows an overview page of all the custom menus and their descriptions.
 */
function menu_overview_page() {
  return Drupal::entityManager()
    ->getListController('menu')
    ->render();
}

/**
 * Page callback: Presents the menu creation form.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see menu_menu()
 */
function menu_menu_add() {
  $menu = entity_create('menu', array());
  return entity_get_form($menu);
}

/**
 * Page callback: Presents the menu edit form.
 *
 * @param \Drupal\system\Plugin\Core\Entity\Menu $menu
 *   The menu to edit.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see menu_menu()
 */
function menu_menu_edit(Menu $menu) {
  drupal_set_title(t('Edit menu %label', array('%label' => $menu->label())), PASS_THROUGH);
  return entity_get_form($menu);
}

/**
 * Form constructor to edit an entire menu tree at once.
 *
 * Shows for one menu the menu links accessible to the current user and
 * relevant operations.
 *
 * This form constructor can be integrated as a section into another form. It
 * relies on the following keys in $form_state:
 * - menu: A loaded menu definition, as returned by menu_load().
 * - menu_overview_form_parents: An array containing the parent keys to this
 *   form.
 * Forms integrating this section should call menu_overview_form_submit() from
 * their form submit handler.
 */
function menu_overview_form($form, &$form_state) {
  global $menu_admin;

  // Ensure that menu_overview_form_submit() knows the parents of this form
  // section.
  $form['#tree'] = TRUE;
  $form['#theme'] = 'menu_overview_form';
  $form_state += array('menu_overview_form_parents' => array());

  $form['#attached']['css'] = array(drupal_get_path('module', 'menu') . '/css/menu.admin.css');

  $links = array();
  $query = Drupal::entityQuery('menu_link')
    ->condition('menu_name', $form_state['menu']->id());
  for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
    $query->sort('p' . $i, 'ASC');
  }
  $result = $query->execute();

  if (!empty($result)) {
    $links = menu_link_load_multiple($result);
  }

  $delta = max(count($links), 50);
  $tree = menu_tree_data($links);
  $node_links = array();
  menu_tree_collect_node_links($tree, $node_links);
  // We indicate that a menu administrator is running the menu access check.
  $menu_admin = TRUE;
  menu_tree_check_access($tree, $node_links);
  $menu_admin = FALSE;

  // Inline the "Add link" action so it displays right above the table of
  // links. No access check needed, since this form has the same access
  // restriction as adding menu items to the menu.
  $form['inline_actions'] = array(
    '#prefix' => '<ul class="action-links">',
    '#suffix' => '</ul>',
  );
  $form['inline_actions']['add'] = array(
    '#theme' => 'menu_local_action',
    '#link' => array(
      'href' => 'admin/structure/menu/manage/' . $form_state['menu']->id() . '/add',
      'title' => t('Add link'),
    ),
  );
  $form = array_merge($form, _menu_overview_tree_form($tree, $delta));
  $form['#empty_text'] = t('There are no menu links yet. <a href="@link">Add link</a>.', array('@link' => url('admin/structure/menu/manage/' . $form_state['menu']->id() .'/add')));

  return $form;
}

/**
 * Recursive helper function for menu_overview_form().
 *
 * @param $tree
 *   The menu_tree retrieved by menu_tree_data.
 * @param $delta
 *   The default number of menu items used in the menu weight selector is 50.
 */
function _menu_overview_tree_form($tree, $delta = 50) {
  $form = &drupal_static(__FUNCTION__, array('#tree' => TRUE));
  foreach ($tree as $data) {
    $title = '';
    $item = $data['link'];
    // Don't show callbacks; these have $item['hidden'] < 0.
    if ($item && $item['hidden'] >= 0) {
      $mlid = 'mlid:' . $item['mlid'];
      $form[$mlid]['#item'] = $item;
      $form[$mlid]['#attributes'] = $item['hidden'] ? array('class' => array('menu-disabled')) : array('class' => array('menu-enabled'));
      $form[$mlid]['title']['#markup'] = l($item['title'], $item['href'], $item['localized_options']);
      if ($item['hidden']) {
        $form[$mlid]['title']['#markup'] .= ' (' . t('disabled') . ')';
      }
      elseif ($item['link_path'] == 'user' && $item['module'] == 'system') {
        $form[$mlid]['title']['#markup'] .= ' (' . t('logged in users only') . ')';
      }

      $form[$mlid]['hidden'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable @title menu link', array('@title' => $item['title'])),
        '#title_display' => 'invisible',
        '#default_value' => !$item['hidden'],
      );
      $form[$mlid]['weight'] = array(
        '#type' => 'weight',
        '#delta' => $delta,
        '#default_value' => $item['weight'],
        '#title_display' => 'invisible',
        '#title' => t('Weight for @title', array('@title' => $item['title'])),
      );
      $form[$mlid]['mlid'] = array(
        '#type' => 'hidden',
        '#value' => $item['mlid'],
      );
      $form[$mlid]['plid'] = array(
        '#type' => 'hidden',
        '#default_value' => $item['plid'],
      );
      // Build a list of operations.
      $operations = array();
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => 'admin/structure/menu/item/' . $item['mlid'] . '/edit',
      );
      $operations['edit'] = array('#type' => 'link', '#title' => t('Edit'), '#href' => 'admin/structure/menu/item/' . $item['mlid'] . '/edit');
      // Only items created by the menu module can be deleted.
      if ($item['module'] == 'menu' || $item['updated'] == 1) {
        $links['delete'] = array(
          'title' => t('Delete'),
          'href' => 'admin/structure/menu/item/' . $item['mlid'] . '/delete',
        );
        $operations['delete'] = array('#type' => 'link', '#title' => t('Delete'), '#href' => 'admin/structure/menu/item/' . $item['mlid'] . '/delete');
      }
      // Set the reset column.
      elseif ($item['module'] == 'system' && $item['customized']) {
        $links['reset'] = array(
          'title' => t('Reset'),
          'href' => 'admin/structure/menu/item/' . $item['mlid'] . '/reset',
        );
        $operations['reset'] = array('#type' => 'link', '#title' => t('Reset'), '#href' => 'admin/structure/menu/item/' . $item['mlid'] . '/reset');
      }
      $form[$mlid]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }

    if ($data['below']) {
      _menu_overview_tree_form($data['below'], $delta);
    }
  }
  return $form;
}

/**
 * Submit handler for the menu overview form.
 *
 * This function takes great care in saving parent items first, then items
 * underneath them. Saving items in the incorrect order can break the menu tree.
 *
 * @see menu_overview_form()
 */
function menu_overview_form_submit($complete_form, &$form_state) {
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

  $updated_items = array();
  $fields = array('weight', 'plid');
  foreach (element_children($form) as $mlid) {
    if (isset($form[$mlid]['#item'])) {
      $element = $form[$mlid];
      // Update any fields that have changed in this menu item.
      foreach ($fields as $field) {
        if ($element[$field]['#value'] != $element[$field]['#default_value']) {
          $element['#item'][$field] = $element[$field]['#value'];
          $updated_items[$mlid] = $element['#item'];
        }
      }
      // Hidden is a special case, the value needs to be reversed.
      if ($element['hidden']['#value'] != $element['hidden']['#default_value']) {
        // Convert to integer rather than boolean due to PDO cast to string.
        $element['#item']['hidden'] = $element['hidden']['#value'] ? 0 : 1;
        $updated_items[$mlid] = $element['#item'];
      }
    }
  }

  // Save all our changed items to the database.
  foreach ($updated_items as $item) {
    $item['customized'] = 1;
    menu_link_save($item);
  }
}

/**
 * Returns HTML for the menu overview form into a table.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_menu_overview_form($variables) {
  $form = $variables['form'];

  drupal_add_tabledrag('menu-overview', 'match', 'parent', 'menu-plid', 'menu-plid', 'menu-mlid', TRUE, MENU_MAX_DEPTH - 1);
  drupal_add_tabledrag('menu-overview', 'order', 'sibling', 'menu-weight');

  $header = array(
    t('Menu link'),
    array('data' => t('Enabled'), 'class' => array('checkbox')),
    t('Weight'),
    t('Operations'),
  );

  $rows = array();
  foreach (element_children($form) as $mlid) {
    if (isset($form[$mlid]['hidden'])) {
      $element = &$form[$mlid];

      // Add special classes to be used for tabledrag.js.
      $element['plid']['#attributes']['class'] = array('menu-plid');
      $element['mlid']['#attributes']['class'] = array('menu-mlid');
      $element['weight']['#attributes']['class'] = array('menu-weight');

      // Change the parent field to a hidden. This allows any value but hides the field.
      $element['plid']['#type'] = 'hidden';

      $row = array();
      $row[] = theme('indentation', array('size' => $element['#item']['depth'] - 1)) . drupal_render($element['title']);
      $row[] = array('data' => drupal_render($element['hidden']), 'class' => array('checkbox', 'menu-enabled'));
      $row[] = drupal_render($element['weight']) . drupal_render($element['plid']) . drupal_render($element['mlid']);
      $row[] = drupal_render($element['operations']);

      $row = array_merge(array('data' => $row), $element['#attributes']);
      $row['class'][] = 'draggable';
      $rows[] = $row;
    }
  }
  $output = '';
  if (empty($rows)) {
    $rows[] = array(array('data' => $form['#empty_text'], 'colspan' => '7'));
  }
  $output .= drupal_render($form['inline_actions']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'menu-overview')));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Returns whether a menu name already exists.
 *
 * @see menu_edit_menu()
 * @see form_validate_machine_name()
 */
function menu_edit_menu_name_exists($value) {
  $custom_exists = entity_load('menu', $value);
  // 'menu-' is added to the menu name to avoid name-space conflicts.
  $value = 'menu-' . $value;
  $link_exists = Drupal::entityQuery('menu_link')->condition('menu_name', $value)->range(0,1)->count()->execute();

  return $custom_exists || $link_exists;
}

/**
 * Submit function for adding or editing a custom menu.
 */
function menu_edit_menu_submit($form, &$form_state) {
  $menu = $form_state['values'];
  $path = 'admin/structure/menu/manage/';
  if ($form['#insert']) {
    // Add 'menu-' to the menu name to help avoid name-space conflicts.
    $menu['id'] = 'menu-' . $menu['id'];
    $system_link = entity_load_multiple_by_properties('menu_link', array('link_path' => 'admin/structure/menu', 'module' => 'system'));
    $system_link = reset($system_link);
    $menu_link = entity_create('menu_link', array(
      'link_title' => $menu['label'],
      'link_path' => $path . $menu['id'],
      'router_path' => $path . '%',
      'plid' => $system_link->id(),
    ));
    $menu_link->save();
    menu_save($menu);
  }
  else {
    menu_save($menu);
    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_path' => $path . $menu['id']));
    foreach ($menu_links as $menu_link) {
      $menu_link->link_title = $menu['label'];
      $menu_link->save();
    }
  }
  drupal_set_message(t('Your configuration has been saved.'));
  $form_state['redirect'] = $path . $menu['id'];
}

/**
 * Menu callback: Provides the menu link submission form.
 *
 * @param \Drupal\system\Plugin\Core\Entity\Menu $menu
 *   An entity representing a custom menu.
 *
 * @return
 *   Returns the menu link submission form.
 */
function menu_link_add(Menu $menu) {
  $menu_link = entity_create('menu_link', array(
    'mlid' => 0,
    'plid' => 0,
    'menu_name' => $menu->id(),
  ));
  drupal_set_title(t('Add menu link'));
  return entity_get_form($menu_link);
}
