<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTTP::Download2
 *
 * PHP versions 5
 *
 * @category  HTTP
 * @package   HTTP_Download2
 * @author    Michael Wallner <mike@php.net>
 * @copyright 2003-2005 Michael Wallner
 * @license   BSD, revised
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/HTTP_Download2
 */

// {{{ includes
/**
 * Requires PEAR
 */
require_once 'PEAR.php';
require_once 'HTTP/Download2/Exception.php';

/**
 * Requires HTTP_Header
 */
require_once 'HTTP/Header.php';
// }}}

// {{{ constants
/**#@+ Use with HTTP_Download2::setContentDisposition() **/
/**
 * Send data as attachment
 */
define('HTTP_DOWNLOAD2_ATTACHMENT', 'attachment');
/**
 * Send data inline
 */
define('HTTP_DOWNLOAD2_INLINE', 'inline');
/**#@-**/

/**#@+ Use with HTTP_Download::sendArchive() **/
/**
 * Send as uncompressed tar archive
 */
define('HTTP_DOWNLOAD2_TAR', 'TAR');
/**
 * Send as gzipped tar archive
 */
define('HTTP_DOWNLOAD2_TGZ', 'TGZ');
/**
 * Send as bzip2 compressed tar archive
 */
define('HTTP_DOWNLOAD2_BZ2', 'BZ2');
/**
 * Send as zip archive
 */
define('HTTP_DOWNLOAD2_ZIP', 'ZIP');
/**#@-**/

/**#@+
 * Error constants
 */
define('HTTP_DOWNLOAD2_E_HEADERS_SENT',          -1);
define('HTTP_DOWNLOAD2_E_NO_EXT_ZLIB',           -2);
define('HTTP_DOWNLOAD2_E_NO_EXT_MMAGIC',         -3);
define('HTTP_DOWNLOAD2_E_INVALID_FILE',          -4);
define('HTTP_DOWNLOAD2_E_INVALID_PARAM',         -5);
define('HTTP_DOWNLOAD2_E_INVALID_RESOURCE',      -6);
define('HTTP_DOWNLOAD2_E_INVALID_REQUEST',       -7);
define('HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE',  -8);
define('HTTP_DOWNLOAD2_E_INVALID_ARCHIVE_TYPE',  -9);
/**#@-**/
// }}}

/**
 * Send HTTP Downloads/Responses.
 *
 * With this package you can handle (hidden) downloads.
 * It supports partial downloads, resuming and sending
 * raw data ie. from database BLOBs.
 *
 * <i>ATTENTION:</i>
 * You shouldn't use this package together with ob_gzhandler or
 * zlib.output_compression enabled in your php.ini, especially
 * if you want to send already gzipped data!
 *
 * @category  HTTP
 * @package   HTTP_Download2
 * @author    Michael Wallner <mike@php.net>
 * @copyright 2003-2005 Michael Wallner
 * @license   BSD, revised
 * @link      http://pear.php.net/package/HTTP_Download2
 */
class HTTP_Download2
{
    // {{{ protected member variables
    /**
     * Path to file for download
     *
     * @see     HTTP_Download2::setFile()
     * @access  protected
     * @var     string
     */
    var $file = '';

    /**
     * Data for download
     *
     * @see     HTTP_Download2::setData()
     * @access  protected
     * @var     string
     */
    var $data = null;

    /**
     * Resource handle for download
     *
     * @see     HTTP_Download2::setResource()
     * @access  protected
     * @var     int
     */
    var $handle = null;

    /**
     * Whether to gzip the download
     *
     * @access  protected
     * @var     bool
     */
    var $gzip = false;

    /**
     * Whether to allow caching of the download on the clients side
     *
     * @access  protected
     * @var     bool
     */
    var $cache = true;

    /**
     * Size of download
     *
     * @access  protected
     * @var     int
     */
    var $size = 0;

    /**
     * Last modified
     *
     * @access  protected
     * @var     int
     */
    var $lastModified = 0;

