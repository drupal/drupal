<?php

declare(strict_types=1);

namespace Drupal\render_placeholder_message_test;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for testing render placeholders and message ordering.
 */
class RenderPlaceholderMessageTestController implements TrustedCallbackInterface, ContainerInjectionInterface {

  /**
   * Constructs a new RenderPlaceholderMessageTestController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(protected RendererInterface $renderer) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
    );
  }

  /**
   * @return array
   *   A renderable array with the messages placeholder rendered first.
   */
  public function messagesPlaceholderFirst() {
    return $this->build([
      // cspell:disable
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P1" token="JBp04zOwNhYqMBgRkyBnPdma8m4l2elDnXMJ9tEsP6k"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P2" token="JnoubSJT1l92Dm4fJw4EPsSzRsmE88H6Q1zu9-OzDh4"></drupal-render-placeholder>',
      // cspell:enable
    ]);
  }

  /**
   * @return array
   *   A renderable array with the messages placeholder rendered in the middle.
   */
  public function messagesPlaceholderMiddle() {
    return $this->build([
      // cspell:disable
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P1" token="JBp04zOwNhYqMBgRkyBnPdma8m4l2elDnXMJ9tEsP6k"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P2" token="JnoubSJT1l92Dm4fJw4EPsSzRsmE88H6Q1zu9-OzDh4"></drupal-render-placeholder>',
      // cspell:enable
    ]);
  }

  /**
   * @return array
   *   A renderable array with the messages placeholder rendered last.
   */
  public function messagesPlaceholderLast() {
    return $this->build([
      // cspell:disable
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P1" token="JBp04zOwNhYqMBgRkyBnPdma8m4l2elDnXMJ9tEsP6k"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P2" token="JnoubSJT1l92Dm4fJw4EPsSzRsmE88H6Q1zu9-OzDh4"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
      // cspell:enable
    ]);
  }

  /**
   * @return array
   *   A renderable array containing only messages.
   */
  public function queuedMessages() {
    return ['#type' => 'status_messages'];
  }

  /**
   * @return array
   *   A renderable array containing only placeholders.
   */
  protected function build(array $placeholder_order) {
    $build = [];
    $build['messages'] = ['#type' => 'status_messages'];
    $build['p1'] = [
      '#lazy_builder' => [
        '\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage',
        ['P1'],
      ],
      '#create_placeholder' => TRUE,
    ];
    $build['p2'] = [
      '#lazy_builder' => [
        '\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage',
        ['P2'],
      ],
      '#create_placeholder' => TRUE,
    ];

    $renderer = $this->renderer;
    $renderer->executeInRenderContext(new RenderContext(), function () use (&$build, $renderer) {
      return $renderer->render($build, FALSE);
    });

    $reordered = [];
    foreach ($placeholder_order as $placeholder) {
      if (isset($build['#attached']['placeholders'][$placeholder])) {
        $reordered[$placeholder] = $build['#attached']['placeholders'][$placeholder];
      }
    }
    $build['#attached']['placeholders'] = $reordered;

    return $build;
  }

  /**
   * Render API callback: Sets and prints a message.
   *
   * This function is assigned as a #lazy_builder callback.
   *
   * @param string $message
   *   The message to send.
   *
   * @return array
   *   A renderable array containing the message.
   */
  public static function setAndLogMessage($message) {
    // Ensure that messages are rendered last even when earlier placeholders
    // suspend the Fiber, this will cause BigPipe::renderPlaceholders() to loop
    // around all of the fibers before resuming this one, then finally rendering
    // the messages when there are no other placeholders left.
    if (\Fiber::getCurrent() !== NULL) {
      \Fiber::suspend();
    }
    // Set message.
    \Drupal::messenger()->addStatus($message);

    // Print which message is expected.
    return ['#markup' => '<p class="logged-message">Message: ' . $message . '</p>'];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['setAndLogMessage'];
  }

}
