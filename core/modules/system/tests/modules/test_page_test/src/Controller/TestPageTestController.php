<?php

namespace Drupal\test_page_test\Controller;

use Drupal\user\Entity\Role;

/**
 * Controller routines for test_page_test routes.
 */
class TestPageTestController {

  /**
   * Returns a test page and sets the title.
   */
  public function testPage() {
    $link_text = t('Visually identical test links');
    return [
      '#title' => t('Test page'),
      '#markup' => t('Test page text.') . "<a href=\"/user/login\">$link_text</a><a href=\"/user/register\">$link_text</a>",
      '#attached' => [
        'drupalSettings' => [
          'test-setting' => 'azAZ09();.,\\\/-_{}',
        ],
      ],
    ];
  }

  /**
   * Returns a test page and with the call to the dump() function.
   */
  public function testPageVarDump() {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    dump($role);
    return [
      '#title' => t('Test page with var dump'),
      '#markup' => t('Test page text.'),
    ];
  }

}
