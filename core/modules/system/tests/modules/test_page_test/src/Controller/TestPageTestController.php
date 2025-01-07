<?php

declare(strict_types=1);

namespace Drupal\test_page_test\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;

/**
 * Controller routines for test_page_test routes.
 */
class TestPageTestController {

  use StringTranslationTrait;

  /**
   * Returns a test page and sets the title.
   */
  public function testPage() {
    $link_text = $this->t('Visually identical test links');
    return [
      '#title' => $this->t('Test page'),
      '#markup' => $this->t('Test page text.') . "<a href=\"/user/login\">$link_text</a><a href=\"/user/register\">$link_text</a>",
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
      '#title' => $this->t('Test page with var dump'),
      '#markup' => $this->t('Test page text.'),
    ];
  }

}
