<?php

require_once dirname(__FILE__) . '/helper.inc';

require_once 'HTTP/Download2.php';
require_once 'HTTP/Request.php';

class HTTP_Download2Test extends PHPUnit_Framework_TestCase {

    function setUp()
    {
        $this->testScript = 'http://local/www/mike/pear/HTTP_Download2/tests/send.php';
    }

    function testHTTP_Download2()
    {
        $this->assertTrue(is_a($h = new HTTP_Download2, 'HTTP_Download2'));
        $this->assertTrue(is_a($h->HTTP, 'HTTP_Header'));
        unset($h);
    }

    function testsetFile()
    {
        $h = new HTTP_Download2;
        $h->setFile(dirname(__FILE__) . '/data.txt');
        $this->assertEquals(realpath(dirname(__FILE__) . '/data.txt'), $h->file, '$h->file == "data.txt');
        try {
            $h->setFile('nonexistant', false); // '$h->setFile("nonexistant")'

            $this->fail("Expected a HTTP_Download2_Exception");
        } catch (HTTP_Download2_Exception $e) {
        }
    }

    function testsetData()
    {
        $h = new HTTP_Download2;
        $this->assertTrue(null === $h->setData('foo'), 'null === $h->setData("foo")');
        $this->assertEquals('foo', $h->data, '$h->data == "foo"');
        unset($h);
    }

    function testsetResource()
    {
        $h = new HTTP_Download2;
        $h->setResource($f = fopen(dirname(__FILE__) . '/data.txt', 'r'));

        $this->assertEquals($f, $h->handle, '$h->handle == $f');
        fclose($f); $f = -1;

        try {
            $h->setResource($f); //, '$h->setResource($f = -1)');

            $this->fail("Expected a HTTP_Download2_Exception");
        } catch (HTTP_Download2_Exception $e) {
        }
    }

    function testsetGzip()
    {
        $h = new HTTP_Download2;
        $h->setGzip(false);
        $this->assertFalse($h->gzip, '$h->gzip');
        if (extension_loaded('zlib')) {
            $h->setGzip(true);

            $this->assertTrue($h->gzip, '$h->gzip');
        } else {
            try {
                $h->setGzip(true);// '$h->setGzip(true) with ext/zlib');

                $this->fail("Expected a HTTP_Download2_Exception");
            } catch (HTTP_Download2_Exception $e) {
            }

            $this->assertFalse($h->gzip, '$h->gzip');
        }
        unset($h);
    }

    function testsetContentType()
    {
        $h = new HTTP_Download2;
        $h->setContentType('text/html;charset=iso-8859-1');

        try {
            $h->setContentType('##++***!§§§§?°°^^}][{'); //, '$h->setContentType("some weird characters")');

            $this->fail("Expected a HTTP_Download2_Exception");
        } catch (HTTP_Download2_Exception $e) {
        }

        $this->assertEquals('text/html;charset=iso-8859-1', $h->headers['Content-Type'], '$h->headers["Content-Type"] == "text/html;charset=iso-8859-1"');
        unset($h);
    }

    function testguessContentType()
    {
        $h = new HTTP_Download2(array('file' => dirname(__FILE__) . '/data.txt'));

        try {
            $h->guessContentType();

        } catch (HTTP_Download2_Exception $e) {
            if ($e->getCode() == HTTP_DOWNLOAD2_E_NO_EXT_MMAGIC) {
                $this->markTestSkipped($e->getMessage());
            }

            $this->fail((string)$e);
        }
    }

