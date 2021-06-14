<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Menu\Form\MenuLinkDefaultForm;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Menu\Form\MenuLinkDefaultForm
 * @group Menu
 */
class MenuLinkDefaultFormTest extends UnitTestCase {

  /**
   * @covers ::extractFormValues
   */
  public function testExtractFormValues() {
    $menu_link_manager = $this->prophesize(MenuLinkManagerInterface::class);
    $menu_parent_form_selector = $this->prophesize(MenuParentFormSelectorInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $menu_link_form = new MenuLinkDefaultForm($menu_link_manager->reveal(), $menu_parent_form_selector->reveal(), $this->getStringTranslationStub(), $module_handler->reveal());

    $static_override = $this->prophesize(StaticMenuLinkOverridesInterface::class);
    $menu_link = new MenuLinkDefault([], 'my_plugin_id', [], $static_override->reveal());
    $menu_link_form->setMenuLinkInstance($menu_link);

    $form_state = new FormState();
    $form_state->setValue('id', 'my_plugin_id');
    $form_state->setValue('enabled', FALSE);
    $form_state->setValue('weight', 5);
    $form_state->setValue('expanded', TRUE);
    $form_state->setValue('menu_parent', 'foo:bar');

    $form = [];
    $result = $menu_link_form->extractFormValues($form, $form_state);

    $this->assertEquals([
      'id' => 'my_plugin_id',
      'enabled' => 0,
      'weight' => 5,
      'expanded' => 1,
      'parent' => 'bar',
      'menu_name' => 'foo',
    ], $result);
  }

}