    /**
     * HTTP headers
     *
     * @access  protected
     * @var     array
     */
    var $headers   = array(
        'Content-Type'  => 'application/x-octetstream',
        'Pragma'        => 'cache',
        'Cache-Control' => 'public, must-revalidate, max-age=0',
        'Accept-Ranges' => 'bytes',
        'X-Sent-By'     => 'PEAR::HTTP::Download2'
    );

    /**
     * HTTP_Header
     *
     * @access  protected
     * @var     object
     */
    var $HTTP = null;

    /**
     * ETag
     *
     * @access  protected
     * @var     string
     */
    var $etag = '';

    /**
     * Buffer Size
     *
     * @access  protected
     * @var     int
     */
    var $bufferSize = 2097152;

    /**
     * Throttle Delay
     *
     * @access  protected
     * @var     float
     */
    var $throttleDelay = 0;

    /**
     * Sent Bytes
     *
     * @access  public
     * @var     int
     */
    var $sentBytes = 0;

    /**
     * Startup error
     *
     * @var    PEAR_Error
     * @access protected
     */
    var $_error = null;
    // }}}

    // {{{ constructor
    /**
     * Constructor
     *
     * Set supplied parameters.
     *
     * @param array $params associative array of parameters
     *  <strong>one of:</strong>
     *  <ul>
     *    <li>'file'               => path to file for download</li>
     *    <li>'data'               => raw data for download</li>
     *    <li>'resource'           => resource handle for download</li>
     *  </ul>
     *  <strong>and any of:</strong>
     *  <ul>
     *    <li>'cache'              => whether to allow cs caching</li>
     *    <li>'gzip'               => whether to gzip the download</li>
     *    <li>'lastmodified'       => unix timestamp</li>
     *    <li>'contenttype'        => content type of download</li>
     *    <li>'contentdisposition' => content disposition</li>
     *    <li>'buffersize'         => amount of bytes to buffer</li>
     *    <li>'throttledelay'      => amount of secs to sleep</li>
     *    <li>'cachecontrol'       => cache privacy and validity</li>
     *  </ul>
     *
     * 'Content-Disposition' is not HTTP compliant, but most browsers
     * follow this header, so it was borrowed from MIME standard.
     *
     * It looks like this:
     * "Content-Disposition: attachment; filename=example.tgz".
     *
     * @see HTTP_Download2::setContentDisposition()
     */
    public function __construct($params = array())
    {
        $this->HTTP = new HTTP_Header;
        $this->_error = $this->setParams($params);
    }
    // }}}

    // {{{ public methods
    /**
     * Set parameters
     *
     * Set supplied parameters through its accessor methods.
     *
     * @param array $params associative array of parameters
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     * @see     HTTP_Download2::HTTP_Download2()
     */
    function setParams($params)
    {
        foreach ((array) $params as $param => $value) {
            $method = 'set'. $param;

            if (!method_exists($this, $method)) {
                throw new HTTP_Download2_Exception(
                    "Method '$method' doesn't exist.",
                    HTTP_DOWNLOAD2_E_INVALID_PARAM
                );
            }

            call_user_func_array(array(&$this, $method), (array) $value);
        }
        return true;
    }

    /**
     * Set path to file for download
     *
     * The Last-Modified header will be set to files filemtime(), actually.
     * Returns PEAR_Error (HTTP_DOWNLOAD2_E_INVALID_FILE) if file doesn't exist.
     * Sends HTTP 404 or 403 status if $send_error is set to true.
     *
     * @param string $file       path to file for download
     * @param bool   $send_error whether to send HTTP/404 or 403 if
     *                              the file wasn't found or is not readable
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function setFile($file, $send_error = true)
    {
        $file = realpath($file);
        if (!is_file($file)) {
            if ($send_error) {
                $this->HTTP->sendStatusCode(404);
            }
            throw new HTTP_Download2_Exception(
                "File '$file' not found.",
                HTTP_DOWNLOAD2_E_INVALID_FILE
            );
        }
        if (!is_readable($file)) {
            if ($send_error) {
                $this->HTTP->sendStatusCode(403);
            }
            throw new HTTP_Download2_Exception(
                "Cannot read file '$file'.",
                HTTP_DOWNLOAD2_E_INVALID_FILE
            );
        }
        $this->setLastModified(filemtime($file));
        $this->file = $file;
        $this->size = filesize($file);
        return true;
    }

    /**
     * Set data for download
     *
     * Set $data to null if you want to unset this.
     *
     * @param string $data raw data to send
     *
     * @access  public
     * @return  void
     */
    function setData($data = null)
    {
        $this->data = $data;
        $this->size = strlen($data);
    }

