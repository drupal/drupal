<?php

namespace Drupal\test_page_test\Controller;

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

}
