<?php

namespace Drupal\render_placeholder_message_test;

use Drupal\Core\Render\RenderContext;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class RenderPlaceholderMessageTestController implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @return array
   */
  public function messagesPlaceholderFirst() {
    return $this->build([
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P1" token="JBp04zOwNhYqMBgRkyBnPdma8m4l2elDnXMJ9tEsP6k"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P2" token="JnoubSJT1l92Dm4fJw4EPsSzRsmE88H6Q1zu9-OzDh4"></drupal-render-placeholder>',
    ]);
  }

  /**
   * @return array
   */
  public function messagesPlaceholderMiddle() {
    return $this->build([
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P1" token="JBp04zOwNhYqMBgRkyBnPdma8m4l2elDnXMJ9tEsP6k"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P2" token="JnoubSJT1l92Dm4fJw4EPsSzRsmE88H6Q1zu9-OzDh4"></drupal-render-placeholder>',
    ]);
  }

  /**
   * @return array
   */
  public function messagesPlaceholderLast() {
    return $this->build([
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P1" token="JBp04zOwNhYqMBgRkyBnPdma8m4l2elDnXMJ9tEsP6k"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage" arguments="0=P2" token="JnoubSJT1l92Dm4fJw4EPsSzRsmE88H6Q1zu9-OzDh4"></drupal-render-placeholder>',
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
    ]);
  }

  /**
   * @return array
   */
  public function queuedMessages() {
    return ['#type' => 'status_messages'];
  }

  /**
   * @return array
   */
  protected function build(array $placeholder_order) {
    $build = [];
    $build['messages'] = ['#type' => 'status_messages'];
    $build['p1'] = [
      '#lazy_builder' => ['\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage', ['P1']],
      '#create_placeholder' => TRUE,
    ];
    $build['p2'] = [
      '#lazy_builder' => ['\Drupal\render_placeholder_message_test\RenderPlaceholderMessageTestController::setAndLogMessage', ['P2']],
      '#create_placeholder' => TRUE,
    ];

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $renderer->executeInRenderContext(new RenderContext(), function () use (&$build, $renderer) {
      return $renderer->render($build, FALSE);
    });

    $reordered = [];
    foreach ($placeholder_order as $placeholder) {
      $reordered[$placeholder] = $build['#attached']['placeholders'][$placeholder];
    }
    $build['#attached']['placeholders'] = $reordered;

    return $build;
  }

  /**
   * #lazy_builder callback; sets and prints a message.
   *
   * @param string $message
   *   The message to send.
   *
   * @return array
   *   A renderable array containing the message.
   */
  public static function setAndLogMessage($message) {
    // Set message.
    \Drupal::messenger()->addStatus($message);

    // Print which message is expected.
    return ['#markup' => '<p class="logged-message">Message: ' . $message . '</p>'];
  }

}
