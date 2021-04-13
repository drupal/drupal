<?php

namespace Drupal\Tests\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's olivero_preprocess_field__comment.
 *
 * @group olivero
 * @covers olivero_preprocess_field__comment
 */
final class OliveroPreprocessFieldCommentTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';

    $container = new ContainerBuilder();
    $userProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $container->set('current_user', $userProphet->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the olivero_preprocess_field__comment count the number of comments.
   */
  public function testPreprocessFieldCommentCount() {

    $variables = [
      'comments' => [],
    ];
    olivero_preprocess_field__comment($variables);
    $this->assertEquals(0, $variables['comment_count']);

    $variables = [
      'comments' => [
        1 => [],
        2 => [],
      ],
    ];

    olivero_preprocess_field__comment($variables);
    $this->assertEquals(2, $variables['comment_count']);

  }

}