    /**
     * Set resource for download
     *
     * The resource handle supplied will be closed after sending the download.
     * Returns a PEAR_Error (HTTP_DOWNLOAD2_E_INVALID_RESOURCE) if $handle
     * is no valid resource. Set $handle to null if you want to unset this.
     *
     * @param int $handle resource handle
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function setResource($handle = null)
    {
        if (!isset($handle)) {
            $this->handle = null;
            $this->size = 0;
            return true;
        }

        if (is_resource($handle)) {
            $this->handle = $handle;
            $filestats    = fstat($handle);
            $this->size   = isset($filestats['size']) ? $filestats['size']
                                                      : -1;
            return true;
        }

        throw new HTTP_Download2_Exception(
            "Handle '$handle' is no valid resource.",
            HTTP_DOWNLOAD2_E_INVALID_RESOURCE
        );
    }

    /**
     * Whether to gzip the download
     *
     * Returns a PEAR_Error (HTTP_DOWNLOAD2_E_NO_EXT_ZLIB)
     * if ext/zlib is not available/loadable.
     *
     * @param bool $gzip whether to gzip the download
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function setGzip($gzip = false)
    {
        if ($gzip && !extension_loaded('zlib')) {
            throw new HTTP_Download2_Exception(
                'GZIP compression (ext/zlib) not available.',
                HTTP_DOWNLOAD2_E_NO_EXT_ZLIB
            );
        }
        $this->gzip = (bool) $gzip;
        return true;
    }

    /**
     * Whether to allow caching
     *
     * If set to true (default) we'll send some headers that are commonly
     * used for caching purposes like ETag, Cache-Control and Last-Modified.
     *
     * If caching is disabled, we'll send the download no matter if it
     * would actually be cached at the client side.
     *
     * @param bool $cache whether to allow caching
     *
     * @access  public
     * @return  void
     */
    function setCache($cache = true)
    {
        $this->cache = (bool) $cache;
    }

    /**
     * Whether to allow proxies to cache
     *
     * If set to 'private' proxies shouldn't cache the response.
     * This setting defaults to 'public' and affects only cached responses.
     *
     * @param string $cache  private or public
     * @param int    $maxage maximum age of the client cache entry
     *
     * @access  public
     * @return  bool
     */
    function setCacheControl($cache = 'public', $maxage = 0)
    {
        switch ($cache = strToLower($cache))
        {
        case 'private':
        case 'public':
            $this->headers['Cache-Control'] 
                = $cache . ', must-revalidate, max-age='. abs($maxage);
            return true;
            break;
        }
        return false;
    }

    /**
     * Set ETag
     *
     * Sets a user-defined ETag for cache-validation.  The ETag is usually
     * generated by HTTP_Download2 through its payload information.
     *
     * @param string $etag Entity tag used for strong cache validation.
     *
     * @access  public
     * @return  void
     */
    function setETag($etag = null)
    {
        $this->etag = (string) $etag;
    }

