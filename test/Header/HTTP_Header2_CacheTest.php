<?php
/**
 * Test Case for HTTP_Header2_Cache
 *
 * Id$
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'HTTP/Header2/Cache.php';
require_once 'HTTP/Request2.php';

class HTTP_Header2_CacheTest extends PHPUnit_Framework_TestCase
{

    function testgetCacheStart()
    {
        $c = new HTTP_Header2_Cache;
        $this->assertEquals(time(), $c->getCacheStart());
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $c->date(strtotime('yesterday'));
        $this->assertEquals($_SERVER['HTTP_IF_MODIFIED_SINCE'], $c->date($c->getCacheStart()));
        unset($c, $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    }

    function testisOlderThan()
    {
        $c = new HTTP_Header2_Cache;
        $this->assertTrue($c->isOlderThan(1, 'second'));
        $this->assertTrue($c->isOlderThan(1, 'hour'));
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $c->date(time() - 3);
        $this->assertTrue($c->isOlderThan(1, 'second'));
        unset($c, $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    }

    function testisCached()
    {
        $c = new HTTP_Header2_Cache;
        $this->assertFalse($c->isCached(), 'no last modified');
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $c->date(strtotime('yesterday'));
        $this->assertTrue($c->isCached(), 'last modified header');
        $this->assertFalse($c->isCached(time()), 'last modified header (yesterday) and param (now)');
        $this->assertTrue($c->isCached(strtotime('last year')), 'last modified header (yesterday) and param (last year)');
        unset($c, $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    }

    function testexitIfCached()
    {
        $http = new HTTP2();

        $r = new HTTP_Request2(HTTP_HEADER2_CACHE_TEST_URL);
        $r->setMethod(HTTP_Request2::METHOD_GET);
        $r->setHeader('If-Modified-Since', $http->date());
        $response = $r->send();
        $this->assertEquals(304, $response->getStatus(), 'HTTP 304 Not Modified');
        $r->setHeader('If-Modified-Since', $http->date(strtotime('yesterday')));
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
        unset($r);
    }

    function testget()
    {
        $r = new HTTP_Request2(HTTP_HEADER2_CACHE_TEST_URL);
        $r->setMethod(HTTP_Request2::METHOD_GET);
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok (simple plain GET)');
        $r->setHeader('If-Modified-Since', $response->getHeader('last-modified'));
        sleep(3);
        $response = $r->send();
        $this->assertEquals(304, $response->getStatus(), 'HTTP 304 Not Modified (GET with If-Modified-Since set to Last-Modified of prior request');
        unset($r);
    }

    function testhead()
    {
        $r = new HTTP_Request2(HTTP_HEADER2_CACHE_TEST_URL);
        $r->setMethod(HTTP_Request2::METHOD_HEAD);
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok (simple plain GET)');
        $r->setHeader('If-Modified-Since', $response->getHeader('last-modified'));
        sleep(3);
        $response = $r->send();
        $this->assertEquals(304, $response->getStatus(), 'HTTP 304 Not Modified (GET with If-Modified-Since set to Last-Modified of prior request');
        unset($r);
    }

    function testpost()
    {
        $http = new HTTP2();

        $r = new HTTP_Request2(HTTP_HEADER2_CACHE_TEST_URL);
        $r->setMethod(HTTP_Request2::METHOD_GET);
        $response = $r->send();
        $lm = $response->getHeader('last-modified');
        $r->setMethod(HTTP_Request2::METHOD_POST);
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok (POST without If-Modified-Since)');
        $r->setHeader('If-Modified-Since', $http->date(strtotime('yesterday')));
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok (POST with If-Modified-Since == yesterday)');
        $r->setHeader('If-Modified-Since', $http->date(time() - 3));
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok (POST with If-Modified-Since == now)');
        $r->setHeader('If-Modified-Since', $http->date($lm));
        sleep(3);
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok (POST with If-Modified-Since == Last-Modified)');
        $this->assertEquals($http->date(), $response->getHeader('last-modified'), 'POST time() == Last-Modified');
        unset($r);
    }
}
