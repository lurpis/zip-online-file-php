<?php

/**
 * @author lurrpis
 * @date 15/8/29 下午9:50
 * @blog http://lurrpis.com
 */
class Zip
{

    /**
     * Temp root path
     *
     * @var string
     */
    public $path = '/example/temp';

    /**
     * Pakeage name
     *
     * @var string
     */
    public $pkgname;

    /**
     * Temp file save path
     *
     * @var array
     */
    public $path_save = array();

    /**
     * Choose whether or not you need to be removed temp file
     *
     * @var bool
     */
    public $delete_temp_file = TRUE;

    /**
     * Zip data in string form
     *
     * @var string
     */
    public $zipdata = '';

    /**
     * Zip data for a directory in string form
     *
     * @var string
     */
    public $directory = '';

    /**
     * Number of files/folder in zip file
     *
     * @var int
     */
    public $entries = 0;

    /**
     * Number of files in zip
     *
     * @var int
     */
    public $file_num = 0;

    /**
     * relative offset of local header
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Reference to time at init
     *
     * @var int
     */
    public $now;

    /**
     * The level of compression
     *
     * Ranges from 0 to 9, with 9 being the highest level.
     *
     * @var    int
     */
    public $compression_level = 2;

    /**
     * Initialize zip compression class
     *
     * @return    void
     */
    public function __construct($pkgname = 'download')
    {
        $this->now = time();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . $this->path;
        $this->pkgname = $pkgname;
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 755, true)) {
                echo 'Create ' . $this->path . 'temp path filed, please create';
                exit;
            }
        } else if (!is_writable($this->path)) {
            echo $this->path . 'Directory is not to write, please modify permissions';
            exit;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Create zip file
     *
     * array data File array
     * string url required，Download file where file need package
     * string path required，Path in zip
     * string name optional，File name in zip
     * @param $data
     * Create zip，$data = array();
     *
     * example
     * $data = array(
     *  array('url'=>'https://pingxx.com/resources/images/newicon/request.png',path=>'/',name=>'logo_one'),
     *  array('url'=>'https://pingxx.com/resources/images/newicon/success.png',path=>'/images/dir',name='logo_two')
     *  array('url'=>'https://pingxx.com/resources/images/newicon/success.png',path=>'/images')
     * );
     */
    public function zip($data)
    {
        $this->clear_data();
        $this->path = $this->path . '/' . $this->pkgname;
        if ($this->_get_save($data)) {
            $this->read_dir($this->path, FALSE);
            $this->delete_temp_file && $this->_delete_temp_dir();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Use download where need download zip
     *
     * @param $pkgname
     */
    public function download()
    {
        $this->create_download($this->pkgname . '.zip');
    }

    // --------------------------------------------------------------------

    /**
     * Download temp files
     *
     * @param $data
     * @return bool
     */
    private function _get_save($data)
    {
        foreach ($data as $key => $value) {
            if (substr($value['path'], -1) != '/') {
                $value['path'] .= '/';
            }
            if (isset($value['name'])) {
                $suffix = explode('/', $value['url']);
                $suffix = explode('.', end($suffix));
                $suffix = '.' . end($suffix);
                $result[] = $this->_http_copy($value['url'], $this->path . $value['path'] . $value['name'] . $suffix);
            } else {
                $name = explode('/', $value['url']);
                $name = end($name);
                $result[] = $this->_http_copy($value['url'], $this->path . $value['path'] . $name);
            }

        }
        if (is_array($result)) {
            $this->path_save = array_unique($result);
            return true;
        }
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Delete temp files
     */
    private function _delete_temp_dir()
    {
        foreach ($this->path_save as $key => $value) {
            if (!unlink($value)) {
                echo $value . 'File deletion error';
                exit;
            }
            $name = explode('/', $value);
            $dir[] = str_replace(end($name), '', $value);
        }
        foreach (array_unique($dir) as $key => $value) {
            if ($value != $this->path . '/') {
                if (!rmdir($value)) {
                    echo $value . 'Directory deletion error';
                    exit;
                }
            }
        }
        if (!rmdir($this->path . '/')) {
            echo $value . 'File deletion error';
            exit;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Copy file from HTTP
     *
     * @param $url
     * @param string $file
     * @param int $timeout
     * @return bool|mixed|string
     */
    private function _http_copy($url, $file = "", $timeout = 60)
    {
        $file = empty($file) ? pathinfo($url, PATHINFO_BASENAME) : $file;
        $dir = pathinfo($file, PATHINFO_DIRNAME);
        !is_dir($dir) && mkdir($dir, 0755, true);
        $url = str_replace(" ", "%20", $url);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $temp = curl_exec($ch);
            if (file_put_contents($file, $temp) && !curl_error($ch)) {
                return $file;
            } else {
                return false;
            }
        } else {
            $opts = array(
                "http" => array(
                    "method" => "GET",
                    "header" => "",
                    "timeout" => $timeout)
            );
            $context = stream_context_create($opts);
            if (copy($url, $file, $context)) {
                return $file;
            } else {
                return false;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add Directory
     *
     * Lets you add a virtual directory into which you can place files.
     *
     * @param    mixed $directory the directory name. Can be string or array
     * @return    void
     */
    public function add_dir($directory)
    {
        foreach ((array)$directory as $dir) {
            if (!preg_match('|.+/$|', $dir)) {
                $dir .= '/';
            }

            $dir_time = $this->_get_mod_time($dir);
            $this->_add_dir($dir, $dir_time['file_mtime'], $dir_time['file_mdate']);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get file/directory modification time
     *
     * If this is a newly created file/dir, we will set the time to 'now'
     *
     * @param    string $dir path to file
     * @return    array    filemtime/filemdate
     */
    protected function _get_mod_time($dir)
    {
        // filemtime() may return false, but raises an error for non-existing files
        $date = file_exists($dir) ? getdate(filemtime($dir)) : getdate($this->now);

        return array(
            'file_mtime' => ($date['hours'] << 11) + ($date['minutes'] << 5) + $date['seconds'] / 2,
            'file_mdate' => (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday']
        );
    }

    // --------------------------------------------------------------------

    /**
     * Add Directory
     *
     * @param    string $dir the directory name
     * @param    int $file_mtime
     * @param    int $file_mdate
     * @return    void
     */
    protected function _add_dir($dir, $file_mtime, $file_mdate)
    {
        $dir = str_replace('\\', '/', $dir);

        $this->zipdata .=
            "\x50\x4b\x03\x04\x0a\x00\x00\x00\x00\x00"
            . pack('v', $file_mtime)
            . pack('v', $file_mdate)
            . pack('V', 0) // crc32
            . pack('V', 0) // compressed filesize
            . pack('V', 0) // uncompressed filesize
            . pack('v', strlen($dir)) // length of pathname
            . pack('v', 0) // extra field length
            . $dir
            // below is "data descriptor" segment
            . pack('V', 0) // crc32
            . pack('V', 0) // compressed filesize
            . pack('V', 0); // uncompressed filesize

        $this->directory .=
            "\x50\x4b\x01\x02\x00\x00\x0a\x00\x00\x00\x00\x00"
            . pack('v', $file_mtime)
            . pack('v', $file_mdate)
            . pack('V', 0) // crc32
            . pack('V', 0) // compressed filesize
            . pack('V', 0) // uncompressed filesize
            . pack('v', strlen($dir)) // length of pathname
            . pack('v', 0) // extra field length
            . pack('v', 0) // file comment length
            . pack('v', 0) // disk number start
            . pack('v', 0) // internal file attributes
            . pack('V', 16) // external file attributes - 'directory' bit set
            . pack('V', $this->offset) // relative offset of local header
            . $dir;

        $this->offset = strlen($this->zipdata);
        $this->entries++;
    }

    // --------------------------------------------------------------------

    /**
     * Add Data to Zip
     *
     * Lets you add files to the archive. If the path is included
     * in the filename it will be placed within a directory. Make
     * sure you use add_dir() first to create the folder.
     *
     * @param    mixed $filepath A single filepath or an array of file => data pairs
     * @param    string $data Single file contents
     * @return    void
     */
    public function add_data($filepath, $data = NULL)
    {
        if (is_array($filepath)) {
            foreach ($filepath as $path => $data) {
                $file_data = $this->_get_mod_time($path);
                $this->_add_data($path, $data, $file_data['file_mtime'], $file_data['file_mdate']);
            }
        } else {
            $file_data = $this->_get_mod_time($filepath);
            $this->_add_data($filepath, $data, $file_data['file_mtime'], $file_data['file_mdate']);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add Data to Zip
     *
     * @param    string $filepath the file name/path
     * @param    string $data the data to be encoded
     * @param    int $file_mtime
     * @param    int $file_mdate
     * @return    void
     */
    protected function _add_data($filepath, $data, $file_mtime, $file_mdate)
    {
        $filepath = str_replace('\\', '/', $filepath);

        $uncompressed_size = strlen($data);
        $crc32 = crc32($data);
        $gzdata = substr(gzcompress($data, $this->compression_level), 2, -4);
        $compressed_size = strlen($gzdata);

        $this->zipdata .=
            "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00"
            . pack('v', $file_mtime)
            . pack('v', $file_mdate)
            . pack('V', $crc32)
            . pack('V', $compressed_size)
            . pack('V', $uncompressed_size)
            . pack('v', strlen($filepath)) // length of filename
            . pack('v', 0) // extra field length
            . $filepath
            . $gzdata; // "file data" segment

        $this->directory .=
            "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00"
            . pack('v', $file_mtime)
            . pack('v', $file_mdate)
            . pack('V', $crc32)
            . pack('V', $compressed_size)
            . pack('V', $uncompressed_size)
            . pack('v', strlen($filepath)) // length of filename
            . pack('v', 0) // extra field length
            . pack('v', 0) // file comment length
            . pack('v', 0) // disk number start
            . pack('v', 0) // internal file attributes
            . pack('V', 32) // external file attributes - 'archive' bit set
            . pack('V', $this->offset) // relative offset of local header
            . $filepath;

        $this->offset = strlen($this->zipdata);
        $this->entries++;
        $this->file_num++;
    }

    // --------------------------------------------------------------------

    /**
     * Read the contents of a file and add it to the zip
     *
     * @param    string $path
     * @param    bool $archive_filepath
     * @return    bool
     */
    public function read_file($path, $archive_filepath = FALSE)
    {
        if (file_exists($path) && FALSE !== ($data = file_get_contents($path))) {
            if (is_string($archive_filepath)) {
                $name = str_replace('\\', '/', $archive_filepath);
            } else {
                $name = str_replace('\\', '/', $path);

                if ($archive_filepath === FALSE) {
                    $name = preg_replace('|.*/(.+)|', '\\1', $name);
                }
            }

            $this->add_data($name, $data);
            return TRUE;
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * Read a directory and add it to the zip.
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a zip based on it. Whatever directory structure
     * is in the original file path will be recreated in the zip file.
     *
     * @param    string $path path to source directory
     * @param    bool $preserve_filepath
     * @param    string $root_path
     * @return    bool
     */
    public function read_dir($path, $preserve_filepath = TRUE, $root_path = NULL)
    {
        $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        if (!$fp = @opendir($path)) {
            return FALSE;
        }

        // Set the original directory root for child dir's to use as relative
        if ($root_path === NULL) {
            $root_path = dirname($path) . DIRECTORY_SEPARATOR;
        }

        while (FALSE !== ($file = readdir($fp))) {
            if ($file[0] === '.') {
                continue;
            }

            if (is_dir($path . $file)) {
                $this->read_dir($path . $file . DIRECTORY_SEPARATOR, $preserve_filepath, $root_path);
            } elseif (FALSE !== ($data = file_get_contents($path . $file))) {
                $name = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
                if ($preserve_filepath === FALSE) {
                    $name = str_replace($root_path, '', $name);
                }

                $this->add_data($name . $file, $data);
            }
        }

        closedir($fp);
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Get the Zip file
     *
     * @return    string    (binary encoded)
     */
    public function get_zip()
    {
        // Is there any data to return?
        if ($this->entries === 0) {
            return FALSE;
        }

        return $this->zipdata
        . $this->directory . "\x50\x4b\x05\x06\x00\x00\x00\x00"
        . pack('v', $this->entries) // total # of entries "on this disk"
        . pack('v', $this->entries) // total # of entries overall
        . pack('V', strlen($this->directory)) // size of central dir
        . pack('V', strlen($this->zipdata)) // offset to start of central dir
        . "\x00\x00"; // .zip file comment length
    }

    // --------------------------------------------------------------------

    /**
     * Write File to the specified directory
     *
     * Lets you write a file
     *
     * @param    string $filepath the file name
     * @return    bool
     */
    public function archive($filepath)
    {
        if (!($fp = @fopen($filepath, 'w+b'))) {
            return FALSE;
        }

        flock($fp, LOCK_EX);

        for ($result = $written = 0, $data = $this->get_zip(), $length = strlen($data); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($data, $written))) === FALSE) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }

    // --------------------------------------------------------------------

    /**
     * create_download
     *
     * @param    string $filename the file name
     * @return    void
     */
    public function create_download($filename = 'download.zip')
    {
        if (!preg_match('|.+?\.zip$|', $filename)) {
            $filename .= '.zip';
        }

        $get_zip = $this->get_zip();
        $zip_content =& $get_zip;

        $this->force_download($filename, $zip_content);
    }

    // --------------------------------------------------------------------

    /**
     * Initialize Data
     *
     * Lets you clear current zip data. Useful if you need to create
     * multiple zips with different data.
     *
     * @return    CI_Zip
     */
    public function clear_data()
    {
        $this->zipdata = '';
        $this->directory = '';
        $this->entries = 0;
        $this->file_num = 0;
        $this->offset = 0;
        return $this;
    }

    public function force_download($filename = '', $data = '', $set_mime = FALSE)
    {
        if ($filename === '' OR $data === '') {
            return;
        } elseif ($data === NULL) {
            if (@is_file($filename) && ($filesize = @filesize($filename)) !== FALSE) {
                $filepath = $filename;
                $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
                $filename = end($filename);
            } else {
                return;
            }
        } else {
            $filesize = strlen($data);
        }

        // Set the default MIME type to send
        $mime = 'application/octet-stream';

        $x = explode('.', $filename);
        $extension = end($x);

        if ($set_mime === TRUE) {
            if (count($x) === 1 OR $extension === '') {
                /* If we're going to detect the MIME type,
                 * we'll need a file extension.
                 */
                return;
            }

            // Load the mime types
            $mimes =& get_mimes();

            // Only change the default MIME if we can find one
            if (isset($mimes[$extension])) {
                $mime = is_array($mimes[$extension]) ? $mimes[$extension][0] : $mimes[$extension];
            }
        }

        /* It was reported that browsers on Android 2.1 (and possibly older as well)
         * need to have the filename extension upper-cased in order to be able to
         * download it.
         *
         * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
         */
        if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT'])) {
            $x[count($x) - 1] = strtoupper($extension);
            $filename = implode('.', $x);
        }

        if ($data === NULL && ($fp = @fopen($filepath, 'rb')) === FALSE) {
            return;
        }

        // Clean output buffer
        if (ob_get_level() !== 0 && @ob_end_clean() === FALSE) {
            @ob_clean();
        }

        // Generate the server headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $filesize);

        // Internet Explorer-specific headers
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }

        header('Pragma: no-cache');

        // If we have raw data - just dump it
        if ($data !== NULL) {
            exit($data);
        }

        // Flush 1MB chunks of data
        while (!feof($fp) && ($data = fread($fp, 1048576)) !== FALSE) {
            echo $data;
        }

        fclose($fp);
        exit;
    }

}
