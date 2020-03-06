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
 * HTTP::Download2::Archive
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

/**
 * Requires Download2
 */
require_once 'System.php';
use Pluf\HTTP\Download2;
use System;
use PEAR;
use Archive_Tar;
//use Archive_Zip;

/**
 * Requires System
 */

/**
 * Archive
 *
 * Helper class for sending Archives.
 *
 * @category  HTTP
 * @package   Download2
 * @author    Daniel O'Connor <clockwerx@php.net>
 * @copyright 2012 Daniel O'Connor
 * @license   BSD, revised
 * @link      http://pear.php.net/package/Download2
 */
class Archive
{
    /**
     * Send a bunch of files or directories as an archive
     *
     * Example:
     * <code>
     *  require_once 'HTTP/Download2/Archive.php';
     *  Archive::send(
     *      'myArchive.tgz',
     *      '/var/ftp/pub/mike',
     *      HTTP_DOWNLOAD2_BZ2,
     *      '',
     *      '/var/ftp/pub'
     *  );
     * </code>
     *
     * @param string $name       name the sent archive should have
     * @param mixed  $files      files/directories
     * @param string $type       archive type
     * @param string $add_path   path that should be prepended to the files
     * @param string $strip_path path that should be stripped from the files
     *
     * @see         Archive_Tar::createModify()
     * @static
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function send(
        $name, $files, $type = HTTP_DOWNLOAD2_TGZ,
        $add_path = '', $strip_path = ''
    ) {
        $tmp = System::mktemp();

        switch ($type = strToUpper($type))
        {
        case HTTP_DOWNLOAD2_TAR:
            include_once 'Archive/Tar.php';
            $arc = new Archive_Tar($tmp);
            $content_type = 'x-tar';
            break;

        case HTTP_DOWNLOAD2_TGZ:
            include_once 'Archive/Tar.php';
            $arc = new Archive_Tar($tmp, 'gz');
            $content_type = 'x-gzip';
            break;

        case HTTP_DOWNLOAD2_BZ2:
            include_once 'Archive/Tar.php';
            $arc = new Archive_Tar($tmp, 'bz2');
            $content_type = 'x-bzip2';
            break;

//         case HTTP_DOWNLOAD2_ZIP:
//             include_once 'Archive/Zip.php';
//             $arc = new Archive_Zip($tmp);
//             $content_type = 'x-zip';
//             break;

        default:
            throw new Exception(
                'Archive type not supported: ' . $type,
                HTTP_DOWNLOAD2_E_INVALID_ARCHIVE_TYPE
            );
        }

        if ($type == HTTP_DOWNLOAD2_ZIP) {
            $options = array(   'add_path' => $add_path,
                                'remove_path' => $strip_path);
            if (!$arc->create($files, $options)) {
                throw new Exception('Archive creation failed.');
            }
        } else {
            if (!$e = $arc->createModify($files, $add_path, $strip_path)) {
                throw new Exception('Archive creation failed.');
            }
            if (PEAR::isError($e)) {
                return $e;
            }
        }
        unset($arc);

        $dl = new Download2(array('file' => $tmp));
        $dl->setContentType('application/' . $content_type);
        $dl->setContentDisposition(HTTP_DOWNLOAD2_ATTACHMENT, $name);
        return $dl->send();
    }
}