    /**
     * Set Size of Buffer
     *
     * The amount of bytes specified as buffer size is the maximum amount
     * of data read at once from resources or files.  The default size is 2M
     * (2097152 bytes).  Be aware that if you enable gzip compression and
     * you set a very low buffer size that the actual file size may grow
     * due to added gzip headers for each sent chunk of the specified size.
     *
     * Returns PEAR_Error (HTTP_DOWNLOAD2_E_INVALID_PARAM) if $size is not
     * greater than 0 bytes.
     *
     * @param int $bytes Amount of bytes to use as buffer.
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function setBufferSize($bytes = 2097152)
    {
        if (0 >= $bytes) {
            throw new HTTP_Download2_Exception(
                'Buffer size must be greater than 0 bytes ('. $bytes .' given)',
                HTTP_DOWNLOAD2_E_INVALID_PARAM
            );
        }
        $this->bufferSize = abs($bytes);
        return true;
    }

    /**
     * Set Throttle Delay
     *
     * Set the amount of seconds to sleep after each chunck that has been
     * sent.  One can implement some sort of throttle through adjusting the
     * buffer size and the throttle delay.  With the following settings
     * HTTP_Download2 will sleep a second after each 25 K of data sent.
     *
     * <code>
     *  Array(
     *      'throttledelay' => 1,
     *      'buffersize'    => 1024 * 25,
     *  )
     * </code>
     *
     * Just be aware that if gzipp'ing is enabled, decreasing the chunk size
     * too much leads to proportionally increased network traffic due to added
     * gzip header and bottom bytes around each chunk.
     *
     * @param float $seconds Amount of seconds to sleep after each
     *                       chunk that has been sent.
     *
     * @access  public
     * @return  void
     */
    function setThrottleDelay($seconds = 0)
    {
        $this->throttleDelay = abs($seconds) * 1000;
    }

    /**
     * Set "Last-Modified"
     *
     * This is usually determined by filemtime() in HTTP_Download2::setFile()
     * If you set raw data for download with HTTP_Download2::setData() and you
     * want do send an appropiate "Last-Modified" header, you should call this
     * method.
     *
     * @param int $last_modified unix timestamp
     *
     * @access  public
     * @return  void
     */
    function setLastModified($last_modified)
    {
        $this->lastModified = $this->headers['Last-Modified'] = (int) $last_modified;
    }

    /**
     * Set Content-Disposition header
     *
     * <b>Example:</b>
     * <code>
     * $HTTP_Download2->setContentDisposition(
     *   HTTP_DOWNLOAD2_ATTACHMENT,
     *   'download.tgz'
     * );
     * </code>
     *
     * @param string $disposition whether to send the download
     *                            inline or as attachment
     * @param string $file_name   the filename to display in
     *                            the browser's download window
     *
     * @access  public
     * @see HTTP_Download2::HTTP_Download2
     * @return  void
     */
    function setContentDisposition(
        $disposition    = HTTP_DOWNLOAD2_ATTACHMENT,
        $file_name      = null
    ) {
        $cd = $disposition;
        if (isset($file_name)) {
            $cd .= '; filename="' . $file_name . '"';
        } elseif ($this->file) {
            $cd .= '; filename="' . basename($this->file) . '"';
        }
        $this->headers['Content-Disposition'] = $cd;
    }

