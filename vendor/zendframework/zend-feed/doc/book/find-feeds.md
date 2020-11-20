# Feed Discovery from Web Pages

Web pages often contain `<link>` tags that refer to feeds with content relevant
to the particular page. `Zend\Feed\Reader\Reader` enables you to retrieve all
feeds referenced by a web page with one method call:

```php
$feedLinks = Zend\Feed\Reader\Reader::findFeedLinks('http://www.example.com/news.html');
```

> ## Finding feed links requires an HTTP client
>
> To find feed links, you will need to have an [HTTP client](zend.feed.http-clients)
> available. 
>
> If you are not using zend-http, you will need to inject `Reader` with the HTTP
> client. See the [section on providing a client to Reader](http-clients.md#providing-a-client-to-reader).

Here the `findFeedLinks()` method returns a `Zend\Feed\Reader\FeedSet` object,
which is in turn a collection of other `Zend\Feed\Reader\FeedSet` objects, each
referenced by `<link>` tags on the `news.html` web page.
`Zend\Feed\Reader\Reader` will throw a
`Zend\Feed\Reader\Exception\RuntimeException` upon failure, such as an HTTP
404 response code or a malformed feed.

You can examine all feed links located by iterating across the collection:

```php
$rssFeed = null;
$feedLinks = Zend\Feed\Reader\Reader::findFeedLinks('http://www.example.com/news.html');
foreach ($feedLinks as $link) {
    if (stripos($link['type'], 'application/rss+xml') !== false) {
        $rssFeed = $link['href'];
        break;
}
```

Each `Zend\Feed\Reader\FeedSet` object will expose the `rel`, `href`, `type`,
and `title` properties of detected links for all RSS, Atom, or RDF feeds. You
can always select the first encountered link of each type by using a shortcut:
the first encountered link of a given type is assigned to a property named after
the feed type.

```php
$rssFeed = null;
$feedLinks = Zend\Feed\Reader\Reader::findFeedLinks('http://www.example.com/news.html');
$firstAtomFeed = $feedLinks->atom;
```
