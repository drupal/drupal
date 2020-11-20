# Consuming RSS Feeds

## Reading a feed

To read an RSS feed, pass its URL to `Zend\Feed\Reader\Reader::import()`:

```php
$channel = Zend\Feed\Reader\Reader::import('http://rss.example.com/channelName');
```

> ## Importing requires an HTTP client
>
> To import a feed, you will need to have an [HTTP client](zend.feed.http-clients)
> available. 
>
> If you are not using zend-http, you will need to inject `Reader` with the HTTP
> client. See the [section on providing a client to Reader](http-clients.md#providing-a-client-to-reader).

If any errors occur fetching the feed, a
`Zend\Feed\Reader\Exception\RuntimeException` will be thrown.

## Get properties

Once you have a feed object, you can access any of the standard RSS channel
properties via the various instance getter methods:

```php
echo $channel->getTitle();
echo $channel->getAuthor();
// etc.
```

If channel properties have attributes, the getter method will return a key/value
pair, where the key is the attribute name, and the value is the attribute value.

```php
$author = $channel->getAuthor();
echo $author['name'];
```

Most commonly, you'll want to loop through the feed and do something with its
entries.  `Zend\Feed\Reader\Feed\Rss` internally converts all entries to a
`Zend\Feed\Reader\Entry\Rss` instance. Entry properties, similarly to channel
properties, can be accessed via getter methods, such as `getTitle`,
`getDescription`, etc.

An example of printing all titles of articles in a channel is:

```php
foreach ($channel as $item) {
    echo $item->getTitle() . "\n";
}
```

If you are not familiar with RSS, here are the standard elements you can expect
to be available in an RSS channel and in individual RSS items (entries).

Required channel elements:

- `title`: The name of the channel.
- `link`: The URL of the web site corresponding to the channel.
- `description`: A sentence (or more) describing the channel.

Common optional channel elements:

- `pubDate`: The publication date of this set of content, in RFC 822 date
  format.
- `language`: The language the channel is written in.
- `category`: One or more (specified by multiple tags) categories the channel
  belongs to.

RSS `<item>` elements do not have any strictly required elements. However,
either `title` or `description` must be present.

Common item elements:

- `title`: The title of the item.
- `link`: The URL of the item.
- `description`: A synopsis of the item.
- `author`: The author's email address.
- `category`: One more categories that the item belongs to.
- `comments`: URL of comments relating to this item.
- `pubDate`: The date the item was published, in RFC 822 date format.

In your code you can always test to see if an element is non-empty by calling
the getter:

```php
if ($item->getPropname()) {
    // ... proceed.
}
```

Where relevant, `Zend\Feed` supports a number of common RSS extensions including
Dublin Core, Atom (inside RSS); the Content, Slash, Syndication,
Syndication/Thread extensions; as well as several others.

Please see the official [RSS 2.0 specification](http://cyber.law.harvard.edu/rss/rss.html)
for further information.
