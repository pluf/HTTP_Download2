<?php
/*
 * Copyright (c) 2003-2005, Michael Wallner <mike@iworks.at>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace Pluf\HTTP\Header2;

/**
 * HTTP::Header::Cache
 *
 * PHP versions 5
 *
 * @category HTTP
 * @package Header2
 * @author Wolfram Kriesing <wk@visionp.de>
 * @author Michael Wallner <mike@php.net>
 * @copyright 2003-2005 The Authors
 * @license BSD, revised
 * @version CVS: $Id$
 * @link http://pear.php.net/package/Header2
 */

/**
 * Header2_Cache
 *
 * This package provides methods to easier handle caching of HTTP pages. That
 * means that the pages can be cached at the client (user agent or browser) and
 * your application only needs to send "hey client you already have the pages".
 *
 * Which is done by sending the HTTP-Status "304 Not Modified", so that your
 * application load and the network traffic can be reduced, since you only need
 * to send the complete page once. This is really an advantage e.g. for
 * generated style sheets, or simply pages that do only change rarely.
 *
 * Usage:
 * <code>
 * require_once 'HTTP/Header/Cache.php';
 * $httpCache = new Header2_Cache(4, 'weeks');
 * $httpCache->sendHeaders();
 * // your code goes here
 * </code>
 *
 * @category HTTP
 * @package Header2
 * @author Wolfram Kriesing <wk@visionp.de>
 * @author Michael Wallner <mike@php.net>
 * @license BSD, revised
 * @version $Revision$
 * @link http://pear.php.net/package/Header2
 */
class Cache extends \Pluf\HTTP\Header2
{

    /**
     * Constructor
     *
     * Set the amount of time to cache.
     *
     * @param int $expires
     *            Amount of time for cache to last
     * @param string $unit
     *            The unit of time to keep cache
     */
    public function __construct($expires = 0, $unit = 'seconds')
    {
        parent::__construct();
        $this->setHeader('Pragma', 'cache');
        $this->setHeader('Last-Modified', $this->getCacheStart());
        $this->setHeader('Cache-Control', 'private, must-revalidate, max-age=0');

        if ($expires) {
            if (! $this->isOlderThan($expires, $unit)) {
                $this->exitCached();
            }
            $this->setHeader('Last-Modified', time());
        }
    }

    /**
     * Get Cache Start
     *
     * Returns the unix timestamp of the If-Modified-Since HTTP header or the
     * current time if the header was not sent by the client.
     *
     * @return int unix timestamp
     */
    public function getCacheStart()
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ! $this->isPost()) {
            $data = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
            return strtotime(current($data));
        }
        return time();
    }

    /**
     * Is Older Than
     *
     * You can call it like this:
     * <code>
     * $httpCache->isOlderThan(1, 'day');
     * $httpCache->isOlderThan(47, 'days');
     *
     * $httpCache->isOlderThan(1, 'week');
     * $httpCache->isOlderThan(3, 'weeks');
     *
     * $httpCache->isOlderThan(1, 'hour');
     * $httpCache->isOlderThan(5, 'hours');
     *
     * $httpCache->isOlderThan(1, 'minute');
     * $httpCache->isOlderThan(15, 'minutes');
     *
     * $httpCache->isOlderThan(1, 'second');
     * $httpCache->isOlderThan(15);
     * </code>
     *
     * If you specify something greater than "weeks" as time untit, it just
     * works approximatly, because a month is taken to consist of 4.3 weeks.
     *
     * @param int $time
     *            The amount of time.
     * @param string $unit
     *            The unit of the time amount - (year[s], month[s],
     *            week[s], day[s], hour[s], minute[s], second[s]).
     *            
     * @return bool Returns true if requested page is older than specified.
     */
    public function isOlderThan($time = 0, $unit = 'seconds')
    {
        if (! isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $this->isPost()) {
            return true;
        }
        if (! $time) {
            return false;
        }

        switch (strtolower($unit)) {
            case 'year':
            case 'years':
                $time *= 12;
            case 'month':
            case 'months':
                $time *= 4.3;
            case 'week':
            case 'weeks':
                $time *= 7;
            case 'day':
            case 'days':
                $time *= 24;
            case 'hour':
            case 'hours':
                $time *= 60;
            case 'minute':
            case 'minutes':
                $time *= 60;
        }

        return (time() - $this->getCacheStart()) > $time;
    }

    /**
     * Is Cached
     *
     * Check whether we can consider to be cached on the client side.
     *
     * @param int $lastModified
     *            Unix timestamp of last modification.
     *            
     * @return bool Whether the page/resource is considered to be cached.
     */
    public function isCached($lastModified = 0)
    {
        if ($this->isPost()) {
            return false;
        }
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ! $lastModified) {
            return true;
        }
        if (! $seconds = time() - $lastModified) {
            return false;
        }
        return ! $this->isOlderThan($seconds);
    }

    /**
     * Is Post
     *
     * Check if request method is "POST".
     *
     * @return bool
     */
    public function isPost()
    {
        return isset($_SERVER['REQUEST_METHOD']) and 'POST' == $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Exit If Cached
     *
     * Exit with "HTTP 304 Not Modified" if we consider to be cached.
     *
     * @param int $lastModified
     *            Unix timestamp of last modification.
     *            
     * @return void
     */
    public function exitIfCached($lastModified = 0)
    {
        if ($this->isCached($lastModified)) {
            $this->exitCached();
        }
    }

    /**
     * Exit Cached
     *
     * Exit with "HTTP 304 Not Modified".
     *
     * @return void
     */
    public function exitCached()
    {
        $this->sendHeaders();
        $this->sendStatusCode(304);
        exit();
    }

    /**
     * Set Last Modified
     *
     * @param int $lastModified
     *            The unix timestamp of last modification.
     *            
     * @return void
     */
    public function setLastModified($lastModified = null)
    {
        $this->setHeader('Last-Modified', $lastModified);
    }
}