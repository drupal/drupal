<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\ExceptionControllerTest
 */

namespace Drupal\Tests\Core\Controller {

use Drupal\Core\Controller\ExceptionController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Exception\FlattenException;

/**
 * @coversDefaultClass \Drupal\Core\Controller\ExceptionController
 * @group Controller
 */
class ExceptionControllerTest extends UnitTestCase {

  /**
   * Ensure the execute() method returns a valid response on 405 exceptions.
   */
  public function test405HTML() {
    $exception = new \Exception('Test exception');
    $flat_exception = FlattenException::create($exception, 405);
    $html_page_renderer = $this->getMock('Drupal\Core\Page\HtmlPageRendererInterface');
    $html_fragment_renderer = $this->getMock('Drupal\Core\Page\HtmlFragmentRendererInterface');
    $title_resolver = $this->getMock('Drupal\Core\Controller\TitleResolverInterface');
    $translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $content_negotiation = $this->getMock('Drupal\Core\ContentNegotiation');
    $content_negotiation->expects($this->any())
      ->method('getContentType')
      ->will($this->returnValue('html'));

    $exception_controller = new ExceptionController($content_negotiation, $title_resolver, $html_page_renderer, $html_fragment_renderer, $translation, $url_generator);
    $response = $exception_controller->execute($flat_exception, new Request());
    $this->assertEquals($response->getStatusCode(), 405, 'HTTP status of response is correct.');
    $this->assertEquals($response->getContent(), 'Method Not Allowed', 'HTTP response body is correct.');
  }

}

}

namespace {
  use Drupal\Core\Language\Language;

  if (!function_exists('language_default')) {
    function language_default() {
      $language = new Language(array('langcode' => 'en'));
      return $language;
    }
  }
}
