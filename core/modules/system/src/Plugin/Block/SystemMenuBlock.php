<?php

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\Form\SystemMenuOffCanvasForm;
use Drupal\system\Plugin\Derivative\SystemMenuBlock as SystemMenuBlockDeriver;

/**
 * Provides a generic Menu block.
 */
#[Block(
  id: "system_menu_block",
  admin_label: new TranslatableMarkup("Menu"),
  category: new TranslatableMarkup("Menus"),
  deriver: SystemMenuBlockDeriver::class,
  forms: [
    'settings_tray' => SystemMenuOffCanvasForm::class,
  ]
)]
class SystemMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $defaults = $this->defaultConfiguration();
    $form['menu_levels'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu levels'),
      // Open if not set to defaults.
      '#open' => $defaults['level'] !== $config['level'] || $defaults['depth'] !== $config['depth'],
      '#process' => [[self::class, 'processMenuLevelParents']],
    ];

    $options = range(0, $this->menuTree->maxDepth());
    unset($options[0]);

    $form['menu_levels']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => $config['level'],
      '#options' => $options,
      '#description' => $this->t('The menu is only visible if the menu link for the current page is at this level or below it. Use level 1 to always display this menu.'),
      '#required' => TRUE,
    ];

    $options[0] = $this->t('Unlimited');

    $form['menu_levels']['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => $config['depth'] ?? 0,
      '#options' => $options,
      '#description' => $this->t('This maximum number includes the initial level.'),
      '#required' => TRUE,
    ];

    $form['menu_levels']['expand_all_items'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand all menu links'),
      '#default_value' => !empty($config['expand_all_items']),
      '#description' => $this->t('Override the option found on each menu link used for expanding children and instead display the whole menu tree as expanded.'),
    ];

    // When only the first level of links are shown, or if all links are
    // expanded, the active trail logic can be skipped.
    $state_conditions = [
      // When the menu level starts at anything other than 1.
      [
        ':input[name="settings[level]"]' => ['!value' => '1'],
      ],
      'or',
      // When links aren't all expanded, and more than one level of links are
      // shown.
      [
        'input[name="settings[expand_all_items]"]' => ['checked' => FALSE],
        ':input[name="settings[depth]"]' => ['!value' => '1'],
      ],
    ];

    // The 'add_active_trail_class' checkbox value is the inverse of the
    // 'ignore_active_trail configuration value. This is because the positive
    // statement is easier to explain in the UI, but the negative statement is
    // easier to implement in the API.
    $form['menu_levels']['add_active_trail_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a CSS class to ancestors of the current page'),
      '#default_value' => empty($config['ignore_active_trail']),
      '#description' => $this->t('Adds a CSS class to parent menu links when the current page is in the menu. This feature has a performance impact and should only be enabled when the menu appearance should differ based on the current page.'),
      '#states' => [
        'required' => $state_conditions,
      ],
    ];

    return $form;
  }

  /**
   * Form API callback: Processes the menu_levels field element.
   *
   * Adjusts the #parents of menu_levels to save its children at the top level.
   */
  public static function processMenuLevelParents(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    if (!empty($values['add_active_trail_class'])) {
      return;
    }

    if ((int) $values['level'] !== 1 || (empty($values['expand_all_items']) && (int) $values['depth'] !== 1)) {
      $form_state->setError($form['menu_levels']['add_active_trail_class'], $this->t('"Add a CSS class to ancestors of the current page" is required if the menu if the initial is 1, or if menu items are not all expanded and the number of levels display is more than 1.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['depth'] = $form_state->getValue('depth') ?: NULL;
    $this->configuration['expand_all_items'] = $form_state->getValue('expand_all_items');

    // Reverse the form checkbox value to match the configuration name. While
    // this is counter-intuitive, it simplifies both the UI and the API logic
    // outside of this method.
    if ($form_state->getValue('add_active_trail_class')) {
      unset($this->configuration['ignore_active_trail']);
    }
    else {
      $this->configuration['ignore_active_trail'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getDerivativeId();
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    // If all items are expanded, or if only the first level of the menu is
    // shown, then the links will always be the same on each page.
    if ($this->configuration['expand_all_items'] || ($level == 1 && $depth == 1)) {
      $parameters = new MenuTreeParameters();
      if ($this->shouldSetActiveTrail()) {
        $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
        $parameters->setActiveTrail($active_trail);
      }
    }
    else {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    }

    // Adjust the menu tree parameters based on the block's configuration.
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    return $this->menuTree->build($tree);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'level' => 1,
      'depth' => NULL,
      'expand_all_items' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the menu block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the menu is changed, this
    // menu block must also be re-rendered for that user, because maybe a menu
    // link that is accessible for that user has been added.
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:system.menu.' . $this->getDerivativeId();
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $menu_name = $this->getDerivativeId();
    $contexts = parent::getCacheContexts();
    // The active trail context is added when the menu block is not configured
    // to ignore the active trail. Ignoring the active trail only applies when
    // the menu is also configured with all items expanded and start level 1, so
    // if any of those conditions are not true, the active trail context is
    // added.
    if ($this->shouldSetActiveTrail()) {
      $contexts = Cache::mergeContexts($contexts, ['route.menu_active_trails:' . $menu_name]);
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function createPlaceholder(): bool {
    return TRUE;
  }

  /**
   * Determine whether the menu block should set active trails on the links.
   *
   * The active trail must be set if it's required to build the correct
   * set of links, so setting 'ignore_active_trail' to TRUE only works with
   * certain configurations:
   * - The initial level is 1 and all items are expanded
   * - The initial level is 1 and only 1 level of links are displayed
   *
   * While the form UI and validation should prevent 'ignore_active_trail' from
   * being set to TRUE otherwise, the other settings are checked as well, in
   * case the configuration is somehow in an invalid state.
   *
   * @return bool
   *   TRUE if the menu block should set active trails on the links.
   */
  protected function shouldSetActiveTrail(): bool {
    return empty($this->configuration['ignore_active_trail']) || $this->configuration['level'] !== 1 || (empty($this->configuration['expand_all_items']) && $this->configuration['depth'] !== 1);
  }

}