    function _send($op)
    {
        if (!@file_get_contents($this->testScript)) {
            $this->markTestSkipped($this->testScript . " is not available");
        }
        $complete = str_repeat('1234567890',10);

        $r = new HTTP_Request2($this->testScript);
        foreach (array('file', 'resource', 'data') as $what) {
            $r->reset($this->testScript);

            // without range
            $r->addPostParameter('op', $op);
            $r->addPostParameter('what', $what);
            $r->addPostParameter('buffersize', 33);
            $response = $r->send();
            $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
            $this->assertEquals($complete, $response->getBody(), $what);

            // range 1-5
            $r->setHeader('Range', 'bytes=1-5');
            $response = $r->send();
            $this->assertEquals(206, $response->getStatus(), 'HTTP 206 Partial Content');
            $this->assertEquals('23456', $response->getBody(), $what);

            // range -5
            $r->setHeader('Range', 'bytes=-5');
            $response = $r->send();
            $this->assertEquals(206, $response->getStatus(), 'HTTP 206 Partial Content');
            $this->assertEquals('67890', $response->getBody(), $what);

            // range 95-
            $r->setHeader('Range', 'bytes=95-');
            $response = $r->send();
            $this->assertEquals(206, $response->getStatus(), 'HTTP 206 Partial Content');
            $this->assertEquals('67890', $response->getBody(), $what);
            $this->assertTrue(preg_match('/^bytes 95-\d+/', $response->getHeader('content-range')), 'bytes keyword in Content-Range header');

            // multiple non-overlapping ranges
            $r->setHeader('Range', 'bytes=2-23,45-51, 24-44');
            $response = $r->send();
            $this->assertEquals(206, $response->getStatus(), 'HTTP 206 Partial Content');
            $this->assertTrue(preg_match('/^multipart\/byteranges; boundary=HTTP_DOWNLOAD-[a-f0-9.]{23}$/', $response->getHeader('content-type')), 'Content-Type header: multipart/byteranges');
            $this->assertTrue(preg_match('/Content-Range: bytes 2-23/', $response->getBody()), 'bytes keyword in Content-Range header');

            // multiple overlapping ranges
            $r->setHeader('Range', 'bytes=2-23,45-51,22-46');
            $response = $r->send();
            $this->assertEquals(206, $response->getStatus(), 'HTTP 206 Partial Content');
            $this->assertEquals('bytes 2-51/100', $response->getHeader('content-range'), 'bytes keyword in Content-Range header');

            // Invalid range #1 (54-51)
            $r->setHeader('Range', 'bytes=2-23,54-51,22-46');
            $response = $r->send();
            $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
            $this->assertEquals('100', $response->getHeader('content-length'), 'full content');
            $this->assertEquals($complete, $response->getBody(), $what);

            // Invalid range #2 (maformed range)
            $r->setHeader('Range', 'bytes=2-23 24-');
            $response = $r->send();
            $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
            $this->assertEquals('100', $response->getHeader('content-length'), 'full content');
            $this->assertEquals($complete, $response->getBody(), $what);

            // Invalid range #3 (451-510)
            $r->setHeader('Range', 'bytes=451-510, -0');
            $response = $r->send();
            $this->assertEquals(416, $response->getStatus(), 'HTTP 416 Unsatisfiable range');
        }

        // Stream
        $what = 'stream';
        $r->reset($this->testScript);

        // without range
        $r->addPostParameter('op', $op);
        $r->addPostParameter('what', $what);
        $r->addPostParameter('buffersize', 33);
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
        $this->assertEquals($complete, $response->getBody(), $what);
        $this->assertFalse($response->getHeader('content-range'), 'No range');

        // range 1-5
        $r->setHeader('Range', 'bytes=1-5');
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
        $this->assertEquals($complete, $response->getBody(), $what);
        $this->assertFalse($response->getHeader('content-length'), 'Length unknown');
        $this->assertFalse($response->getHeader('content-range'), 'No range');

        // range -5
        $r->setHeader('Range', 'bytes=-5');
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
        $this->assertEquals($complete, $response->getBody(), $what);
        $this->assertFalse($response->getHeader('content-length'), 'Length unknown');
        $this->assertFalse($response->getHeader('content-range'), 'No range');

        // range 95-
        $r->setHeader('Range', 'bytes=95-');
        $response = $r->send();
        $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
        $this->assertEquals($complete, $response->getBody(), $what);
        $this->assertFalse($response->getHeader('content-length'), 'Length unknown');
        $this->assertFalse($response->getHeader('content-range'), 'No range');

        unset($r);
    }

    function testsend()
    {
        $this->_send('send');
    }

    function teststaticSend()
    {
        $this->_send('static');
    }

    function testsendArchive()
    {
        if (!@file_get_contents($this->testScript)) {
            $this->markTestSkipped($this->testScript . " is not available");
        }

        $r = new HTTP_Request2($this->testScript);
        foreach (array('tar', 'tgz', 'zip', 'bz2') as $type) {
            $r->addPostParameter('type', $type);
            $r->addPostParameter('op', 'arch');

            $r->addPostParameter('what', 'data.txt');
            $response = $r->send();
            $this->assertEquals(200, $response->getStatus(), 'HTTP 200 Ok');
            $this->assertTrue($response->getHeader('content-length') > 0, 'Content-Length > 0');
            $this->assertTrue(preg_match('/application\/x-(tar|gzip|bzip2|zip)/', $t = $response->getHeader('content-type')), 'Reasonable Content-Type for '. $type .' (actual: '. $t .')');
        }
        unset($r);
    }

}
