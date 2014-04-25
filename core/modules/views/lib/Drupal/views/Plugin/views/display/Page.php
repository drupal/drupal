<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\Page.
 */

namespace Drupal\views\Plugin\views\display;

use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The plugin that handles a full page.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "page",
 *   title = @Translation("Page"),
 *   help = @Translation("Display the view as a page, with a URL and menu links."),
 *   uses_hook_menu = TRUE,
 *   uses_route = TRUE,
 *   contextual_links_locations = {"page"},
 *   theme = "views_view",
 *   admin = @Translation("Page")
 * )
 */
class Page extends PathPluginBase {

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   */
  protected $usesAttachments = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['menu'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        // Do not translate menu and title as menu system will.
        'title' => array('default' => '', 'translatable' => FALSE),
        'description' => array('default' => '', 'translatable' => FALSE),
        'weight' => array('default' => 0),
        'name' => array('default' => 'navigation'),
        'context' => array('default' => ''),
      ),
    );
    $options['tab_options'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        // Do not translate menu and title as menu system will.
        'title' => array('default' => '', 'translatable' => FALSE),
        'description' => array('default' => '', 'translatable' => FALSE),
        'weight' => array('default' => 0),
        'name' => array('default' => 'navigation'),
      ),
    );

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoute($view_id, $display_id) {
    $route = parent::getRoute($view_id, $display_id);

    // Move _controller to _content for page displays, which will return a
    // normal Drupal HTML page.
    $defaults = $route->getDefaults();
    $defaults['_content'] = $defaults['_controller'];
    unset($defaults['_controller']);
    $route->setDefaults($defaults);

    return $route;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::execute().
   */
  public function execute() {
    parent::execute();

    // Let the world know that this is the page view we're using.
    views_set_page_view($this->view);

    // And now render the view.
    $render = $this->view->render();

    // First execute the view so it's possible to get tokens for the title.
    // And the title, which is much easier.
    // @todo Figure out how to support custom response objects. Maybe for pages
    //   it should be dropped.
    if (is_array($render)) {
      $render += array(
        '#title' => Xss::filterAdmin($this->view->getTitle()),
      );
    }
    return $render;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $menu = $this->getOption('menu');
    if (!is_array($menu)) {
      $menu = array('type' => 'none');
    }
    switch ($menu['type']) {
      case 'none':
      default:
        $menu_str = t('No menu');
        break;
      case 'normal':
        $menu_str = t('Normal: @title', array('@title' => $menu['title']));
        break;
      case 'tab':
      case 'default tab':
        $menu_str = t('Tab: @title', array('@title' => $menu['title']));
        break;
    }

    $options['menu'] = array(
      'category' => 'page',
      'title' => t('Menu'),
      'value' => views_ui_truncate($menu_str, 24),
    );

    // This adds a 'Settings' link to the style_options setting if the style
    // has options.
    if ($menu['type'] == 'default tab') {
      $options['menu']['setting'] = t('Parent menu item');
      $options['menu']['links']['tab_options'] = t('Change settings for the parent menu');
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'menu':
        $form['#title'] .= t('Menu item entry');
        $form['menu'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $menu = $this->getOption('menu');
        if (empty($menu)) {
          $menu = array('type' => 'none', 'title' => '', 'weight' => 0);
        }
        $form['menu']['type'] = array(
          '#prefix' => '<div class="views-left-30">',
          '#suffix' => '</div>',
          '#title' => t('Type'),
          '#type' => 'radios',
          '#options' => array(
            'none' => t('No menu entry'),
            'normal' => t('Normal menu entry'),
            'tab' => t('Menu tab'),
            'default tab' => t('Default menu tab')
          ),
          '#default_value' => $menu['type'],
        );

        $form['menu']['title'] = array(
          '#prefix' => '<div class="views-left-50">',
          '#title' => t('Menu link title'),
          '#type' => 'textfield',
          '#default_value' => $menu['title'],
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="menu[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'tab'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'default tab'),
              ),
            ),
          ),
        );
        $form['menu']['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textfield',
          '#default_value' => $menu['description'],
          '#description' => t("Shown when hovering over the menu link."),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="menu[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'tab'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'default tab'),
              ),
            ),
          ),
        );

        // Only display the menu selector if Menu UI module is enabled.
        if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
          $form['menu']['name'] = array(
            '#title' => t('Menu'),
            '#type' => 'select',
            '#options' => menu_ui_get_menus(),
            '#default_value' => $menu['name'],
            '#states' => array(
              'visible' => array(
                array(
                  ':input[name="menu[type]"]' => array('value' => 'normal'),
                ),
                array(
                  ':input[name="menu[type]"]' => array('value' => 'tab'),
                ),
              ),
            ),
          );
        }
        else {
          $form['menu']['name'] = array(
            '#type' => 'value',
            '#value' => $menu['name'],
          );
          $form['menu']['markup'] = array(
            '#markup' => t('Menu selection requires the activation of Menu UI module.'),
          );
        }
        $form['menu']['weight'] = array(
          '#title' => t('Weight'),
          '#type' => 'textfield',
          '#default_value' => isset($menu['weight']) ? $menu['weight'] : 0,
          '#description' => t('In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="menu[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'tab'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'default tab'),
              ),
            ),
          ),
        );
        $form['menu']['context'] = array(
          '#title' => t('Context'),
          '#suffix' => '</div>',
          '#type' => 'checkbox',
          '#default_value' => !empty($menu['context']),
          '#description' => t('Displays the link in contextual links'),
          '#states' => array(
            'visible' => array(
              ':input[name="menu[type]"]' => array('value' => 'tab'),
            ),
          ),
        );
        break;
      case 'tab_options':
        $form['#title'] .= t('Default tab options');
        $tab_options = $this->getOption('tab_options');
        if (empty($tab_options)) {
          $tab_options = array('type' => 'none', 'title' => '', 'weight' => 0);
        }

        $form['tab_markup'] = array(
          '#markup' => '<div class="form-item description">' . t('When providing a menu item as a tab, Drupal needs to know what the parent menu item of that tab will be. Sometimes the parent will already exist, but other times you will need to have one created. The path of a parent item will always be the same path with the last part left off. i.e, if the path to this view is <em>foo/bar/baz</em>, the parent path would be <em>foo/bar</em>.') . '</div>',
        );

        $form['tab_options'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $form['tab_options']['type'] = array(
          '#prefix' => '<div class="views-left-25">',
          '#suffix' => '</div>',
          '#title' => t('Parent menu item'),
          '#type' => 'radios',
          '#options' => array('none' => t('Already exists'), 'normal' => t('Normal menu item'), 'tab' => t('Menu tab')),
          '#default_value' => $tab_options['type'],
        );
        $form['tab_options']['title'] = array(
          '#prefix' => '<div class="views-left-75">',
          '#title' => t('Title'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['title'],
          '#description' => t('If creating a parent menu item, enter the title of the item.'),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'tab'),
              ),
            ),
          ),
        );
        $form['tab_options']['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['description'],
          '#description' => t('If creating a parent menu item, enter the description of the item.'),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'tab'),
              ),
            ),
          ),
        );
        // Only display the menu selector if Menu UI module is enabled.
        if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
          $form['tab_options']['name'] = array(
            '#title' => t('Menu'),
            '#type' => 'select',
            '#options' => menu_ui_get_menus(),
            '#default_value' => $tab_options['name'],
            '#description' => t('Insert item into an available menu.'),
            '#states' => array(
              'visible' => array(
                ':input[name="tab_options[type]"]' => array('value' => 'normal'),
              ),
            ),
          );
        }
        else {
          $form['tab_options']['name'] = array(
            '#type' => 'value',
            '#value' => $tab_options['name'],
          );
          $form['tab_options']['markup'] = array(
            '#markup' => t('Menu selection requires the activation of Menu UI module.'),
          );
        }
        $form['tab_options']['weight'] = array(
          '#suffix' => '</div>',
          '#title' => t('Tab weight'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['weight'],
          '#size' => 5,
          '#description' => t('If the parent menu item is a tab, enter the weight of the tab. Heavier tabs will sink and the lighter tabs will be positioned nearer to the first menu item.'),
          '#states' => array(
            'visible' => array(
              ':input[name="tab_options[type]"]' => array('value' => 'tab'),
            ),
          ),
        );
        break;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::validateOptionsForm().
   */
  public function validateOptionsForm(&$form, &$form_state) {
    parent::validateOptionsForm($form, $form_state);

    if ($form_state['section'] == 'menu') {
      $path = $this->getOption('path');
      if ($form_state['values']['menu']['type'] == 'normal' && strpos($path, '%') !== FALSE) {
        form_error($form['menu']['type'], $form_state, t('Views cannot create normal menu items for paths with a % in them.'));
      }

      if ($form_state['values']['menu']['type'] == 'default tab' || $form_state['values']['menu']['type'] == 'tab') {
        $bits = explode('/', $path);
        $last = array_pop($bits);
        if ($last == '%') {
          form_error($form['menu']['type'], $form_state, t('A display whose path ends with a % cannot be a tab.'));
        }
      }

      if ($form_state['values']['menu']['type'] != 'none' && empty($form_state['values']['menu']['title'])) {
        form_error($form['menu']['title'], $form_state, t('Title is required for this menu type.'));
      }
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, &$form_state) {
    parent::submitOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'menu':
        $this->setOption('menu', $form_state['values']['menu']);
        // send ajax form to options page if we use it.
        if ($form_state['values']['menu']['type'] == 'default tab') {
          $form_state['view']->addFormToStack('display', $this->display['id'], 'tab_options');
        }
        break;
      case 'tab_options':
        $this->setOption('tab_options', $form_state['values']['tab_options']);
        break;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::validate().
   */
  public function validate() {
    $errors = parent::validate();

    $menu = $this->getOption('menu');
    if (!empty($menu['type']) && $menu['type'] != 'none' && empty($menu['title'])) {
      $errors[] = t('Display @display is set to use a menu but the menu link text is not set.', array('@display' => $this->display['display_title']));
    }

    if ($menu['type'] == 'default tab') {
      $tab_options = $this->getOption('tab_options');
      if (!empty($tab_options['type']) && $tab_options['type'] != 'none' && empty($tab_options['title'])) {
        $errors[] = t('Display @display is set to use a parent menu but the parent menu link text is not set.', array('@display' => $this->display['display_title']));
      }
    }

    return $errors;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getArgumentText().
   */
  public function getArgumentText() {
    return array(
      'filter value not present' => t('When the filter value is <em>NOT</em> in the URL'),
      'filter value present' => t('When the filter value <em>IS</em> in the URL or a default is provided'),
      'description' => t('The contextual filter values is provided by the URL.'),
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getPagerText().
   */
  public function getPagerText() {
    return array(
      'items per page title' => t('Items per page'),
      'items per page description' => t('The number of items to display per page. Enter 0 for no limit.')
    );
  }

}
