CHANGELOG
=========

* 0.2.5 (2012-11-26)

  * Feature: [Stream] Make BufferedSink trigger progress events on the promise (@jsor)
  * Feature: [HttpClient] Use a promise-based API internally
  * Bug fix: [HttpClient] Use DNS resolver correctly

* 0.2.4 (2012-11-18)

  * Feature: [Stream] Added ThroughStream, CompositeStream, ReadableStream and WritableStream
  * Feature: [Stream] Added BufferedSink
  * Feature: [Dns] Change to promise-based API (@jsor)

* 0.2.3 (2012-11-14)

  * Feature: LibEvLoop, integration of `php-libev`
  * Bug fix: Forward drain events from HTTP response (@cs278)
  * Dependency: Updated guzzle deps to `3.0.*`

* 0.2.2 (2012-10-28)

  * Major: Dropped Espresso as a core component now available as `react/espresso` only
  * Feature: DNS executor timeout handling (@arnaud-lb)
  * Feature: DNS retry executor (@arnaud-lb)
  * Feature: HTTP client (@arnaud-lb)

* 0.2.1 (2012-10-14)

  * Feature: Support HTTP 1.1 continue
  * Bug fix: Check for EOF in `Buffer::write()`
  * Bug fix: Make `Espresso\Stack` work with invokables (such as `Espresso\Application`)
  * Minor adjustments to DNS parser

* 0.2.0 (2012-09-10)

  * Feature: DNS resolver

* 0.1.1 (2012-07-12)

  * Bug fix: Testing and functional against PHP >= 5.3.3 and <= 5.3.8

* 0.1.0 (2012-07-11)

  * First tagged release
