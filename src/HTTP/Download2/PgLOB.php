<?php
/*
 * Copyright (c) 2003-2005, Michael Wallner <mike@iworks.at>.
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright notice, 
 *       this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright 
 *       notice, this list of conditions and the following disclaimer in the 
 *       documentation and/or other materials provided with the distribution.
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

namespace Pluf\HTTP\Download2;

/**
 * HTTP::Download2::PgLOB
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   Download2
 * @author    Michael Wallner <mike@php.net>
 * @copyright 2003-2005 Michael Wallner
 * @license   BSD, revised
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Download2
 */

$GLOBALS['_Download2_PgLOB_Connection'] = null;
stream_register_wrapper('pglob', 'Download2_PgLOB');

/**
 * PgSQL large object stream interface for Download2
 *
 * Usage:
 * <code>
 * require_once 'HTTP/Download2.php';
 * require_once 'HTTP/Download2/PgLOB.php';
 * $db = &DB::connect('pgsql://user:pass@host/db');
 * // or $db = pg_connect(...);
 * $lo = Download2_PgLOB::open($db, 12345);
 * $dl = new Download2;
 * $dl->setResource($lo);
 * $dl->send()
 * </code>
 *
 * @category  HTTP
 * @package   Download2
 * @author    Daniel O'Connor <clockwerx@php.net>
 * @copyright 2012 Daniel O'Connor
 * @license   BSD, revised
 * @link      http://pear.php.net/package/Download2
 */
class PgLOB
{
    /**
     * Set Connection
     *
     * @static
     * @access  public
     * @return  bool
     * @param   mixed   $conn
     */
    function setConnection($conn)
    {
        if (is_a($conn, 'DB_Common')) {
            $conn = $conn->dbh;
        } elseif (  is_a($conn, 'MDB_Common') ||
                    is_a($conn, 'MDB2_Driver_Common')) {
            $conn = $conn->connection;
        }
        if ($isResource = is_resource($conn)) {
            $GLOBALS['_Download2_PgLOB_Connection'] = $conn;
        }
        return $isResource;
    }

    /**
     * Get Connection
     *
     * @static
     * @access  public
     * @return  resource
     */
    function getConnection()
    {
        if (is_resource($GLOBALS['_Download2_PgLOB_Connection'])) {
            return $GLOBALS['_Download2_PgLOB_Connection'];
        }
        return null;
    }

    /**
     * Open
     *
     * @static
     * @access  public
     * @return  resource
     * @param   mixed   $conn
     * @param   int     $loid
     * @param   string  $mode
     */
    function open($conn, $loid, $mode = 'rb')
    {
        Download2_PgLOB::setConnection($conn);
        return fopen('pglob:///'. $loid, $mode);
    }

    /**#@+
     * Stream Interface Implementation
     * @internal
     */
    var $ID = 0;
    var $size = 0;
    var $conn = null;
    var $handle = null;

    function stream_open($path, $mode)
    {
        if (!$this->conn = Download2_PgLOB::getConnection()) {
            return false;
        }
        if (!preg_match('/(\d+)/', $path, $matches)) {
            return false;
        }
        $this->ID = $matches[1];

        if (!pg_query($this->conn, 'BEGIN')) {
            return false;
        }

        $this->handle = pg_lo_open($this->conn, $this->ID, $mode);
        if (!is_resource($this->handle)) {
            return false;
        }

        // fetch size of lob
        pg_lo_seek($this->handle, 0, PGSQL_SEEK_END);
        $this->size = (int) pg_lo_tell($this->handle);
        pg_lo_seek($this->handle, 0, PGSQL_SEEK_SET);

        return true;
    }

    function stream_read($length)
    {
        return pg_lo_read($this->handle, $length);
    }

    function stream_seek($offset, $whence = SEEK_SET)
    {
        return pg_lo_seek($this->handle, $offset, $whence);
    }

    function stream_tell()
    {
        return pg_lo_tell($this->handle);
    }

    function stream_eof()
    {
        return pg_lo_tell($this->handle) >= $this->size;
    }

    function stream_flush()
    {
        return true;
    }

    function stream_stat()
    {
        return array('size' => $this->size, 'ino' => $this->ID);
    }

    function stream_write($data)
    {
        return pg_lo_write($this->handle, $data);
    }

    function stream_close()
    {
        if (pg_lo_close($this->handle)) {
            return pg_query($this->conn, 'COMMIT');
        } else {
            pg_query($this->conn, 'ROLLBACK');
            return false;
        }
    }
    /**#@-*/
}
