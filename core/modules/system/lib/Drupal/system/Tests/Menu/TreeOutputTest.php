<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\TreeOutputTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

/**
 * Menu tree output related tests.
 */
class TreeOutputTest extends WebTestBase {
  /**
   * Dummy link structure acceptable for menu_tree_output().
   */
  var $tree_data = array(
    '1'=> array(
      'link' => array( 'menu_name' => 'main-menu', 'mlid' => 1, 'hidden'=>0, 'has_children' => 1, 'title' => 'Item 1', 'in_active_trail' => 1, 'access'=>1, 'href' => 'a', 'localized_options' => array('attributes' => array('title' =>'')) ),
      'below' => array(
        '2' => array('link' => array( 'menu_name' => 'main-menu', 'mlid' => 2, 'hidden'=>0, 'has_children' => 1, 'title' => 'Item 2', 'in_active_trail' => 1, 'access'=>1, 'href' => 'a/b', 'localized_options' => array('attributes' => array('title' =>'')) ),
          'below' => array(
            '3' => array('link' => array( 'menu_name' => 'main-menu', 'mlid' => 3, 'hidden'=>0, 'has_children' => 0, 'title' => 'Item 3', 'in_active_trail' => 0, 'access'=>1, 'href' => 'a/b/c', 'localized_options' => array('attributes' => array('title' =>'')) ),
              'below' => array() ),
            '4' => array('link' => array( 'menu_name' => 'main-menu', 'mlid' => 4, 'hidden'=>0, 'has_children' => 0, 'title' => 'Item 4', 'in_active_trail' => 0, 'access'=>1, 'href' => 'a/b/d', 'localized_options' => array('attributes' => array('title' =>'')) ),
              'below' => array() )
            )
          )
        )
      ),
    '5' => array('link' => array( 'menu_name' => 'main-menu', 'mlid' => 5, 'hidden'=>1, 'has_children' => 0, 'title' => 'Item 5', 'in_active_trail' => 0, 'access'=>1, 'href' => 'e', 'localized_options' => array('attributes' => array('title' =>'')) ), 'below' => array( ) ),
    '6' => array('link' => array( 'menu_name' => 'main-menu', 'mlid' => 6, 'hidden'=>0, 'has_children' => 0, 'title' => 'Item 6', 'in_active_trail' => 0, 'access'=>0, 'href' => 'f', 'localized_options' => array('attributes' => array('title' =>'')) ), 'below' => array( ) ),
    '7' => array('link' => array( 'menu_name' => 'main-menu', 'mlid' => 7, 'hidden'=>0, 'has_children' => 0, 'title' => 'Item 7', 'in_active_trail' => 0, 'access'=>1, 'href' => 'g', 'localized_options' => array('attributes' => array('title' =>'')) ), 'below' => array( ) )
  );

  public static function getInfo() {
    return array(
      'name' => 'Menu tree output',
      'description' => 'Tests menu tree output functions.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Validate the generation of a proper menu tree output.
   */
  function testMenuTreeData() {
    $output = menu_tree_output($this->tree_data);

    // Validate that the - in main-menu is changed into an underscore
    $this->assertEqual( $output['1']['#theme'], 'menu_link__main_menu', t('Hyphen is changed to a dash on menu_link'));
    $this->assertEqual( $output['#theme_wrappers'][0], 'menu_tree__main_menu', t('Hyphen is changed to a dash on menu_tree wrapper'));
    // Looking for child items in the data
    $this->assertEqual( $output['1']['#below']['2']['#href'], 'a/b', t('Checking the href on a child item'));
    $this->assertTrue( in_array('active-trail',$output['1']['#below']['2']['#attributes']['class']) , t('Checking the active trail class'));
    // Validate that the hidden and no access items are missing
    $this->assertFalse( isset($output['5']), t('Hidden item should be missing'));
    $this->assertFalse( isset($output['6']), t('False access should be missing'));
    // Item 7 is after a couple hidden items. Just to make sure that 5 and 6 are skipped and 7 still included
    $this->assertTrue( isset($output['7']), t('Item after hidden items is present'));
  }
}