    /**
     * Set content type of the download
     *
     * Default content type of the download will be 'application/x-octetstream'.
     * Returns PEAR_Error (HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE) if
     * $content_type doesn't seem to be valid.
     *
     * @param string $content_type content type of file for download
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function setContentType($content_type = 'application/x-octetstream')
    {
        if (!preg_match('/^[a-z]+\w*\/[a-z0-9]+[\w.;=\+ -]*$/', $content_type)) {
            throw new HTTP_Download2_Exception(
                "Invalid content type '$content_type' supplied.",
                HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE
            );
        }
        $this->headers['Content-Type'] = $content_type;
        return true;
    }

    /**
     * Guess content type of file
     *
     * First we try to use PEAR::MIME_Type, if installed, to detect the content
     * type, else we check if ext/mime_magic is loaded and properly configured.
     *
     * Returns PEAR_Error if:
     *      o if PEAR::MIME_Type failed to detect a proper content type
     *        (HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE)
     *      o ext/magic.mime is not installed, or not properly configured
     *        (HTTP_DOWNLOAD2_E_NO_EXT_MMAGIC)
     *      o mime_content_type() couldn't guess content type or returned
     *        a content type considered to be bogus by setContentType()
     *        (HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE)
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function guessContentType()
    {
        if (class_exists('MIME_Type') || @include_once 'MIME/Type.php') {
            if (PEAR::isError($mime_type = MIME_Type::autoDetect($this->file))) {
                throw new HTTP_Download2_Exception(
                    $mime_type->getMessage(),
                    HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE
                );
            }
            return $this->setContentType($mime_type);
        }
        if (!function_exists('mime_content_type')) {
            throw new HTTP_Download2_Exception(
                'This feature requires ext/mime_magic!',
                HTTP_DOWNLOAD2_E_NO_EXT_MMAGIC
            );
        }
        if (!is_file(ini_get('mime_magic.magicfile'))) {
            throw new HTTP_Download2_Exception(
                'ext/mime_magic is loaded but not properly configured!',
                HTTP_DOWNLOAD2_E_NO_EXT_MMAGIC
            );
        }
        if (!$content_type = @mime_content_type($this->file)) {
            throw new HTTP_Download2_Exception(
                'Couldn\'t guess content type with mime_content_type().',
                HTTP_DOWNLOAD2_E_INVALID_CONTENT_TYPE
            );
        }
        return $this->setContentType($content_type);
    }

    /**
     * Send
     *
     * Returns PEAR_Error if:
     *   o HTTP headers were already sent (HTTP_DOWNLOAD2_E_HEADERS_SENT)
     *   o HTTP Range was invalid (HTTP_DOWNLOAD2_E_INVALID_REQUEST)
     *
     * @param bool $autoSetContentDisposition Whether to set the
     *             Content-Disposition header if it isn't already.
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function send($autoSetContentDisposition = true)
    {
        if (headers_sent()) {
            throw new HTTP_Download2_Exception(
                'Headers already sent.',
                HTTP_DOWNLOAD2_E_HEADERS_SENT
            );
        }

        if (!ini_get('safe_mode')) {
            @set_time_limit(0);
        }

        if ($autoSetContentDisposition 
            && !isset($this->headers['Content-Disposition'])
        ) {
            $this->setContentDisposition();
        }

        if ($this->cache) {
            $this->headers['ETag'] = $this->generateETag();
            if ($this->isCached()) {
                $this->HTTP->sendStatusCode(304);
                $this->sendHeaders();
                return true;
            }
        } else {
            unset($this->headers['Last-Modified']);
        }

        if (ob_get_level()) {
            while (@ob_end_clean()) {
            }
        }

        if ($this->gzip) {
            @ob_start('ob_gzhandler');
        } else {
            ob_start();
        }

        $this->sentBytes = 0;

        // Known content length?
        $end = ($this->size >= 0) ? max($this->size - 1, 0) : '*';

        if ($end != '*' && $this->isRangeRequest()) {
             $chunks = $this->getChunks();
            if (empty($chunks)) {
                $this->HTTP->sendStatusCode(200);
                $chunks = array(array(0, $end));

            } elseif (PEAR::isError($chunks)) {
                ob_end_clean();
                $this->HTTP->sendStatusCode(416);
                return $chunks;

            } else {
                $this->HTTP->sendStatusCode(206);
            }
        } else {
            $this->HTTP->sendStatusCode(200);
            $chunks = array(array(0, $end));
            if (!$this->gzip && count(ob_list_handlers()) < 2 && $end != '*') {
                $this->headers['Content-Length'] = $this->size;
            }
        }

        $this->sendChunks($chunks);

        ob_end_flush();
        flush();
        return true;
    }

    /**
     * Static send
     *
     * @param array $params associative array of parameters
     * @param bool  $guess  whether HTTP_Download2::guessContentType()
     *                               should be called
     *
     * @see     HTTP_Download2::HTTP_Download2()
     * @see     HTTP_Download2::send()
     * @static
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function staticSend($params, $guess = false)
    {
        $d = new HTTP_Download2();
        $e = $d->setParams($params);
        if ($guess) {
            $d->guessContentType();
        }
        return $d->send();
    }

    /**
     * Send a bunch of files or directories as an archive
     *
     * Example:
     * <code>
     *  require_once 'HTTP/Download2.php';
     *  HTTP_Download2::sendArchive(
     *      'myArchive.tgz',
     *      '/var/ftp/pub/mike',
     *      HTTP_DOWNLOAD2_TGZ,
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
     * @deprecated  use HTTP_Download2_Archive::send()
     * @static
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function sendArchive(
        $name,
        $files,
        $type       = HTTP_DOWNLOAD2_TGZ,
        $add_path   = '',
        $strip_path = ''
    ) {
        include_once 'HTTP/Download2/Archive.php';
        return HTTP_Download2_Archive::send(
            $name, $files, $type,
            $add_path, $strip_path
        );
    }
    // }}}

    // {{{ protected methods
    /**
     * Generate ETag
     *
     * @access  protected
     * @return  string
     */
    function generateETag()
    {
        if (!$this->etag) {
            if (isset($this->data)) {
                $md5 = md5($this->data);
            } else {
                $mtime = time();
                $ino   = 0;
                $size  = mt_rand();

                if (is_resource($this->handle)) {
                    $content = fstat($this->handle);
                } else {
                    stat($this->file);
                }

                extract($content);
                $md5 = md5($mtime .'='. $ino .'='. $size);
            }
            $this->etag = '"' . $md5 . '-' . crc32($md5) . '"';
        }
        return $this->etag;
    }

