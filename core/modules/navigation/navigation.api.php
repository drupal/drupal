<?php

/**
 * @file
 * Hooks related to the Navigation module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide content for Navigation content_top section.
 *
 * @return array
 *   An associative array of renderable elements.
 *
 * @see hook_navigation_content_top_alter()
 */
function hook_navigation_content_top(): array {
  return [
    'navigation_foo' => [
      '#markup' => \Drupal::config('system.site')->get('name'),
      '#cache' => [
        'tags' => ['config:system.site'],
      ],
    ],
    'navigation_bar' => [
      '#markup' => 'bar',
    ],
    'navigation_baz' => [
      '#markup' => 'baz',
    ],
  ];
}

/**
 * Alter replacement values for placeholder tokens.
 *
 * @param array $content_top
 *   An associative array of content returned by hook_navigation_content_top().
 *
 * @see hook_navigation_content_top()
 */
function hook_navigation_content_top_alter(array &$content_top): void {
  // Remove a specific element.
  unset($content_top['navigation_foo']);
  // Modify an element.
  $content_top['navigation_bar']['#markup'] = 'new bar';
  // Change weight.
  $content_top['navigation_baz']['#weight'] = '-100';
}

/**
 * Provides default content for the Navigation bar.
 *
 * @return array
 *   An associative array of navigation block definitions.
 *   The following elements should be part of each definition array:
 *    - delta: The expected delta where the block should be placed in the
 *    Navigation bar. Defaults to 0.
 *    - configuration: The key-value array with the Navigation Block definition.
 *    It should include the following elements, besides the Navigation block
 *    specific settings:
 *      - id: The Navigation Block plugin ID.
 *      - label: The block label.
 *      -label_display: 0 or 1 depending on whether the label should be
 *      displayed or not.
 *      - provider: The module that provides the block. In general, the module
 *      that defines the Navigation block.
 */
function hook_navigation_defaults(): array {
  $blocks = [];

  $blocks[] = [
    'delta' => 1,
    'configuration' => [
      'id' => 'navigation_test',
      'label' => 'My test block',
      'label_display' => 1,
      'provider' => 'navigation_test_block',
      'test_block_setting_foo' => 'Foo',
      'test_block_setting_bar' => 1,
    ],
  ];

  return $blocks;
}

/**
 * Alter the content of a given Navigation menu link tree.
 *
 * @param array &$tree
 *   The Navigation link tree.
 *
 * @see \Drupal\navigation\Menu\NavigationMenuLinkTree::transform()
 */
function hook_navigation_menu_link_tree_alter(array &$tree): void {
  foreach ($tree as $key => $item) {
    // Skip elements where menu is not the 'admin' one.
    $menu_name = $item->link->getMenuName();
    if ($menu_name != 'admin') {
      continue;
    }

    // Remove unwanted Help menu link.
    $plugin_id = $item->link->getPluginId();
    if ($plugin_id == 'help.main') {
      unset($tree[$key]);
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
