# Consuming a Single Atom Entry

Single Atom `<entry>` elements are also valid by themselves. Usually the URL for
an entry is the feed's URL followed by `/<entryId>`, such as
`http://atom.example.com/feed/1`, using the example URL we used above. This
pattern may exist for some web services which use Atom as a container syntax.

If you read a single entry, you will have a `Zend\Feed\Reader\Entry\Atom` object.

## Reading a Single-Entry Atom Feed

```php
$entry = Zend\Feed\Reader\Reader::import('http://atom.example.com/feed/1');
echo 'Entry title: ' . $entry->getTitle();
```

> ## Importing requires an HTTP client
>
> To import a feed, you will need to have an [HTTP client](zend.feed.http-clients)
> available. 
>
> If you are not using zend-http, you will need to inject `Reader` with the HTTP
> client. See the [section on providing a client to Reader](http-clients.md#providing-a-client-to-reader).
