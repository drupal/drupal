<?php

namespace Drupal\token_test\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a test controller for token replacement.
 */
class TestController extends ControllerBase {

  /**
   * The token replacement system.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new TestController instance.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement system.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('token'));
  }

  /**
   * Provides a token replacement with a node as well as the current user.
   *
   * This controller passes an explicit bubbleable metadata object to
   * $this->token->replace(), and applies the collected metadata to the render
   * array being built.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   The render array.
   */
  public function tokenReplace(NodeInterface $node) {
    $bubbleable_metadata = new BubbleableMetadata();
    $build['#markup'] = $this->token->replace('Tokens: [node:nid] [current-user:uid]', ['node' => $node], [], $bubbleable_metadata);
    $bubbleable_metadata->applyTo($build);

    return $build;
  }

  /**
   * Provides a token replacement with a node as well as the current user.
   *
   * This controller is for testing the token service's fallback behavior of
   * applying collected metadata to the currently active render context when an
   * explicit bubbleable metadata object isn't passed in.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   The render array.
   */
  public function tokenReplaceWithoutPassedBubbleableMetadata(NodeInterface $node) {
    $build['#markup'] = $this->token->replace('Tokens: [node:nid] [current-user:uid]', ['node' => $node], []);

    return $build;
  }

}