    /**
     * Send multiple chunks
     *
     * @param array $chunks Chunks to send
     *
     * @access  protected
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function sendChunks($chunks)
    {
        if (count($chunks) == 1) {
            return $this->sendChunk(current($chunks));
        }

        $bound = uniqid('HTTP_DOWNLOAD-', true);
        $cType = $this->headers['Content-Type'];
        $this->headers['Content-Type'] = 'multipart/byteranges; boundary=' . $bound;
        $this->sendHeaders();
        foreach ($chunks as $chunk) {
            $this->sendChunk($chunk, $cType, $bound);
        }

        return true;
    }

    /**
     * Send chunk of data
     *
     * @param array  $chunk start and end offset of the chunk to send
     * @param string $cType actual content type
     * @param string $bound boundary for multipart/byteranges
     *
     * @access  protected
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     */
    function sendChunk($chunk, $cType = null, $bound = null)
    {
        list($offset, $lastbyte) = $chunk;
        $length = ($lastbyte - $offset) + 1;

        $range = $offset . '-' . $lastbyte . '/'
                 . (($this->size >= 0) ? $this->size : '*');

        if (isset($cType, $bound)) {
            echo    "\r\n--$bound\r\n",
                    "Content-Type: $cType\r\n",
                    "Content-Range: bytes $range\r\n\r\n";
        } else {
            if ($lastbyte != '*' && $this->isRangeRequest()) {
                $this->headers['Content-Length'] = $length;
                $this->headers['Content-Range'] = 'bytes '. $range;
            }
            $this->sendHeaders();
        }

        if (isset($this->data)) {
            while (($length -= $this->bufferSize) > 0) {
                $this->flush(substr($this->data, $offset, $this->bufferSize));
                $this->throttleDelay and $this->sleep();
                $offset += $this->bufferSize;
            }
            if ($length) {
                $this->flush(
                    substr($this->data, $offset, $this->bufferSize + $length)
                );
            }
        } else {
            if (!is_resource($this->handle)) {
                $this->handle = fopen($this->file, 'rb');
            }
            fseek($this->handle, $offset);
            if ($lastbyte == '*') {
                while (!feof($this->handle)) {
                    $this->flush(fread($this->handle, $this->bufferSize));
                    $this->throttleDelay and $this->sleep();
                }
            } else {
                while (($length -= $this->bufferSize) > 0) {
                    $this->flush(fread($this->handle, $this->bufferSize));
                    $this->throttleDelay and $this->sleep();
                }

                if ($length) {
                    $this->flush(fread($this->handle, $this->bufferSize + $length));
                }
            }
        }

        return true;
    }

