<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\RssResponseRelativeUrlFilter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\RssResponseRelativeUrlFilter
 * @group event_subscriber
 */
class RssResponseRelativeUrlFilterTest extends UnitTestCase {

  public static function providerTestOnResponse() {
    $data = [];

    $valid_feed = <<<RSS
<?xml version="1.0" encoding="utf-8"?>
<rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0" xml:base="https://www.drupal.org">
<channel>
  <title>Drupal.org</title>
  <link>https://www.drupal.org</link>
  <description>Come for the software &amp; stay for the community
Drupal is an open source content management platform powering millions of websites and applications. It’s built, used, and supported by an active and diverse community of people around the world.</description>
  <language>en</language>
  <item>
     <title>Drupal 8 turns one!</title>
     <link>https://www.drupal.org/blog/drupal-8-turns-one</link>
     <description>&lt;a href=&quot;localhost/node/1&quot;&gt;Hello&amp;nbsp;&lt;/a&gt;
    </description>
  </item>
  </channel>
</rss>
RSS;

    $valid_expected_feed = <<<RSS
<?xml version="1.0" encoding="utf-8"?>
<rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0" xml:base="https://www.drupal.org">
<channel>
  <title>Drupal.org</title>
  <link>https://www.drupal.org</link>
  <description>Come for the software &amp; stay for the community
Drupal is an open source content management platform powering millions of websites and applications. It’s built, used, and supported by an active and diverse community of people around the world.</description>
  <language>en</language>
  <item>
     <title>Drupal 8 turns one!</title>
     <link>https://www.drupal.org/blog/drupal-8-turns-one</link>
     <description>&lt;a href="localhost/node/1"&gt;Hello&amp;nbsp;&lt;/a&gt;
    </description>
  </item>
  </channel>
</rss>

RSS;

    $data['valid-feed'] = [$valid_feed, $valid_expected_feed];

    $invalid_feed = <<<RSS
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xml:base="https://www.drupal.org"  xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
  <title>Drupal.org</title>
  <link>https://www.drupal.org</link>
  <description>Come for the software, stay for the community
Drupal is an open source content management platform powering millions of websites and applications. It’s built, used, and supported by an active and diverse community of people around the world.</description>
  <language>en</language>
  <item>
     <title>Drupal 8 turns one!</title>
     <link>https://www.drupal.org/blog/drupal-8-turns-one</link>
     <description>
     <![CDATA[
     &lt;a href="localhost/node/1"&gt;Hello&lt;/a&gt;
     <script>
<!--//--><![CDATA[// ><!--

<!--//--><![CDATA[// ><!--

<!--//--><![CDATA[// ><!--
(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/de_DE/sdk.js#xfbml=1&version=v2.3";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
//--><!]]]]]]><![CDATA[><![CDATA[>

//--><!]]]]><![CDATA[>

//--><!]]>
</script>
    ]]>
    </description>
  </item>
  </channel>
</rss>
RSS;

    $data['invalid-feed'] = [$invalid_feed, $invalid_feed];
    return $data;
  }

  /**
   * @dataProvider providerTestOnResponse
   *
   * @param string $content
   *   The content for the request.
   * @param string $expected_content
   *   The expected content from the response.
   */
  public function testOnResponse($content, $expected_content): void {
    $event = new ResponseEvent(
      $this->prophesize(HttpKernelInterface::class)->reveal(),
      Request::create('/'),
      HttpKernelInterface::MAIN_REQUEST,
      new Response($content, 200, [
        'Content-Type' => 'application/rss+xml',
      ])
    );

    $url_filter = new RssResponseRelativeUrlFilter();
    $url_filter->onResponse($event);

    $this->assertEquals($expected_content, $event->getResponse()->getContent());
  }

}
