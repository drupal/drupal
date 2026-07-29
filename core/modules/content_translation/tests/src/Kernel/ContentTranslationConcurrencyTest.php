<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Routing\RouteMatch;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestMulBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Route;

/**
 * Tests concurrency in the content translation controller.
 */
#[CoversClass(ContentTranslationController::class)]
#[Group('content_translation')]
#[RunTestsInSeparateProcesses]
class ContentTranslationConcurrencyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'language', 'content_translation', 'user', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mul_with_bundle');
    EntityTestMulBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests that adding a translation that already exists redirects gracefully.
   */
  public function testConcurrentTranslationAddition(): void {
    $language_manager = $this->container->get('language_manager');
    $source_lang = $language_manager->getLanguage('en');
    $target_lang = $language_manager->getLanguage('fr');

    $entity = EntityTestMul::create([
      'name' => 'English Entity',
      'type' => 'test',
      'langcode' => 'en',
    ]);
    $entity->save();

    $entity->addTranslation('fr', ['name' => 'French Entity'])->save();

    $controller = ContentTranslationController::create($this->container);

    $route_match = new RouteMatch(
      'entity.entity_test_mul.content_translation_add',
      new Route('/entity-test-mul/{entity_test_mul}/translations/add/{source}/{target}'),
      ['entity_test_mul' => $entity],
      ['entity_test_mul' => $entity->id()]
    );

    $response = $controller->add($source_lang, $target_lang, $route_match, 'entity_test_mul');

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $expected = $entity->toUrl('edit-form')->toString();
    $this->assertStringContainsString($expected, $response->getTargetUrl());

    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $this->container->get('messenger');
    $errors = $messenger->messagesByType('error');

    $this->assertCount(1, $errors);

    $actual_message = (string) reset($errors);
    $this->assertEquals('A translation already exists for French.', $actual_message);
  }

}