    /**
     * Get chunks to send
     *
     * @access  protected
     * @return  array Chunk list or PEAR_Error on invalid range request
     * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35
     */
    function getChunks()
    {
        $end = ($this->size >= 0) ? max($this->size - 1, 0) : '*';

        // Trying to handle ranges on content with unknown length is too
        // big of a mess (impossible to determine if a range is valid)
        if ($end == '*') {
            return array();
        }

        $ranges = $this->getRanges();
        if (empty($ranges)) {
            return array();
        }

        $parts = array();
        $satisfiable = false;
        foreach (explode(',', $ranges) as $chunk) {
            list($o, $e) = explode('-', trim($chunk));

            // If the last-byte-pos value is present, it MUST be greater than
            // or equal to the first-byte-pos in that byte-range-spec, or the
            // byte- range-spec is syntactically invalid. The recipient of a
            // byte-range- set that includes one or more syntactically invalid
            // byte-range-spec values MUST ignore the header field that
            // includes that byte-range- set.
            if ($e !== '' && $o !== '' && $e < $o) {
                return array();
            }

            // If the last-byte-pos value is absent, or if the value is
            // greater than or equal to the current length of the entity-body,
            // last-byte-pos is taken to be equal to one less than the current
            // length of the entity- body in bytes.
            if ($e === '' || $e > $end) {
                $e = $end;
            }

            // A suffix-byte-range-spec is used to specify the suffix of the
            // entity-body, of a length given by the suffix-length value. (That
            // is, this form specifies the last N bytes of an entity-body.) If
            // the entity is shorter than the specified suffix-length, the
            // entire entity-body is used.
            if ($o === '') {
                // If a syntactically valid byte-range-set includes at least
                // one suffix-byte-range-spec with a non-zero suffix-length,
                // then the byte-range-set is satisfiable.
                $satisfiable |= ($e != 0);

                $o = max($this->size - $e, 0);
                $e = $end;

            } elseif ($o <= $end) {
                // If a syntactically valid byte-range-set includes at least
                // one byte- range-spec whose first-byte-pos is less than the
                // current length of the entity-body, then the byte-range-set
                // is satisfiable.
                $satisfiable = true;
            } else {
                continue;
            }

            $parts[] = array($o, $e);
        }

        // If the byte-range-set is unsatisfiable, the server SHOULD return a
        // response with a status of 416 (Requested range not satisfiable).
        if (!$satisfiable) {
            throw new HTTP_Download2_Exception(
                'Error processing range request',
                HTTP_DOWNLOAD2_E_INVALID_REQUEST
            );
        }
        //$this->sortChunks($parts);
        return $this->mergeChunks($parts);
    }

    /**
     * Sorts the ranges to be in ascending order
     *
     * @param array &$chunks ranges to sort
     *
     * @return void
     * @access protected
     * @static
     * @author Philippe Jausions <jausions@php.net>
     */
    function sortChunks(&$chunks)
    {
        $sortFunc = create_function(
            '$a,$b',
            'if ($a[0] == $b[0]) {
                if ($a[1] == $b[1]) {
                    return 0;
                }
                return (($a[1] != "*" && $a[1] < $b[1])
                        || $b[1] == "*") ? -1 : 1;
             }

             return ($a[0] < $b[0]) ? -1 : 1;'
        );

