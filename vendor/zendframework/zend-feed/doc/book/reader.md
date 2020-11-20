# Zend\\Feed\\Reader

`Zend\Feed\Reader` is a component used to consume RSS and Atom feeds of
any version, including RDF/RSS 1.0, RSS 2.0, Atom 0.3, and Atom 1.0. The API for
retrieving feed data is deliberately simple since `Zend\Feed\Reader` is capable
of searching any feed of any type for the information requested through the API.
If the typical elements containing this information are not present, it will
adapt and fall back on a variety of alternative elements instead. This ability
to choose from alternatives removes the need for users to create their own
abstraction layer on top of the component to make it useful or have any in-depth
knowledge of the underlying standards, current alternatives, and namespaced
extensions.

Internally, the `Zend\Feed\Reader\Reader` class works almost entirely on the
basis of making XPath queries against the feed XML's Document Object Model. This
singular approach to parsing is consistent, and the component offers a plugin
system to add to the Feed and Entry APIs by writing extensions on a similar
basis.

Performance is assisted in three ways. First of all, `Zend\Feed\Reader\Reader`
supports caching using [zend-cache](https://github.com/zendframework/zend-cache)
to maintain a copy of the original feed XML. This allows you to skip network
requests for a feed URI if the cache is valid. Second, the Feed and Entry APIs
are backed by an internal cache (non-persistent) so repeat API calls for the
same feed will avoid additional DOM or XPath use. Thirdly, importing feeds from
a URI can take advantage of HTTP Conditional `GET` requests which allow servers
to issue an empty 304 response when the requested feed has not changed since the
last time you requested it. In the final case, an zend-cache storage instance
will hold the last received feed along with the ETag and Last-Modified header
values sent in the HTTP response.

`Zend\Feed\Reader\Reader` is not capable of constructing feeds, and delegates
this responsibility to `Zend\Feed\Writer\Writer`.

## Importing Feeds

Feeds can be imported from a string, file or a URI. Importing from a URI can
additionally utilise an HTTP Conditional `GET` request. If importing fails, an
exception will be raised. The end result will be an object of type
`Zend\Feed\Reader\Feed\AbstractFeed`, the core implementations of which are
`Zend\Feed\Reader\Feed\Rss` and `Zend\Feed\Reader\Feed\Atom`. Both objects
support multiple (all existing) versions of these broad feed types.

In the following example, we import an RDF/RSS 1.0 feed and extract some basic
information that can be saved to a database or elsewhere.

```php
$feed = Zend\Feed\Reader\Reader::import('http://www.planet-php.net/rdf/');
$data = [
    'title'        => $feed->getTitle(),
    'link'         => $feed->getLink(),
    'dateModified' => $feed->getDateModified(),
    'description'  => $feed->getDescription(),
    'language'     => $feed->getLanguage(),
    'entries'      => [],
];

foreach ($feed as $entry) {
    $edata = [
        'title'        => $entry->getTitle(),
        'description'  => $entry->getDescription(),
        'dateModified' => $entry->getDateModified(),
        'authors'      => $entry->getAuthors(),
        'link'         => $entry->getLink(),
        'content'      => $entry->getContent(),
    ];
    $data['entries'][] = $edata;
}
```

> ## Importing requires an HTTP client
>
> To import a feed, you will need to have an [HTTP client](zend.feed.http-clients)
> available. 
>
> If you are not using zend-http, you will need to inject `Reader` with the HTTP
> client. See the [section on providing a client to Reader](http-clients.md#providing-a-client-to-reader).

The example above demonstrates `Zend\Feed\Reader\Reader`'s API, and it also
demonstrates some of its internal operation. In reality, the RDF feed selected
does not have any native date or author elements; however it does utilise the
Dublin Core 1.1 module which offers namespaced creator and date elements.
`Zend\Feed\Reader\Reader` falls back on these and similar options if no relevant
native elements exist. If it absolutely cannot find an alternative it will
return `NULL`, indicating the information could not be found in the feed. You
should note that classes implementing `Zend\Feed\Reader\Feed\AbstractFeed` also
implement the SPL `Iterator` and `Countable` interfaces.

Feeds can also be imported from strings or files.

```php
// from a URI
$feed = Zend\Feed\Reader\Reader::import('http://www.planet-php.net/rdf/');

// from a String
$feed = Zend\Feed\Reader\Reader::importString($feedXmlString);

// from a file
$feed = Zend\Feed\Reader\Reader::importFile('./feed.xml');
```

## Retrieving Underlying Feed and Entry Sources

`Zend\Feed\Reader\Reader` does its best not to stick you in a narrow confine. If
you need to work on a feed outside of `Zend\Feed\Reader\Reader`, you can extract
the base DOMDocument or DOMElement objects from any class, or even an XML
string containing these. Also provided are methods to extract the current
DOMXPath object (with all core and extension namespaces registered) and the
correct prefix used in all XPath queries for the current feed or entry. The
basic methods to use (on any object) are `saveXml()`, `getDomDocument()`,
`getElement()`, `getXpath()` and `getXpathPrefix()`. These will let you break
free of `Zend\Feed\Reader` and do whatever else you want.

- `saveXml()` returns an XML string containing only the element representing the
  current object.
- `getDomDocument()` returns the DOMDocument object representing the entire feed
  (even if called from an entry object).
- `getElement()` returns the DOMElement of the current object (i.e. the feed or
  current entry).
- `getXpath()` returns the DOMXPath object for the current feed (even if called
  from an entry object) with the namespaces of the current feed type and all
  loaded extensions pre-registered.
- `getXpathPrefix()` returns the query prefix for the current object (i.e. the
  feed or current entry) which includes the correct XPath query path for that
  specific feed or entry.

Let's look at an example where a feed might include an RSS extension not
supported by `Zend\Feed\Reader\Reader` out of the box. Notably, you could write
and register an extension (covered later) to do this, but that's not always
warranted for a quick check. You must register any new namespaces on the
DOMXPath object before use unless they are registered by `Zend\Feed\Reader` or
an extension beforehand.

```php
$feed        = Zend\Feed\Reader\Reader::import('http://www.planet-php.net/rdf/');
$xpathPrefix = $feed->getXpathPrefix();
$xpath       = $feed->getXpath();
$xpath->registerNamespace('admin', 'http://webns.net/mvcb/');
$reportErrorsTo = $xpath->evaluate(
    'string(' . $xpathPrefix . '/admin:errorReportsTo)'
);
```

> ### Do not register duplicate namespaces
>
> If you register an already registered namespace with a different prefix name
> to that used internally by `Zend\Feed\Reader\Reader`, it will break the
> internal operation of this component.

## Cache Support and Intelligent Requests

### Adding Cache Support to Zend\\Feed\\Reader\\Reader

`Zend\Feed\Reader\Reader` supports using a
[zend-cache](https://github.com/zendframework/zend-cache) storage instance to
cache feeds (as XML) to avoid unnecessary network requests. To add a cache,
create and configure your cache instance, and then tell
`Zend\Feed\Reader\Reader` to use it. The cache key used is
"`Zend\Feed\Reader\\`" followed by the MD5 hash of the feed's URI.

```php
$cache = Zend\Cache\StorageFactory::adapterFactory('Memory');
Zend\Feed\Reader\Reader::setCache($cache);
```

### HTTP Conditional GET Support

The big question often asked when importing a feed frequently is if it has even
changed. With a cache enabled, you can add HTTP Conditional `GET` support to
your arsenal to answer that question.

Using this method, you can request feeds from URIs and include their last known
ETag and Last-Modified response header values with the request (using the
If-None-Match and If-Modified-Since headers). If the feed on the server remains
unchanged, you should receive a 304 response which tells
`Zend\Feed\Reader\Reader` to use the cached version. If a full feed is sent in a
response with a status code of 200, this means the feed has changed and
`Zend\Feed\Reader\Reader` will parse the new version and save it to the cache.
It will also cache the new ETag and Last-Modified header values for future use.

> #### Conditional GET requires a HeaderAwareClientInterface
>
> Conditional GET support only works for `Zend\Feed\Reader\Http\HeaderAwareClientInterface`
> client implementations, as it requires the ability to send HTTP headers.

These "conditional" requests are not guaranteed to be supported by the server
you request a *URI* of, but can be attempted regardless. Most common feed
sources like blogs should however have this supported. To enable conditional
requests, you will need to provide a cache to `Zend\Feed\Reader\Reader`.

```php
$cache = Zend\Cache\StorageFactory::adapterFactory('Memory');

Zend\Feed\Reader\Reader::setCache($cache);
Zend\Feed\Reader\Reader::useHttpConditionalGet();

$feed = Zend\Feed\Reader\Reader::import('http://www.planet-php.net/rdf/');
```

In the example above, with HTTP Conditional `GET` requests enabled, the response
header values for ETag and Last-Modified will be cached along with the feed. For
the the cache's lifetime, feeds will only be updated on the cache if a non-304
response is received containing a valid RSS or Atom XML document.

If you intend on managing request headers from outside
`Zend\Feed\Reader\Reader`, you can set the relevant If-None-Matches and
If-Modified-Since request headers via the URI import method.

```php
$lastEtagReceived = '5e6cefe7df5a7e95c8b1ba1a2ccaff3d';
$lastModifiedDateReceived = 'Wed, 08 Jul 2009 13:37:22 GMT';
$feed = Zend\Feed\Reader\Reader::import(
    $uri, $lastEtagReceived, $lastModifiedDateReceived
);
```

## Locating Feed URIs from Websites

These days, many websites are aware that the location of their XML feeds is not
always obvious. A small RDF, RSS, or Atom graphic helps when the user is reading
the page, but what about when a machine visits trying to identify where your
feeds are located? To assist in this, websites may point to their feeds using
`<link>` tags in the `<head>` section of their HTML. To take advantage
of this, you can use `Zend\Feed\Reader\Reader` to locate these feeds using the
static `findFeedLinks()` method.

This method calls any URI and searches for the location of RSS, RDF, and Atom
feeds assuming, the website's HTML contains the relevant links. It then returns
a value object where you can check for the existence of a RSS, RDF or Atom feed
URI.

The returned object is an `ArrayObject` subclass called
`Zend\Feed\Reader\FeedSet`, so you can cast it to an array or iterate over it to
access all the detected links. However, as a simple shortcut, you can just grab
the first RSS, RDF, or Atom link using its public properties as in the example
below. Otherwise, each element of the `ArrayObject` is a simple array with the
keys `type` and `uri` where the type is one of "rdf", "rss", or "atom".

```php
$links = Zend\Feed\Reader\Reader::findFeedLinks('http://www.planet-php.net');

if (isset($links->rdf)) {
    echo $links->rdf, "\n"; // http://www.planet-php.org/rdf/
}
if (isset($links->rss)) {
    echo $links->rss, "\n"; // http://www.planet-php.org/rss/
}
if (isset($links->atom)) {
    echo $links->atom, "\n"; // http://www.planet-php.org/atom/
}
```

Based on these links, you can then import from whichever source you wish in the usual manner.

> ### Finding feed links requires an HTTP client
>
> To find feed links, you will need to have an [HTTP client](zend.feed.http-clients)
> available. 
>
> If you are not using zend-http, you will need to inject `Reader` with the HTTP
> client. See the [section on providing a client to Reader](http-clients.md#providing-a-client-to-reader).

This quick method only gives you one link for each feed type, but websites may
indicate many links of any type. Perhaps it's a news site with a RSS feed for
each news category. You can iterate over all links using the ArrayObject's
iterator.

```php
$links = Zend\Feed\Reader::findFeedLinks('http://www.planet-php.net');

foreach ($links as $link) {
    echo $link['href'], "\n";
}
```

## Attribute Collections

In an attempt to simplify return types, return types from the various feed and
entry level methods may include an object of type
`Zend\Feed\Reader\Collection\AbstractCollection`. Despite the special class name
which I'll explain below, this is just a simple subclass of SPL's `ArrayObject`.

The main purpose here is to allow the presentation of as much data as possible
from the requested elements, while still allowing access to the most relevant
data as a simple array. This also enforces a standard approach to returning such
data which previously may have wandered between arrays and objects.

The new class type acts identically to `ArrayObject` with the sole addition
being a new method `getValues()` which returns a simple flat array containing
the most relevant information.

A simple example of this is `Zend\Feed\Reader\Reader\FeedInterface::getCategories()`.
When used with any RSS or Atom feed, this method will return category data as a
container object called `Zend\Feed\Reader\Collection\Category`. The container
object will contain, per category, three fields of data: term, scheme, and label.
The "term" is the basic category name, often machine readable (i.e. plays nice
with URIs). The scheme represents a categorisation scheme (usually a URI
identifier) also known as a "domain" in RSS 2.0. The "label" is a human readable
category name which supports HTML entities. In RSS 2.0, there is no label
attribute so it is always set to the same value as the term for convenience.

To access category labels by themselves in a simple value array, you might
commit to something like:

```php
$feed = Zend\Feed\Reader\Reader::import('http://www.example.com/atom.xml');
$categories = $feed->getCategories();
$labels = [];
foreach ($categories as $cat) {
    $labels[] = $cat['label']
}
```

It's a contrived example, but the point is that the labels are tied up with
other information.

However, the container class allows you to access the "most relevant" data as a
simple array using the `getValues()` method. The concept of "most relevant" is
obviously a judgement call. For categories it means the category labels (not the
terms or schemes) while for authors it would be the authors' names (not their
email addresses or URIs). The simple array is flat (just values) and passed
through `array_unique()` to remove duplication.

```php
$feed = Zend\Feed\Reader\Reader::import('http://www.example.com/atom.xml');
$categories = $feed->getCategories();
$labels = $categories->getValues();
```

The above example shows how to extract only labels and nothing else thus giving
simple access to the category labels without any additional work to extract that
data by itself.

## Retrieving Feed Information

Retrieving information from a feed (we'll cover entries and items in the next
section though they follow identical principals) uses a clearly defined API
which is exactly the same regardless of whether the feed in question is RSS,
RDF, or Atom. The same goes for sub-versions of these standards and we've tested
every single RSS and Atom version. While the underlying feed XML can differ
substantially in terms of the tags and elements they present, they nonetheless
are all trying to convey similar information and to reflect this all the
differences and wrangling over alternative tags are handled internally by
`Zend\Feed\Reader\Reader` presenting you with an identical interface for each.
Ideally, you should not have to care whether a feed is RSS or Atom so long as
you can extract the information you want.

> ### RSS feeds vary widely
>
> While determining common ground between feed types is itself complex, it
> should be noted that *RSS* in particular is a constantly disputed
> "specification". This has its roots in the original RSS 2.0 document, which
> contains ambiguities and does not detail the correct treatment of all
> elements. As a result, this component rigorously applies the RSS 2.0.11
> Specification published by the RSS Advisory Board and its accompanying RSS
> Best Practices Profile. No other interpretation of RSS
> 2.0 will be supported, though exceptions may be allowed where it does not
> directly prevent the application of the two documents mentioned above.

Of course, we don't live in an ideal world, so there may be times the API just
does not cover what you're looking for. To assist you, `Zend\Feed\Reader\Reader`
offers a plugin system which allows you to write extensions to expand the core
API and cover any additional data you are trying to extract from feeds. If
writing another extension is too much trouble, you can simply grab the
underlying DOM or XPath objects and do it by hand in your application. Of
course, we really do encourage writing an extension simply to make it more
portable and reusable, and useful extensions may be proposed to the component
for formal addition.

Below is a summary of the Core API for feeds. You should note it comprises not
only the basic RSS and Atom standards, but also accounts for a number of
included extensions bundled with `Zend\Feed\Reader\Reader`. The naming of these
extension sourced methods remain fairly generic; all Extension methods operate
at the same level as the Core API though we do allow you to retrieve any
specific extension object separately if required.

### Feed Level API Methods

Method | Description
------ | -----------
`getId()` | Returns a unique ID associated with this feed
`getTitle()` |  Returns the title of the feed
`getDescription()` | Returns the text description of the feed.
`getLink()` | Returns a URI to the HTML website containing the same or similar information as this feed (i.e. if the feed is from a blog, it should provide the blog's URI where the HTML version of the entries can be read).
`getFeedLink()` | Returns the URI of this feed, which may be the same as the URI used to import the feed. There are important cases where the feed link may differ because the source URI is being updated and is intended to be removed in the future.
`getAuthors()` | Returns an object of type `Zend\Feed\Reader\Collection\Author` which is an `ArrayObject` whose elements are each simple arrays containing any combination of the keys "name", "email" and "uri". Where irrelevant to the source data, some of these keys may be omitted.
`getAuthor(integer $index = 0)` | Returns either the first author known, or with the optional $index parameter any specific index on the array of authors as described above (returning `NULL` if an invalid index).
`getDateCreated()` | Returns the date on which this feed was created. Generally only applicable to Atom, where it represents the date the resource described by an Atom 1.0 document was created. The returned date will be a `DateTime` object.
`getDateModified()` | Returns the date on which this feed was last modified. The returned date will be a `DateTime` object.
`getLastBuildDate()` |  Returns the date on which this feed was last built. The returned date will be a `DateTime` object. This is only supported by RSS; Atom feeds will always return `NULL`.
`getLanguage()` | Returns the language of the feed (if defined) or simply the language noted in the XML document.
`getGenerator()` |  Returns the generator of the feed, e.g. the software which generated it. This may differ between RSS and Atom since Atom defines a different notation.
`getCopyright()` | Returns any copyright notice associated with the feed.
`getHubs()` | Returns an array of all Hub Server URI endpoints which are advertised by the feed for use with the Pubsubhubbub Protocol, allowing subscriptions to the feed for real-time updates.
`getCategories()` | Returns a `Zend\Feed\Reader\Collection\Category` object containing the details of any categories associated with the overall feed. The supported fields include "term" (the machine readable category name), "scheme" (the categorisation scheme and domain for this category), and "label" (a HTML decoded human readable category name). Where any of the three fields are absent from the field, they are either set to the closest available alternative or, in the case of "scheme", set to `NULL`.
`getImage()` | Returns an array containing data relating to any feed image or logo, or `NULL` if no image found. The resulting array may contain the following keys: uri, link, title, description, height, and width. Atom logos only contain a URI so the remaining metadata is drawn from RSS feeds only.

Given the variety of feeds in the wild, some of these methods will undoubtedly
return `NULL` indicating the relevant information couldn't be located. Where
possible, `Zend\Feed\Reader\Reader` will fall back on alternative elements
during its search. For example, searching an RSS feed for a modification date is
more complicated than it looks. RSS 2.0 feeds should include a `<lastBuildDate>`
tag and/or a `<pubDate>` element. But what if it doesn't? Maybe this is an RSS
1.0 feed? Perhaps it instead has an `<atom:updated>` element with identical
information (Atom may be used to supplement RSS syntax)? Failing that, we
could simply look at the entries, pick the most recent, and use its `<pubDate>`
element. Assuming it exists, that is. Many feeds also use Dublin Core 1.0 or 1.1
`<dc:date>` elements for feeds and entries. Or we could find Atom lurking again.

The point is, `Zend\Feed\Reader\Reader` was designed to know this. When you ask
for the modification date (or anything else), it will run off and search for all
these alternatives until it either gives up and returns `NULL`, or finds an
alternative that should have the right answer.

In addition to the above methods, all feed objects implement methods for
retrieving the DOM and XPath objects for the current feeds as described
earlier. Feed objects also implement the SPL Iterator and Countable
interfaces. The extended API is summarised below.

### Extended Feed API Methods

Method | Description
------ | -----------
`getDomDocument()` | Returns the parent DOMDocument object for the entire source XML document.
`getElement()` | Returns the current feed level DOMElement object.
`saveXml()` | Returns a string containing an XML document of the entire feed element (this is not the original document, but a rebuilt version).
`getXpath()` | Returns the DOMXPath object used internally to run queries on the DOMDocument object (this includes core and extension namespaces pre-registered).
`getXpathPrefix()` | Returns the valid DOM path prefix prepended to all XPath queries matching the feed being queried.
`getEncoding()` | Returns the encoding of the source XML document (note: this cannot account for errors such as the server sending documents in a different encoding). Where not defined, the default UTF-8 encoding of Unicode is applied.
`count()` | Returns a count of the entries or items this feed contains (implements SPL `Countable` interface)
`current()` | Returns either the current entry (using the current index from `key()`).
`key()` | Returns the current entry index.
`next()` | Increments the entry index value by one.
`rewind()` | Resets the entry index to 0.
`valid()` | Checks that the current entry index is valid, i.e. it does not fall below 0 and does not exceed the number of entries existing.
`getExtensions()` | Returns an array of all extension objects loaded for the current feed (note: both feed-level and entry-level extensions exist, and only feed-level extensions are returned here). The array keys are of the form `{ExtensionName}_Feed`.
`getExtension(string $name)` | Returns an extension object for the feed registered under the provided name. This allows more fine-grained access to extensions which may otherwise be hidden within the implementation of the standard API methods.
`getType()` | Returns a static class constant (e.g.  `Zend\Feed\Reader\Reader::TYPE_ATOM_03`, i.e. "Atom 0.3"), indicating exactly what kind of feed is being consumed.

## Retrieving Entry/Item Information

Retrieving information for specific entries or items (depending on whether you
speak Atom or RSS) is identical to feed level data. Accessing entries is
simply a matter of iterating over a feed object or using the SPL `Iterator`
interface feed objects implement, and calling the appropriate method on each.

### Entry API Methods

Method | Description
------ | -----------
`getId()` | Returns a unique ID for the current entry.
`getTitle()` | Returns the title of the current entry.
`getDescription()` | Returns a description of the current entry.
`getLink()` | Returns a URI to the HTML version of the current entry.
`getPermaLink()` | Returns the permanent link to the current entry. In most cases, this is the same as using `getLink()`.
`getAuthors()` | Returns an object of type `Zend\Feed\Reader\Collection\Author`, which is an `ArrayObject` whose elements are each simple arrays containing any combination of the keys "name", "email" and "uri". Where irrelevant to the source data, some of these keys may be omitted.
`getAuthor(integer $index = 0)` | Returns either the first author known, or, with the optional `$index` parameter, any specific index on the array of Authors as described above (returning `NULL` if an invalid index).
`getDateCreated()` | Returns the date on which the current entry was created. Generally only applicable to Atom where it represents the date the resource described by an Atom 1.0 document was created.
`getDateModified()` | Returns the date on which the current entry was last modified.
`getContent()` | Returns the content of the current entry (this has any entities reversed if possible, assuming the content type is HTML). The description is returned if a separate content element does not exist.
`getEnclosure()` | Returns an array containing the value of all attributes from a multi-media `<enclosure>` element including as array keys: url, length, type. In accordance with the RSS Best Practices Profile of the RSS Advisory Board, no support is offers for multiple enclosures since such support forms no part of the RSS specification.
`getCommentCount()` | Returns the number of comments made on this entry at the time the feed was last generated.
`getCommentLink()` | Returns a URI pointing to the HTML page where comments can be made on this entry.
`getCommentFeedLink([string $type = ‘atom'|'rss'])` | Returns a URI pointing to a feed of the provided type containing all comments for this entry (type defaults to Atom/RSS depending on current feed type).
`getCategories()` | Returns a `Zend\Feed\Reader\Collection\Category` object containing the details of any categories associated with the entry. The supported fields include "term" (the machine readable category name), "scheme" (the categorisation scheme and domain for this category), and "label" (an HTML-decoded human readable category name). Where any of the three fields are absent from the field, they are either set to the closest available alternative or, in the case of "scheme", set to `NULL`.

The extended API for entries is identical to that for feeds with the exception
of the `Iterator` methods, which are not needed here.

> ### Modified vs Created dates
> 
> There is often confusion over the concepts of *modified* and *created* dates.
> In Atom, these are two clearly defined concepts (so knock yourself out) but in
> RSS they are vague. RSS 2.0 defines a single `<pubDate>` element which
> typically refers to the date this entry was published, i.e.  a creation date of
> sorts. This is not always the case, and it may change with updates or not. As a
> result, if you really want to check whether an entry has changed, don't rely on
> the results of `getDateModified()`. Instead, consider tracking the MD5 hash of
> three other elements concatenated, e.g. using `getTitle()`, `getDescription()`,
> and `getContent()`. If the entry was truly updated, this hash computation will
> give a different result than previously saved hashes for the same entry. This
> is obviously content oriented, and will not assist in detecting changes to
> other relevant elements.  Atom feeds should not require such steps.

> Further muddying the waters, dates in feeds may follow different standards.
> Atom and Dublin Core dates should follow ISO 8601, and RSS dates should
> follow RFC 822 or RFC 2822 (which is also common). Date methods will throw an
> exception if `DateTime` cannot load the date string using one of the above
> standards, or the PHP recognised possibilities for RSS dates.

> ### Validation
>
> The values returned from these methods are not validated. This means users
> must perform validation on all retrieved data including the filtering of any
> HTML such as from `getContent()` before it is output from your application.
> Remember that most feeds come from external sources, and therefore the default
> assumption should be that they cannot be trusted.

### Extended Entry Level API Methods

Method | Description
------ | -----------
`getDomDocument()` | Returns the parent DOMDocument object for the entire feed (not just the current entry).
`getElement()` | Returns the current entry level DOMElement object.
`getXpath()` | Returns the DOMXPath object used internally to run queries on the DOMDocument object (this includes core and extension namespaces pre-registered).
`getXpathPrefix()` | Returns the valid DOM path prefix prepended to all XPath queries matching the entry being queried.
`getEncoding()` | Returns the encoding of the source XML document (note: this cannot account for errors such as the server sending documents in a different encoding). The default encoding applied in the absence of any other is the UTF-8 encoding of Unicode.
`getExtensions()` | Returns an array of all extension objects loaded for the current entry (note: both feed-level and entry-level extensions exist, and only entry-level extensions are returned here). The array keys are in the form `{ExtensionName}Entry`.
`getExtension(string $name)` | Returns an extension object for the entry registered under the provided name. This allows more fine-grained access to extensions which may otherwise be hidden within the implementation of the standard API methods.
`getType()` | Returns a static class constant (e.g. `Zend\Feed\Reader\Reader::TYPE_ATOM_03`, i.e. "Atom 0.3") indicating exactly what kind of feed is being consumed.

## Extending Feed and Entry APIs

Extending `Zend\Feed\Reader\Reader` allows you to add methods at both the feed
and entry level which cover the retrieval of information not already supported
by `Zend\Feed\Reader\Reader`. Given the number of RSS and Atom extensions that
exist, this is a good thing, since `Zend\Feed\Reader\Reader` couldn't possibly
add everything.

There are two types of extensions possible, those which retrieve information
from elements which are immediate children of the root element (e.g.
`<channel>` for RSS or `<feed>` for Atom), and those who retrieve information
from child elements of an entry (e.g. `<item>` for RSS or `<entry>` for Atom).
On the filesystem, these are grouped as classes within a namespace based on the
extension standard's name. For example, internally we have
`Zend\Feed\Reader\Extension\DublinCore\Feed` and
`Zend\Feed\Reader\Extension\DublinCore\Entry` classes which are two extensions
implementing Dublin Core 1.0 and 1.1 support.

Extensions are loaded into `Zend\Feed\Reader\Reader` using an "extension
manager". Extension managers must implement `Zend\Feed\Reader\ExtensionManagerInterface`.
Three implementations exist:

- `Zend\Feed\Reader\StandaloneExtensionManager` is a hard-coded implementation
  seeded with all feed and entry implementations. You can extend it to add
  extensions, though it's likely easier to copy and paste it, adding your
  changes.
- `Zend\Feed\Reader\ExtensionPluginManager` is a `Zend\ServiceManager\AbstractPluginManager`
  implementation, `Zend\Feed\Reader\ExtensionManager`; as such, you can extend
  it to add more extensions, use a `Zend\ServiceManager\ConfigInterface` instance
  to inject it with more extensions, or use its public API for adding services
  (e.g., `setService()`, `setFactory()`, etc.). This implementation *does not*
  implement `ExtensionManagerInterface`, and must be used with `ExtensionManager`.
- `Zend\Feed\Reader\ExtensionManager` exists for legacy purposes; prior to 2.3,
  this was an `AbstractPluginManager` implementation, and the only provided
  extension manager. It now implements `ExtensionManagerInterface`, and acts as
  a decorator for `ExtensionPluginManager`.

By default, `Zend\Feed\Reader\Reader` composes a `StandaloneExtensionManager`. You
can inject an alternate implementation using `Reader::setExtensionManager()`:

```php
$extensions = new Zend\Feed\Reader\ExtensionPluginManager();
Zend\Feed\Reader\Reader::setExtensionManager(
    new ExtensionManager($extensions)
);
```

The shipped implementations all provide the default extensions (so-called
"Core Extensions") used internally by `Zend\Feed\Reader\Reader`. These
include:

Extension | Description
--------- | -----------
DublinCore (Feed and Entry) | Implements support for Dublin Core Metadata Element Set 1.0 and 1.1.
Content (Entry only) | Implements support for Content 1.0.
Atom (Feed and Entry) | Implements support for Atom 0.3 and Atom 1.0.
Slash | Implements support for the Slash RSS 1.0 module.
WellFormedWeb | Implements support for the Well Formed Web CommentAPI 1.0.
Thread | Implements support for Atom Threading Extensions as described in RFC 4685.
Podcast | Implements support for the Podcast 1.0 DTD from Apple.

The core extensions are somewhat special since they are extremely common and
multi-faceted. For example, we have a core extension for Atom. Atom is
implemented as an extension (not just a base class) because it doubles as a
valid RSS module; you can insert Atom elements into RSS feeds.  I've even seen
RDF feeds which use a lot of Atom in place of more common extensions like
Dublin Core.

The following is a list of non-Core extensions that are offered, but not registered 
by default. If you want to use them, you'll need to
tell `Zend\Feed\Reader\Reader` to load them in advance of importing a feed.
Additional non-Core extensions will be included in future iterations of the
component.

Extension | Description
--------- | -----------
Syndication | Implements Syndication 1.0 support for RSS feeds.
CreativeCommons | An RSS module that adds an element at the `<channel>` or `<item>` level that specifies which Creative Commons license applies.

`Zend\Feed\Reader\Reader` requires you to explicitly register non-Core
extensions in order to expose their API to feed and entry objects.  Below, we
register the optional Syndication extension, and discover that it can be
directly called from the entry API without any effort. (Note that
extension names are case sensitive and use camelCasing for multiple terms.)

```php
use Zend\Feed\Reader\Reader;

Reader::registerExtension('Syndication');
$feed = Reader::import('http://rss.slashdot.org/Slashdot/slashdot');
$updatePeriod = $feed->getUpdatePeriod();
```

In the simple example above, we checked how frequently a feed is being updated
using the `getUpdatePeriod()` method. Since it's not part of
`Zend\Feed\Reader\Reader`'s core API, it could only be a method supported by
the newly registered Syndication extension.

As you can also notice, methods provided by extensions are accessible from the
main API using method overloading. As an alternative, you can also directly
access any extension object for a similar result as seen below.

```php
use Zend\Feed\Reader\Reader;

Reader::registerExtension('Syndication');
$feed = Reader::import('http://rss.slashdot.org/Slashdot/slashdot');
$syndication = $feed->getExtension('Syndication');
$updatePeriod = $syndication->getUpdatePeriod();
```

### Writing Zend\\Feed\\Reader Extensions

Inevitably, there will be times when the `Zend\Feed\Reader` API is just
not capable of getting something you need from a feed or entry. You can use the
underlying source objects, like DOMDocument, to get these by hand; however, there
is a more reusable method available: you can write extensions supporting these new
queries.

As an example, let's take the case of a purely fictitious corporation named
Jungle Books. Jungle Books have been publishing a lot of reviews on books they
sell (from external sources and customers), which are distributed as an RSS 2.0
feed. Their marketing department realises that web applications using this feed
cannot currently figure out exactly what book is being reviewed. To make life
easier for everyone, they determine that the geek department needs to extend
RSS 2.0 to include a new element per entry supplying the ISBN-10 or ISBN-13
number of the publication the entry concerns. They define the new `<isbn>`
element quite simply with a standard name and namespace URI:

- Name: JungleBooks 1.0
- Namespace URI: http://example.com/junglebooks/rss/module/1.0/

A snippet of RSS containing this extension in practice could be something
similar to:

```xml
<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0"
   xmlns:content="http://purl.org/rss/1.0/modules/content/"
   xmlns:jungle="http://example.com/junglebooks/rss/module/1.0/">
<channel>
    <title>Jungle Books Customer Reviews</title>
    <link>http://example.com/junglebooks</link>
    <description>Many book reviews!</description>
    <pubDate>Fri, 26 Jun 2009 19:15:10 GMT</pubDate>
    <jungle:dayPopular>
        http://example.com/junglebooks/book/938
    </jungle:dayPopular>
    <item>
        <title>Review Of Flatland: A Romance of Many Dimensions</title>
        <link>http://example.com/junglebooks/review/987</link>
        <author>Confused Physics Student</author>
        <content:encoded>
        A romantic square?!
        </content:encoded>
        <pubDate>Thu, 25 Jun 2009 20:03:28 -0700</pubDate>
        <jungle:isbn>048627263X</jungle:isbn>
    </item>
</channel>
</rss>
```

Implementing this new ISBN element as a simple entry level extension would
require the following class (using your own namespace).

```php
namespace My\FeedReader\Extension\JungleBooks;

use Zend\Feed\Reader\Extension\AbstractEntry;

class Entry extends AbstractEntry
{
    public function getIsbn()
    {
        if (isset($this->data['isbn'])) {
            return $this->data['isbn'];
        }

        $isbn = $this->xpath->evaluate(
            'string(' . $this->getXpathPrefix() . '/jungle:isbn)'
        );

        if (! $isbn) {
            $isbn = null;
        }

        $this->data['isbn'] = $isbn;
        return $this->data['isbn'];
    }

    protected function registerNamespaces()
    {
        $this->xpath->registerNamespace(
            'jungle',
            'http://example.com/junglebooks/rss/module/1.0/'
        );
    }
}
```

This extension creates a new method `getIsbn()`, which runs an XPath query on
the current entry to extract the ISBN number enclosed by the `<jungle:isbn>`
element. It can optionally store this to the internal non-persistent cache (no
need to keep querying the DOM if it's called again on the same entry). The
value is returned to the caller. At the end we have a protected method (it's
abstract, making it required by implementations) which registers the Jungle
Books namespace for their custom RSS module. While we call this an RSS module,
there's nothing to prevent the same element being used in Atom feeds; all
extensions which use the prefix provided by `getXpathPrefix()` are actually
neutral and work on RSS or Atom feeds with no extra code.

Since this extension is stored outside of zend-feed, you'll need to ensure your
application can autoload it. Once that's in place, you will also need to ensure
your extension manager knows about it, and then register the extension with
`Zend\Feed\Reader\Reader`.

The following example uses `Zend\Feed\Reader\ExtensionPluginManager` to manage
extensions, as it provides the ability to register new extensions without
requiring extension of the plugin manager itself. To use it, first intall
zend-servicemanager:

```bash
$ composer require zendframework/zend-servicemanager
```

From there:

```php
use My\FeedReader\Extension\JungleBooks;
use Zend\Feed\Reader\ExtensionManager;
use Zend\Feed\Reader\ExtensionPluginManager;
use Zend\Feed\Reader\Reader;

$extensions = new ExtensionPluginManager();
$extensions->setInvokableClass('JungleBooksEntry', JungleBooks\Entry::class);
Reader::setExtensionManager(new ExtensionManager($extensions));
Reader::registerExtension('JungleBooks');

$feed = Reader::import('http://example.com/junglebooks/rss');

// ISBN for whatever book the first entry in the feed was concerned with
$firstIsbn = $feed->current()->getIsbn();
```

Writing a feed extension is not much different. The example feed from earlier
included an unmentioned `<jungle:dayPopular>` element which Jungle Books have
added to their standard to include a link to the day's most popular book (in
terms of visitor traffic). Here's an extension which adds a
`getDaysPopularBookLink()` method to the feel level API.

```php
namespace My\FeedReader\Extension\JungleBooks;

use Zend\Feed\Reader\Extension\AbstractFeed;

class Feed extends AbstractFeed
{
    public function getDaysPopularBookLink()
    {
        if (isset($this->data['dayPopular'])) {
            return $this->data['dayPopular'];
        }

        $dayPopular = $this->xpath->evaluate(
            'string(' . $this->getXpathPrefix() . '/jungle:dayPopular)'
        );

        if (!$dayPopular) {
            $dayPopular = null;
        }

        $this->data['dayPopular'] = $dayPopular;
        return $this->data['dayPopular'];
    }

    protected function registerNamespaces()
    {
        $this->xpath->registerNamespace(
            'jungle',
            'http://example.com/junglebooks/rss/module/1.0/'
        );
    }
}
```

Let's add to the previous example; we'll register the new class with the
extension manager, and then demonstrate using the newly exposed method:

```php
use My\FeedReader\Extension\JungleBooks;
use Zend\Feed\Reader\ExtensionManager;
use Zend\Feed\Reader\ExtensionPluginManager;
use Zend\Feed\Reader\Reader;

$extensions = new ExtensionPluginManager();
$extensions->setInvokableClass('JungleBooksEntry', JungleBooks\Entry::class);
$extensions->setInvokableClass('JungleBooksFeed', JungleBooks\Feed::class);
Reader::setExtensionManager(new ExtensionManager($extensions));
Reader::registerExtension('JungleBooks');

$feed = Reader::import('http://example.com/junglebooks/rss');

// URI to the information page of the day's most popular book with visitors
$daysPopularBookLink = $feed->getDaysPopularBookLink();
```

Going through these examples, you'll note that while we need to register the
feed and entry classes separately with the plugin manager, we don't register
them separately when registering the extension with the `Reader`.  Extensions
within the same standard may or may not include both a feed and entry class, so
`Zend\Feed\Reader\Reader` only requires you to register the overall parent name,
e.g.  JungleBooks, DublinCore, Slash. Internally, it can check at what level
extensions exist and load them up if found. In our case, we have a complete
extension now, spanning the classes `JungleBooks\Feed` and `JungleBooks\Entry`.
