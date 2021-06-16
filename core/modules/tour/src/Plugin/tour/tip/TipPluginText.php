<?php

namespace Drupal\tour\Plugin\tour\tip;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\tour\TipPluginBase;
use Drupal\tour\TourTipPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays some text as a tip.
 *
 * @Tip(
 *   id = "text",
 *   title = @Translation("Text")
 * )
 */
class TipPluginText extends TipPluginBase implements ContainerFactoryPluginInterface, TourTipPluginInterface {

  /**
   * The body text which is used for render of this Text Tip.
   *
   * @var string
   */
  protected $body;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a \Drupal\tour\Plugin\tour\tip\TipPluginText object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('token'));
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->token->replace($this->get('body')),
      '#attributes' => [
        'class' => ['tour-tip-body'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    // Call parent to trigger error when calling this function.
    parent::getOutput();
    $output = '<p class="tour-tip-body">' . $this->token->replace($this->get('body')) . '</p>';
    return ['#markup' => $output];
  }

}
