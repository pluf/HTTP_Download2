<?php
/**
 * TestCase for HTTP_Header2
 *
 * $Id$
 */
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'HTTP/Header2.php';
require_once 'HTTP/Request2.php';
require_once 'Net/URL2.php';

class HTTP_Header2Test extends PHPUnit_Framework_TestCase
{
    function testHTTP_Header2()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->getHttpVersion() == 1.1 || $h->getHttpVersion() == 1.0);
        unset($h);
    }

    function testsetHttpVersion()
    {
        $h = new HTTP_Header2;
        $this->assertFalse($h->setHttpVersion('foo'));
        $this->assertTrue($h->setHttpVersion(1.0));
        $this->assertTrue($h->setHttpVersion(1.1));
        $this->assertTrue($h->setHttpVersion(1));
        $this->assertTrue($h->setHttpVersion(1.111111111));
        $this->assertTrue($h->setHttpVersion('1'));
        $this->assertTrue($h->setHttpVersion('1.1'));
        $this->assertTrue($h->setHttpVersion('1.0000000000000'));
        $this->assertFalse($h->setHttpVersion(2));
        unset($h);
    }

    function testgetHttpVersion()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->getHttpVersion() == 1.0 || $h->getHttpVersion() == 1.1, ' http version is 1.0 or 1.1');
        $h->setHttpVersion(1);
        $this->assertEquals(1, $h->getHttpVersion());
        $h->setHttpVersion(1.1);
        $this->assertEquals(1.1, $h->getHttpVersion());
        $h->setHttpVersion(2);
        $this->assertEquals(1.1, $h->getHttpVersion());
        unset($h);
    }

    function testsetHeader()
    {
        $h = new HTTP_Header2;
        $this->assertFalse($h->setHeader(null), 'set null');
        $this->assertFalse($h->setHeader(''), ' set empty string');
        $this->assertFalse($h->setHeader(0), 'set 0');
        $this->assertTrue($h->setHeader('X-Foo', 'bla'), 'set X-Foo = bla');
        $this->assertFalse($h->setHeader('X-Array', array('foo')), 'set array');
        $this->assertFalse($h->setHeader('X-Object', new StdClass), 'set object');
        unset($h);
    }

    function testgetHeader()
    {
        $h = new HTTP_Header2;
        $this->assertEquals('no-cache', $h->getHeader('Pragma'));
        $this->assertEquals('no-cache', $h->getHeader('pRaGmA'));
        $h->setHeader('X-Foo', 'foo');
        $this->assertEquals('foo', $h->getHeader('X-Foo'));
        $this->assertEquals('foo', $h->getHeader('x-FoO'));
        $this->assertTrue(is_array($h->getHeader()), 'test for array');
        $this->assertFalse($h->getHeader('Non-Existant'), 'test unset header');
        unset($h);
    }

    function testsendHeaders()
    {
        $url = new Net_URL2(HTTP_HEADER2_TEST_URL);
        $url->setQueryVariable('X-Foo', 'blablubb');

        $r = new HTTP_Request2($url);
        $r->setMethod(HTTP_Request2::METHOD_GET);
        $response = $r->send();
        $this->assertEquals('blablubb', $response->getHeader('x-foo'));
        unset($h, $r);
    }

    function testsendStatusCode()
    {
        $url = new Net_URL2(HTTP_HEADER2_TEST_URL);
        $r = new HTTP_Request2($url);

        $r->setMethod(HTTP_Request2::METHOD_GET);
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'test for response code 200');

        $url->setQueryVariable('status', '500');

        $response = $r->send();
        $this->assertEquals(500, $response->getStatus(), 'test for response code 500');
        unset($h, $r);
    }

    function testdateToTimestamp()
    {
        $h = new HTTP_Header2;
        $this->assertEquals(strtotime($d = $h->date()), $h->dateToTimestamp($d));
        unset($h);
    }

    function testredirect()
    {
        $url = new Net_URL2(HTTP_HEADER2_TEST_URL);
        $url->setQueryVariable('redirect', 'response.php?abc=123');

        $r = new HTTP_Request2($url);
        $r->setConfig('follow_redirects', false);
        $r->setMethod(HTTP_Request2::METHOD_GET);
        $response = $r->send();
        $this->assertEquals(302, $response->getStatus(), 'test for response code 302');
        $this->assertContains('response.php', $response->getHeader('location'));
        unset($h, $r);
    }

    function testgetStatusType()
    {
        $h = new HTTP_Header2;
        $this->assertEquals(HTTP_Header2::STATUS_INFORMATIONAL, $h->getStatusType(101));
        $this->assertEquals(HTTP_Header2::STATUS_SUCCESSFUL, $h->getStatusType(206));
        $this->assertEquals(HTTP_Header2::STATUS_REDIRECT, $h->getStatusType(301));
        $this->assertEquals(HTTP_Header2::STATUS_CLIENT_ERROR, $h->getStatusType(404));
        $this->assertEquals(HTTP_Header2::STATUS_SERVER_ERROR, $h->getStatusType(500));
        $this->assertFalse($h->getStatusType(8));
        unset($h);
    }

    function testgetStatusText()
    {
        $h = new HTTP_Header2;
        $this->assertEquals(HTTP_Header2::STATUS_100, '100 '. $h->getStatusText(100));
        $this->assertEquals(HTTP_Header2::STATUS_200, '200 '. $h->getStatusText(200));
        $this->assertEquals(HTTP_Header2::STATUS_300, '300 '. $h->getStatusText(300));
        $this->assertEquals(HTTP_Header2::STATUS_302, '302 '. $h->getStatusText(302));
        $this->assertEquals(HTTP_Header2::STATUS_401, '401 '. $h->getStatusText(401));
        $this->assertEquals(HTTP_Header2::STATUS_400, '400 '. $h->getStatusText(400));
        $this->assertEquals(HTTP_Header2::STATUS_500, '500 '. $h->getStatusText(500));
        $this->assertEquals(HTTP_Header2::STATUS_102, '102 '. $h->getStatusText(102));
        $this->assertEquals(HTTP_Header2::STATUS_404, '404 '. $h->getStatusText(404));
        $this->assertFalse($h->getStatusText(0));
        $this->assertFalse($h->getStatusText(800));
        unset($h);
    }

    function testisInformational()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->isInformational(100));
        $this->assertTrue($h->isInformational(101));
        $this->assertTrue($h->isInformational(102));
        $this->assertFalse($h->isInformational(404));
        unset($h);
    }

    function testisSuccessful()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->isSuccessful(200));
        $this->assertTrue($h->isSuccessful(201));
        $this->assertTrue($h->isSuccessful(202));
        $this->assertFalse($h->isSuccessful(404));
        unset($h);
    }

    function testisRedirect()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->isRedirect(300));
        $this->assertTrue($h->isRedirect(301));
        $this->assertTrue($h->isRedirect(302));
        $this->assertFalse($h->isRedirect(404));
        unset($h);
    }

    function testisClientError()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->isClientError(400));
        $this->assertTrue($h->isClientError(401));
        $this->assertTrue($h->isClientError(404));
        $this->assertFalse($h->isClientError(500));
        unset($h);
    }

    function testisServerError()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->isServerError(500));
        $this->assertTrue($h->isServerError(501));
        $this->assertTrue($h->isServerError(502));
        $this->assertFalse($h->isServerError(404));
        unset($h);
    }

    function testisError()
    {
        $h = new HTTP_Header2;
        $this->assertTrue($h->isError(500));
        $this->assertTrue($h->isError(501));
        $this->assertTrue($h->isError(502));
        $this->assertFalse($h->isError(206));
        $this->assertTrue($h->isError(400));
        $this->assertTrue($h->isError(401));
        $this->assertTrue($h->isError(404));
        $this->assertFalse($h->isError(100));
        unset($h);
    }
}