        usort($chunks, $sortFunc);
    }

    /**
     * Merges consecutive chunks to avoid overlaps
     *
     * @param array $chunks Ranges to merge
     *
     * @return array merged ranges
     * @access protected
     * @static
     * @author Philippe Jausions <jausions@php.net>
     */
    function mergeChunks($chunks)
    {
        do {
            $count = count($chunks);
            $merged = array(current($chunks));
            $j = 0;
            for ($i = 1; $i < count($chunks); ++$i) {
                list($o, $e) = $chunks[$i];
                if ($merged[$j][1] == '*') {
                    if ($merged[$j][0] <= $o) {
                        continue;
                    } elseif ($e == '*' || $merged[$j][0] <= $e) {
                        $merged[$j][0] = min($merged[$j][0], $o);
                    } else {
                        $merged[++$j] = $chunks[$i];
                    }
                } elseif ($merged[$j][0] <= $o && $o <= $merged[$j][1]) {
                    $merged[$j][1] = ($e == '*') ? '*' : max($e, $merged[$j][1]);
                } elseif ($merged[$j][0] <= $e && $e <= $merged[$j][1]) {
                    $merged[$j][0] = min($o, $merged[$j][0]);
                } else {
                    $merged[++$j] = $chunks[$i];
                }
            }
            if ($count == count($merged)) {
                break;
            }
            $chunks = $merged;
        } while (true);
        return $merged;
    }

    /**
     * Check if range is requested
     *
     * @access  protected
     * @return  bool
     */
    function isRangeRequest()
    {
        if (!isset($_SERVER['HTTP_RANGE']) || !count($this->getRanges())) {
            return false;
        }
        return $this->isValidRange();
    }

    /**
     * Get range request
     *
     * @access  protected
     * @return  array
     */
    function getRanges()
    {
        $match = preg_match(
            '/^bytes=((\d+-|\d+-\d+|-\d+)(, ?(\d+-|\d+-\d+|-\d+))*)$/',
            @$_SERVER['HTTP_RANGE'], $matches
        );

        if ($match) {
            return $matches[1];
        }
        return array();
    }

    /**
     * Check if entity is cached
     *
     * @access  protected
     * @return  bool
     */
    function isCached()
    {
        return (
            (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
            && $this->lastModified == strtotime(
                current(
                    $a = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'])
                )
            )) 
            ||
            (isset($_SERVER['HTTP_IF_NONE_MATCH']) 
            && $this->compareAsterisk('HTTP_IF_NONE_MATCH', $this->etag))
        );
    }

    /**
     * Check if entity hasn't changed
     *
     * @access  protected
     * @return  bool
     */
    function isValidRange()
    {
        if (isset($_SERVER['HTTP_IF_MATCH']) 
            && !$this->compareAsterisk('HTTP_IF_MATCH', $this->etag)
        ) {
            return false;
        }

        if (isset($_SERVER['HTTP_IF_RANGE']) 
            && $_SERVER['HTTP_IF_RANGE'] !== $this->etag 
            &&  strtotime($_SERVER['HTTP_IF_RANGE']) !== $this->lastModified
        ) {
            return false;
        }

        if (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
            $lm = current($a = explode(';', $_SERVER['HTTP_IF_UNMODIFIED_SINCE']));
            if (strtotime($lm) !== $this->lastModified) {
                return false;
            }
        }
        if (isset($_SERVER['HTTP_UNLESS_MODIFIED_SINCE'])) {
            $lm = current($a = explode(';', $_SERVER['HTTP_UNLESS_MODIFIED_SINCE']));
            if (strtotime($lm) !== $this->lastModified) {
                return false;
            }
        }
        return true;
    }

    /**
     * Compare against an asterisk or check for equality
     *
     * @param string $svar    key for the $_SERVER array
     * @param string $compare string to compare
     *
     * @access  protected
     * @return  bool
     */
    function compareAsterisk($svar, $compare)
    {
        foreach (array_map('trim', explode(',', $_SERVER[$svar])) as $request) {
            if ($request === '*' || $request === $compare) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send HTTP headers
     *
     * @access  protected
     * @return  void
     */
    function sendHeaders()
    {
        foreach ($this->headers as $header => $value) {
            $this->HTTP->setHeader($header, $value);
        }
        $this->HTTP->sendHeaders();
        /* NSAPI won't output anything if we did this */
        if (strncasecmp(PHP_SAPI, 'nsapi', 5)) {
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
    }

    /**
     * Flush
     *
     * @param string $data Data
     *
     * @access  protected
     * @return  void
     */
    function flush($data = '')
    {
        if ($dlen = strlen($data)) {
            $this->sentBytes += $dlen;
            echo $data;
        }
        ob_flush();
        flush();
    }

    /**
     * Sleep
     *
     * @access  protected
     * @return  void
     */
    function sleep()
    {
        if (OS_WINDOWS) {
            com_message_pump($this->throttleDelay);
        } else {
            usleep($this->throttleDelay * 1000);
        }
    }

}
