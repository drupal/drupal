<?php

namespace Drupal\tour_test\Plugin\tour\tip;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\tour\TipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays some text as a tip.
 *
 * @Tip(
 *   id = "text_legacy",
 *   title = @Translation("Text Legacy")
 * )
 */
class TipPluginTextLegacy extends TipPluginBase implements ContainerFactoryPluginInterface {

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
   * The forced position of where the tip will be located.
   *
   * @var string
   */
  protected $location;

  /**
   * Unique aria-id.
   *
   * @var string
   */
  protected $ariaId;

  /**
   * Constructs a TipPluginTextLegacy object.
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
   * Returns an ID that is guaranteed uniqueness.
   *
   * @return string
   *   A unique id to be used to generate aria attributes.
   */
  public function getAriaId() {
    if (!$this->ariaId) {
      $this->ariaId = Html::getUniqueId($this->get('id'));
    }
    return $this->ariaId;
  }

  /**
   * Returns body of the text tip.
   *
   * @return array
   *   The tip body.
   */
  public function getBody(): array {
    return [$this->get('body')];
  }

}
