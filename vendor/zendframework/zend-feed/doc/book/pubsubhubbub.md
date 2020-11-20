# Zend\\Feed\\PubSubHubbub

`Zend\Feed\PubSubHubbub` is an implementation of the [PubSubHubbub Core 0.2/0.3
Specification (Working Draft)](http://pubsubhubbub.googlecode.com/svn/trunk/pubsubhubbub-core-0.3.html).
It offers implementations of a Pubsubhubbub Publisher and Subscriber suited to
PHP applications.

## What is PubSubHubbub?

Pubsubhubbub is an open, simple, web-scale, pubsub protocol. A common use case
to enable blogs (Publishers) to "push" updates from their RSS or Atom feeds
(Topics) to end Subscribers. These Subscribers will have subscribed to the
blog's RSS or Atom feed via a Hub, a central server which is notified of any
updates by the Publisher, and which then distributes these updates to all
Subscribers. Any feed may advertise that it supports one or more Hubs using an
Atom namespaced link element with a rel attribute of "hub" (i.e., `rel="hub"`).

Pubsubhubbub has garnered attention because it is a pubsub protocol which is
easy to implement and which operates over HTTP. Its philosophy is to replace the
traditional model where blog feeds have been polled at regular intervals to
detect and retrieve updates. Depending on the frequency of polling, this can
take a lot of time to propagate updates to interested parties from planet
aggregators to desktop readers. With a pubsub system in place, updates are not
simply polled by Subscribers, they are pushed to Subscribers, eliminating any
delay. For this reason, Pubsubhubbub forms part of what has been dubbed the
real-time web.

The protocol does not exist in isolation. Pubsub systems have been around for a
while, such as the familiar Jabber Publish-Subscribe protocol,
[XEP-0060](http://www.xmpp.org/extensions/xep-0060.html), or the less well-known
[rssCloud](http://www.rssboard.org/rsscloud-interface) (described in 2001).
However, these have not achieved widespread adoption due to either their
complexity, poor timing, or lack of suitability for web applications. rssCloud,
which was recently revived as a response to the appearance of Pubsubhubbub, has
also seen its usage increase significantly, though it lacks a formal
specification and currently does not support Atom 1.0 feeds.

Perhaps surprisingly given its relative early age, Pubsubhubbub is already in
use including in Google Reader and Feedburner, and there are plugins available
for Wordpress blogs.

## Architecture

`Zend\Feed\PubSubHubbub` implements two sides of the Pubsubhubbub 0.2/0.3
Specification: a Publisher and a Subscriber. It does not currently implement a
Hub Server.

A Publisher is responsible for notifying all supported Hubs (many can be
supported to add redundancy to the system) of any updates to its feeds, whether
they be Atom or RSS based. This is achieved by pinging the supported Hub Servers
with the URL of the updated feed. In Pubsubhubbub terminology, any updatable
resource capable of being subscribed to is referred to as a Topic. Once a ping
is received, the Hub will request the updated feed, process it for updated
items, and forward all updates to all Subscribers subscribed to that feed.

A Subscriber is any party or application which subscribes to one or more Hubs to
receive updates from a Topic hosted by a Publisher. The Subscriber never
directly communicates with the Publisher since the Hub acts as an intermediary,
accepting subscriptions and sending updates to Subscribers. The Subscriber
therefore communicates only with the Hub, either to subscribe or unsubscribe to
Topics, or when it receives updates from the Hub. This communication design
("Fat Pings") effectively removes the possibility of a "Thundering Herd" issue.
(Thundering Herds occur in a pubsub system where the Hub merely informs
Subscribers that an update is available, prompting all Subscribers to
immediately retrieve the feed from the Publisher, giving rise to a traffic
spike.) In Pubsubhubbub, the Hub distributes the actual update in a "Fat Ping"
so the Publisher is not subjected to any traffic spike.

`Zend\Feed\PubSubHubbub` implements Pubsubhubbub Publishers and Subscribers with
the classes `Zend\Feed\PubSubHubbub\Publisher` and
`Zend\Feed\PubSubHubbub\Subscriber`. In addition, the Subscriber implementation
may handle any feed updates forwarded from a Hub by using
`Zend\Feed\PubSubHubbub\Subscriber\Callback`. These classes, their use cases,
and etheir APIs are covered in subsequent sections.

## Zend\\Feed\\PubSubHubbub\\Publisher

In Pubsubhubbub, the Publisher is the party publishing a live feed with content
updates. This may be a blog, an aggregator, or even a web service with a public
feed based API. In order for these updates to be pushed to Subscribers, the
Publisher must notify all of its supported Hubs that an update has occurred
using a simple HTTP POST request containing the URI of the updated Topic (i.e.,
the updated RSS or Atom feed). The Hub will confirm receipt of the notification,
fetch the updated feed, and forward any updates to any Subscribers who have
subscribed to that Hub for updates from the relevant feed.

By design, this means the Publisher has very little to do except send these Hub
pings whenever its feeds change. As a result, the Publisher implementation is
extremely simple to use and requires very little work to setup and use when
feeds are updated.

`Zend\Feed\PubSubHubbub\Publisher` implements a full Pubsubhubbub Publisher. Its
setup for use primarily requires that it is configured with the URI endpoint for
all Hubs to be notified of updates, and the URIs of all Topics to be included in
the notifications.

The following example shows a Publisher notifying a collection of Hubs about
updates to a pair of local RSS and Atom feeds. The class retains a collection of
errors which include the Hub URLs, so that notification can be attempted again
later and/or logged if any notifications happen to fail.  Each resulting error
array also includes a "response" key containing the related HTTP response
object. In the event of any errors, it is strongly recommended to attempt the
operation for failed Hub Endpoints at least once more at a future time. This may
require the use of either a scheduled task for this purpose or a job queue,
though such extra steps are optional.

```php
use Zend\Feed\PubSubHubbub\Publisher;

$publisher = Publisher;
$publisher->addHubUrls([
    'http://pubsubhubbub.appspot.com/',
    'http://hubbub.example.com',
]);
$publisher->addUpdatedTopicUrls([
    'http://www.example.net/rss',
    'http://www.example.net/atom',
]);
$publisher->notifyAll();

if (! $publisher->isSuccess()) {
    // check for errors
    $errors     = $publisher->getErrors();
    $failedHubs = [];
    foreach ($errors as $error) {
        $failedHubs[] = $error['hubUrl'];
    }
}

// reschedule notifications for the failed Hubs in $failedHubs
```

If you prefer having more concrete control over the Publisher, the methods
`addHubUrls()` and `addUpdatedTopicUrls()` pass each array value to the singular
`addHubUrl()` and `addUpdatedTopicUrl()` public methods. There are also matching
`removeUpdatedTopicUrl()` and `removeHubUrl()` methods.

You can also skip setting Hub URIs, and notify each in turn using the
`notifyHub()` method which accepts the URI of a Hub endpoint as its only
argument.

There are no other tasks to cover. The Publisher implementation is very simple
since most of the feed processing and distribution is handled by the selected
Hubs. It is, however, important to detect errors and reschedule notifications as
soon as possible (with a reasonable maximum number of retries) to ensure
notifications reach all Subscribers. In many cases, as a final alternative, Hubs
may frequently poll your feeds to offer some additional tolerance for failures
both in terms of their own temporary downtime or Publisher errors or downtime.

## Zend\\Feed\\PubSubHubbub\\Subscriber

In Pubsubhubbub, the Subscriber is the party who wishes to receive updates to
any Topic (RSS or Atom feed). They achieve this by subscribing to one or more of
the Hubs advertised by that Topic, usually as a set of one or more Atom 1.0
links with a rel attribute of "hub" (i.e., `rel="hub"`). The Hub from that point
forward will send an Atom or RSS feed containing all updates to that
Subscriber's callback URL when it receives an update notification from the
Publisher. In this way, the Subscriber need never actually visit the original
feed (though it's still recommended at some level to ensure updates are
retrieved if ever a Hub goes offline). All subscription requests must contain
the URI of the Topic being subscribed and a callback URL which the Hub will use
to confirm the subscription and to forward updates.

The Subscriber therefore has two roles. The first is to *create* and *manage*
subscriptions, including subscribing for new Topics with a Hub, unsubscribing
(if necessary), and periodically renewing subscriptions, since they may have an
expiry set by the Hub. This is handled by `Zend\Feed\PubSubHubbub\Subscriber`.

The second role is to *accept updates* sent by a Hub to the Subscriber's
callback URL, i.e. the URI the Subscriber has assigned to handle updates. The
callback URL also handles events where the Hub contacts the Subscriber to
confirm all subscriptions and unsubscriptions. This is handled by using an
instance of `Zend\Feed\PubSubHubbub\Subscriber\Callback` when the callback URL
is accessed.

> ### Query strings in callback URLs
>
> `Zend\Feed\PubSubHubbub\Subscriber` implements the Pubsubhubbub 0.2/0.3
> specification. As this is a new specification version, not all Hubs currently
> implement it. The new specification allows the callback URL to include a query
> string which is used by this class, but not supported by all Hubs. In the
> interests of maximising compatibility, it is therefore recommended that the
> query string component of the Subscriber callback URI be presented as a path
> element, i.e. recognised as a parameter in the route associated with the
> callback URI and used by the application's router.

### Subscribing and Unsubscribing

`Zend\Feed\PubSubHubbub\Subscriber` implements a full Pubsubhubbub Subscriber
capable of subscribing to, or unsubscribing from, any Topic via any Hub
advertised by that Topic. It operates in conjunction with
`Zend\Feed\PubSubHubbub\Subscriber\Callback`, which accepts requests from a Hub
to confirm all subscription or unsubscription attempts (to prevent third-party
misuse).

Any subscription (or unsubscription) requires the relevant information before
proceeding, i.e. the URI of the Topic (Atom or RSS feed) to be subscribed to for
updates, and the URI of the endpoint for the Hub which will handle the
subscription and forwarding of the updates. The lifetime of a subscription may
be determined by the Hub, but most Hubs should support automatic subscription
refreshes by checking with the Subscriber. This is supported by
`Zend\Feed\PubSubHubbub\Subscriber\Callback` and requires no other work on your
part. It is still strongly recommended that you use the Hub-sourced subscription
time-to.live (ttl) to schedule the creation of new subscriptions (the process is
identical to that for any new subscription) to refresh it with the Hub. While it
should not be necessary per se, it covers cases where a Hub may not support
automatic subscription refreshing, and rules out Hub errors for additional
redundancy.

With the relevant information to hand, a subscription can be attempted as
demonstrated below:

```php
use Zend\Feed\PubSubHubbub\Model\Subscription;
use Zend\Feed\PubSubHubbub\Subscriber;

$storage    = new Subscription;
$subscriber = new Subscriber;
$subscriber->setStorage($storage);
$subscriber->addHubUrl('http://hubbub.example.com');
$subscriber->setTopicUrl('http://www.example.net/rss.xml');
$subscriber->setCallbackUrl('http://www.mydomain.com/hubbub/callback');
$subscriber->subscribeAll();
```

In order to store subscriptions and offer access to this data for general use,
the component requires a database (a schema is provided later in this section).
By default, it is assumed the table name is "subscription", and it utilises
`Zend\Db\TableGateway\TableGateway` in the background, meaning it will use the
default adapter you have set for your application. You may also pass a specific
custom `Zend\Db\TableGateway\TableGateway` instance into the associated model
`Zend\Feed\PubSubHubbub\Model\Subscription`. This custom adapter may be as
simple in intent as changing the table name to use or as complex as you deem
necessary.

While this model is offered as a default ready-to-roll solution, you may create
your own model using any other backend or database layer (e.g. Doctrine) so long
as the resulting class implements the interface
`Zend\Feed\PubSubHubbub\Model\SubscriptionInterface`.

An example schema (MySQL) for a subscription table accessible by the provided
model may look similar to:

```sql
CREATE TABLE IF NOT EXISTS `subscription` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `topic_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hub_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `lease_seconds` bigint(20) DEFAULT NULL,
  `verify_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `secret` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expiration_time` datetime DEFAULT NULL,
  `subscription_state` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

Behind the scenes, the Subscriber above will send a request to the Hub endpoint
containing the following parameters (based on the previous example):

Parameter | Value | Explanation
--------- | ----- | -----------
`hub.callback` | `http://www.mydomain.com/hubbub/callback?xhub.subscription=5536df06b5dcb966edab3a4c4d56213c16a8184` | The URI used by a Hub to contact the Subscriber and either request confirmation of a (un)subscription request, or send updates from subscribed feeds. The appended query string contains a custom parameter (hence the xhub designation). It is a query string parameter preserved by the Hub and re-sent with all Subscriber requests. Its purpose is to allow the Subscriber to identify and look up the subscription associated with any Hub request in a backend storage medium. This is a non-standard parameter used by this component in preference to encoding a subscription key in the URI path, which is difficult to enforce generically. Nevertheless, since not all Hubs support query string parameters, we still strongly recommend adding the subscription key as a path component in the form `http://www.mydomain.com/hubbub/callback/5536df06b5dcb966edab3a4c4d56213c16a8184`.  This requires defining a route capable of parsing out the final value of the key, retrieving the value, and passing it to the Subscriber callback object. The value should be passed into the method `Zend\PubSubHubbub\Subscriber\Callback::setSubscriptionKey()`. A detailed example is offered later.
`hub.lease_seconds` | `2592000` | The number of seconds for which the Subscriber would like a new subscription to remain valid (i.e. a TTL). Hubs may enforce their own maximum subscription period. All subscriptions should be renewed by re-subscribing before the subscription period ends to ensure continuity of updates. Hubs should additionally attempt to automatically refresh subscriptions before they expire by contacting Subscribers (handled automatically by the `Callback` class).
`hub.mode` | `subscribe` | Value indicating this is a subscription request. Unsubscription requests would use the "unsubscribe" value.
`hub.topic` | `http://www.example.net/rss.xml` | The URI of the Topic (i.e. Atom or RSS feed) which the Subscriber wishes to subscribe to for updates.
`hub.verify` | `sync` or `async` | Indicates to the Hub the preferred mode of verifying subscriptions or unsubscriptions. It is repeated twice in order of preference. Technically this component does not distinguish between the two modes and treats both equally.
`hub.verify_token` | `3065919804abcaa7212ae89.879827871253878386` | A verification token returned to the Subscriber by the Hub when it is confirming a subscription or unsubscription. Offers a measure of reliance that the confirmation request originates from the correct Hub to prevent misuse.

You can modify several of these parameters to indicate a different preference.
For example, you can set a different lease seconds value using
`Zend\Feed\PubSubHubbub\Subscriber::setLeaseSeconds(),` or show a preference for
the `async` verify mode by using `setPreferredVerificationMode(Zend\Feed\PubSubHubbub\PubSubHubbub::VERIFICATION_MODE_ASYNC)`.
However, the Hubs retain the capability to enforce their own preferences, and
for this reason the component is deliberately designed to work across almost any
set of options with minimum end-user configuration required. Conventions are
great when they work!

> ### Verification modes
>
> While Hubs may require the use of a specific verification mode (both are
> supported by `Zend\Feed\PubSubHubbub`), you may indicate a specific preference
> using the `setPreferredVerificationMode()` method. In `sync` (synchronous)
> mode, the Hub attempts to confirm a subscription as soon as it is received,
> and before responding to the subscription request. In `async` (asynchronous)
> mode, the Hub will return a response to the subscription request immediately,
> and its verification request may occur at a later time. Since
> `Zend\Feed\PubSubHubbub` implements the Subscriber verification role as a
> separate callback class and requires the use of a backend storage medium, it
> actually supports both transparently. In terms of end-user performance,
> asynchronous verification is very much preferred to eliminate the impact of a
> poorly performing Hub tying up end-user server resources and connections for
> too long.

Unsubscribing from a Topic follows the exact same pattern as the previous
example, with the exception that we should call `unsubscribeAll()` instead. The
parameters included are identical to a subscription request with the exception
that `hub.mode` is set to "unsubscribe".

By default, a new instance of `Zend\PubSubHubbub\Subscriber` will attempt to use
a database backed storage medium which defaults to using the default zend-db
adapter with a table name of "subscription". It is recommended to set a custom
storage solution where these defaults are not apt either by passing in a new
model supporting the required interface or by passing a new instance of
`Zend\Db\TableGateway\TableGateway` to the default model's constructor to change
the used table name.

### Handling Subscriber Callbacks

Whenever a subscription or unsubscription request is made, the Hub must verify
the request by forwarding a new verification request to the callback URL set in
the subscription or unsubscription parameters. To handle these Hub requests,
which will include all future communications containing Topic (feed) updates,
the callback URL should trigger the execution of an instance of
`Zend\Feed\PubSubHubbub\Subscriber\Callback` to handle the request.

The `Callback` class should be configured to use the same storage medium as the
`Subscriber` class. The bulk of the work is handled internal to these classes.

```php
use Zend\Feed\PubSubHubbub\Model\Subscription;
use Zend\Feed\PubSubHubbub\Subscriber\Callback;

$storage = new Subscription();
$callback = new Callback();
$callback->setStorage($storage);
$callback->handle();
$callback->sendResponse();

/*
 * Check if the callback resulting in the receipt of a feed update.
 * Otherwise it was either a (un)sub verification request or invalid request.
 * Typically we need do nothing other than add feed update handling; the rest
 * is handled internally by the class.
 */
if ($callback->hasFeedUpdate()) {
    $feedString = $callback->getFeedUpdate();
    /*
     * Process the feed update asynchronously to avoid a Hub timeout.
     */
}
```

> #### Query and body parameters
>
> It should be noted that `Zend\Feed\PubSubHubbub\Subscriber\Callback` may
> independently parse any incoming query string and other parameters. This is
> necessary since PHP alters the structure and keys of a query string when it is
> parsed into the `$_GET` or `$_POST` superglobals; for example, all duplicate
> keys are ignored and periods are converted to underscores. Pubsubhubbub
> features both of these in the query strings it generates.

> #### Always delay feed processing
>
> It is essential that developers recognise that Hubs are only concerned with
> sending requests and receiving a response which verifies its receipt. If a
> feed update is received, it should never be processed on the spot since this
> leaves the Hub waiting for a response. Rather, any processing should be
> offloaded to another process or deferred until after a response has been
> returned to the Hub. One symptom of a failure to promptly complete Hub
> requests is that a Hub may continue to attempt delivery of the update or
> verification request leading to duplicated update attempts being processed by
> the Subscriber. This appears problematic, but in reality a Hub may apply a
> timeout of just a few seconds, and if no response is received within that time
> it may disconnect (assuming a delivery failure) and retry later. Note that
> Hubs are expected to distribute vast volumes of updates so their resources are
> stretched; please process feeds asynchronously (e.g. in a separate process or
> a job queue or even a cronjob) as much as possible.

### Setting Up And Using A Callback URL Route

As noted earlier, the `Zend\Feed\PubSubHubbub\Subscriber\Callback` class
receives the combined key associated with any subscription from the Hub via one
of two methods. The technically preferred method is to add this key to the
callback URL employed by the Hub in all future requests using a query string
parameter with the key `xhub.subscription`. However, for historical reasons
(primarily that this was not supported in Pubsubhubbub 0.1, and a late addition
to 0.2 ), it is strongly recommended to use the most compatible means of adding
this key to the callback URL by appending it to the URL's path.

Thus the URL `http://www.example.com/callback?xhub.subscription=key` would become
`http://www.example.com/callback/key`.

Since the query string method is the default in anticipation of a greater level
of future support for the full 0.2/0.3 specification, this requires some
additional work to implement.

The first step is to make the `Zend\Feed\PubSubHubbub\Subscriber\Callback` class
aware of the path contained subscription key. It's manually injected; therefore
it also requires manually defining a route for this purpose. This is achieved by
called the method `Zend\Feed\PubSubHubbub\Subscriber\Callback::setSubscriptionKey()`
with the parameter being the key value available from the router. The example
below demonstrates this using a zend-mvc controller.

```php
use Zend\Feed\PubSubHubbub\Model\Subscription;
use Zend\Feed\PubSubHubbub\Subscriber\Callback;
use Zend\Mvc\Controller\AbstractActionController;

class CallbackController extends AbstractActionController
{

    public function indexAction()
    {
        $storage = new Subscription();
        $callback = new Callback();
        $callback->setStorage($storage);

        /*
         * Inject subscription key parsing from URL path using
         * a parameter from the router.
         */
        $subscriptionKey = $this->params()->fromRoute('subkey');
        $callback->setSubscriptionKey($subscriptionKey);
        $callback->handle();
        $callback->sendResponse();

        /*
         * Check if the callback resulting in the receipt of a feed update.
         * Otherwise it was either a (un)sub verification request or invalid
         * request. Typically we need do nothing other than add feed update
         * handling; the rest is handled internally by the class.
         */
        if ($callback->hasFeedUpdate()) {
            $feedString = $callback->getFeedUpdate();
            /*
             *  Process the feed update asynchronously to avoid a Hub timeout.
             */
        }
    }
}
```

The example below illustrates adding a route mapping the path segment to a route
parameter, using zend-mvc:

```php
use Zend\Mvc\Router\Http\Segment as SegmentRoute;;

// Route defininition for enabling appending of a PuSH Subscription's lookup key
$route = SegmentRoute::factory([
   'route' => '/callback/:subkey',
   'constraints' => [
      'subkey' => '[a-z0-9]+',
   ],
   'defaults' => [
      'controller' => 'application-index',
      'action' => 'index',
   ]
]);
```
