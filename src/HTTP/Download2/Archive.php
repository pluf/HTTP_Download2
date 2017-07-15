<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTTP::Download2::Archive
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP_Download2
 * @author    Michael Wallner <mike@php.net>
 * @copyright 2003-2005 Michael Wallner
 * @license   BSD, revised
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/HTTP_Download2
 */

/**
 * Requires HTTP_Download2
 */
require_once 'HTTP/Download2.php';
require_once 'HTTP/Download2/Exception.php';

/**
 * Requires System
 */
require_once 'System.php';

/**
 * HTTP_Download2_Archive
 *
 * Helper class for sending Archives.
 *
 * @category  HTTP
 * @package   HTTP_Download2
 * @author    Daniel O'Connor <clockwerx@php.net>
 * @copyright 2012 Daniel O'Connor
 * @license   BSD, revised
 * @link      http://pear.php.net/package/HTTP_Download2
 */
class HTTP_Download2_Archive
{
    /**
     * Send a bunch of files or directories as an archive
     *
     * Example:
     * <code>
     *  require_once 'HTTP/Download2/Archive.php';
     *  HTTP_Download2_Archive::send(
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

        case HTTP_DOWNLOAD2_ZIP:
            include_once 'Archive/Zip.php';
            $arc = new Archive_Zip($tmp);
            $content_type = 'x-zip';
            break;

        default:
            throw new HTTP_Download2_Exception(
                'Archive type not supported: ' . $type,
                HTTP_DOWNLOAD2_E_INVALID_ARCHIVE_TYPE
            );
        }

        if ($type == HTTP_DOWNLOAD2_ZIP) {
            $options = array(   'add_path' => $add_path,
                                'remove_path' => $strip_path);
            if (!$arc->create($files, $options)) {
                throw new HTTP_Download2_Exception('Archive creation failed.');
            }
        } else {
            if (!$e = $arc->createModify($files, $add_path, $strip_path)) {
                throw new HTTP_Download2_Exception('Archive creation failed.');
            }
            if (PEAR::isError($e)) {
                return $e;
            }
        }
        unset($arc);

        $dl = new HTTP_Download2(array('file' => $tmp));
        $dl->setContentType('application/' . $content_type);
        $dl->setContentDisposition(HTTP_DOWNLOAD2_ATTACHMENT, $name);
        return $dl->send();
    }
}
