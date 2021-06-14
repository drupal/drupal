<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityRepository
 * @group Entity
 */
class EntityRepositoryTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $languageManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $contextRepository;

  /**
   * The entity repository under test.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);

    $this->entityRepository = new EntityRepository($this->entityTypeManager->reveal(), $this->languageManager->reveal(), $this->contextRepository->reveal());
  }

  /**
   * Tests the getTranslationFromContext() method.
   *
   * @covers ::getTranslationFromContext
   */
  public function testGetTranslationFromContext() {
    $language = new Language(['id' => 'en']);
    $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->willReturn($language)
      ->shouldBeCalledTimes(1);
    $this->languageManager->getFallbackCandidates(Argument::type('array'))
      ->will(function ($args) {
        $context = $args[0];
        $candidates = [];
        if (!empty($context['langcode'])) {
          $candidates[$context['langcode']] = $context['langcode'];
        }
        return $candidates;
      })
      ->shouldBeCalledTimes(1);

    $translated_entity = $this->prophesize(ContentEntityInterface::class);

    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getUntranslated()->willReturn($entity);
    $entity->language()->willReturn($language);
    $entity->hasTranslation(LanguageInterface::LANGCODE_DEFAULT)->willReturn(FALSE);
    $entity->hasTranslation('custom_langcode')->willReturn(TRUE);
    $entity->getTranslation('custom_langcode')->willReturn($translated_entity->reveal());
    $entity->getTranslationLanguages()->willReturn([new Language(['id' => 'en']), new Language(['id' => 'custom_langcode'])]);
    $entity->addCacheContexts(['languages:language_content'])->shouldBeCalled();

    $this->assertSame($entity->reveal(), $this->entityRepository->getTranslationFromContext($entity->reveal()));
    $this->assertSame($translated_entity->reveal(), $this->entityRepository->getTranslationFromContext($entity->reveal(), 'custom_langcode'));
  }

}
