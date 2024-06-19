<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\ParamConverter;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore deutscher titel

/**
 * Tests upcasting of URL arguments to entities.
 *
 * @group ParamConverter
 */
class UpcastingTest extends BrowserTestBase {

  protected static $modules = ['paramconverter_test', 'node', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Confirms that all parameters are converted as expected.
   *
   * All of these requests end up being processed by a controller with the
   * signature: f($user, $node, $foo) returning either values or labels
   * like "user: Dries, node: First post, foo: bar"
   *
   * The test shuffles the parameters around and checks if the right thing is
   * happening.
   */
  public function testUpcasting(): void {
    $node = $this->drupalCreateNode(['title' => $this->randomMachineName(8)]);
    $user = $this->drupalCreateUser(['access content']);
    $foo = 'bar';

    // paramconverter_test/test_user_node_foo/{user}/{node}/{foo}
    $this->drupalGet("paramconverter_test/test_user_node_foo/" . $user->id() . '/' . $node->id() . "/$foo");
    // Verify user and node upcast by entity name.
    $this->assertSession()->pageTextContains("user: {$user->label()}, node: {$node->label()}, foo: $foo");

    // paramconverter_test/test_node_user_user/{node}/{foo}/{user}
    // options.parameters.foo.type = entity:user
    $this->drupalGet("paramconverter_test/test_node_user_user/" . $node->id() . "/" . $user->id() . "/" . $user->id());
    // Verify foo converted to user as well.
    $this->assertSession()->pageTextContains("user: {$user->label()}, node: {$node->label()}, foo: {$user->label()}");

    // paramconverter_test/test_node_node_foo/{user}/{node}/{foo}
    // options.parameters.user.type = entity:node
    $this->drupalGet("paramconverter_test/test_node_node_foo/" . $node->id() . "/" . $node->id() . "/$foo");
    // Verify that user is upcast to node (rather than to user).
    $this->assertSession()->pageTextContains("user: {$node->label()}, node: {$node->label()}, foo: $foo");
  }

  /**
   * Confirms we can upcast to controller arguments of the same type.
   */
  public function testSameTypes(): void {
    $node = $this->drupalCreateNode(['title' => $this->randomMachineName(8)]);
    $parent = $this->drupalCreateNode(['title' => $this->randomMachineName(8)]);
    // paramconverter_test/node/{node}/set/parent/{parent}
    // options.parameters.parent.type = entity:node
    $this->drupalGet("paramconverter_test/node/" . $node->id() . "/set/parent/" . $parent->id());
    $this->assertSession()->pageTextContains("Setting '" . $parent->getTitle() . "' as parent of '" . $node->getTitle() . "'.");
  }

  /**
   * Confirms entity is shown in user's language by default.
   */
  public function testEntityLanguage(): void {
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    \Drupal::configFactory()->getEditable('language.negotiation')
      ->set('url.prefixes', ['de' => 'de'])
      ->save();

    // The container must be recreated after adding a new language.
    $this->rebuildContainer();

    $node = $this->drupalCreateNode(['title' => 'English label']);
    $translation = $node->addTranslation('de');
    $translation->setTitle('Deutscher Titel')->save();

    $this->drupalGet("/paramconverter_test/node/" . $node->id() . "/test_language");
    $this->assertSession()->pageTextContains("English label");
    $this->drupalGet("paramconverter_test/node/" . $node->id() . "/test_language", ['language' => $language]);
    $this->assertSession()->pageTextContains("Deutscher Titel");
  }

}
