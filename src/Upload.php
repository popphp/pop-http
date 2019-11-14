<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http;

/**
 * HTTP upload class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
 */
class Upload
{

    /**
     * File is too big by the user-defined max size
     */
    const UPLOAD_ERR_USER_SIZE = 9;

    /**
     * File is not allowed, per user-definition
     */
    const UPLOAD_ERR_NOT_ALLOWED = 10;

    /**
     * Upload directory does not exist
     */
    const UPLOAD_ERR_DIR_NOT_EXIST = 11;

    /**
     * Upload directory not writable
     */
    const UPLOAD_ERR_DIR_NOT_WRITABLE = 12;

    /**
     * Unexpected error
     */
    const UPLOAD_ERR_UNEXPECTED = 13;

    /**
     * Error messageed
     * @var array
     */
    protected static $errorMessages = [
         0 => 'The file uploaded successfully',
         1 => 'The uploaded file exceeds the upload_max_filesize directive',
         2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
         3 => 'The uploaded file was only partially uploaded',
         4 => 'No file was uploaded',
         6 => 'Missing a temporary folder',
         7 => 'Failed to write file to disk',
         8 => 'A PHP extension stopped the file upload',
         9 => 'The uploaded file exceeds the user-defined max file size',
        10 => 'The uploaded file is not allowed',
        11 => 'The specified upload directory does not exist',
        12 => 'The specified upload directory is not writable',
        13 => 'Unexpected error'
    ];

    /**
     * The upload directory path
     * @var string
     */
    protected $uploadDir = null;

    /**
     * The final filename of the uploaded file
     * @var string
     */
    protected $uploadedFile = null;

    /**
     * Allowed maximum file size
     * @var int
     */
    protected $maxSize = 0;

    /**
     * Allowed file types
     * @var array
     */
    protected $allowedTypes = [];

    /**
     * Disallowed file types
     * @var array
     */
    protected $disallowedTypes = [];

    /**
     * Overwrite flag
     * @var boolean
     */
    protected $overwrite = false;

    /**
     * Error flag
     * @var int
     */
    protected $error = 0;

    /**
     * Constructor
     *
     * Instantiate a file upload object
     *
     * @param  string $dir
     * @param  int    $maxSize
     * @param  array  $disallowedTypes
     * @param  array  $allowedTypes
     */
    public function __construct($dir, $maxSize = 0, array $disallowedTypes = null, array $allowedTypes = null)
    {
        $this->setUploadDir($dir);
        $this->setMaxSize($maxSize);

        if ((null !== $disallowedTypes) && (count($disallowedTypes) > 0)) {
            $this->setDisallowedTypes($disallowedTypes);
        }
        if ((null !== $allowedTypes) && (count($allowedTypes) > 0)) {
            $this->setAllowedTypes($allowedTypes);
        }
    }

    /**
     * Create an upload object
     *
     * @param  string $dir
     * @param  int    $maxSize
     * @param  array  $disallowedTypes
     * @param  array  $allowedTypes
     * @return Upload
     */
    public static function create($dir, $maxSize = 0, array $disallowedTypes = null, array $allowedTypes = null)
    {
        return new static($dir, $maxSize, $disallowedTypes, $allowedTypes);
    }

    /**
     * Set default file upload settings
     *
     * @param  string $dir
     * @param  string $file
     * @return string
     */
    public static function checkDuplicate($dir, $file)
    {
        return (new static($dir))->checkFilename($file);
    }

    /**
     * Set default file upload settings
     *
     * @return Upload
     */
    public function setDefaults()
    {
        // Allow basic text, graphic, audio/video, data and archive file types
        $allowedTypes = [
            'ai', 'aif', 'aiff', 'avi', 'bmp', 'bz2', 'csv', 'doc', 'docx', 'eps', 'fla', 'flv', 'gif', 'gz',
            'jpe','jpg', 'jpeg', 'log', 'md', 'mov', 'mp2', 'mp3', 'mp4', 'mpg', 'mpeg', 'otf', 'pdf',
            'png', 'ppt', 'pptx', 'psd', 'rar', 'svg', 'swf', 'tar', 'tbz', 'tbz2', 'tgz', 'tif', 'tiff', 'tsv',
            'ttf', 'txt', 'wav', 'wma', 'wmv', 'xls', 'xlsx', 'xml', 'zip'
        ];

        // Disallow programming/development file types
        $disallowedTypes = [
            'css', 'htm', 'html', 'js', 'json', 'pgsql', 'php', 'php3', 'php4', 'php5', 'sql', 'sqlite', 'yaml', 'yml'
        ];

        // Set max file size to 10 MBs
        $this->setMaxSize(10000000);
        $this->setAllowedTypes($allowedTypes);
        $this->setDisallowedTypes($disallowedTypes);

        return $this;
    }

    /**
     * Set the upload directory
     *
     * @param  string $dir
     * @return Upload
     */
    public function setUploadDir($dir)
    {
        // Check to see if the upload directory exists.
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->error = self::UPLOAD_ERR_DIR_NOT_EXIST;
        // Check to see if the permissions are set correctly.
        } else if (!is_writable($dir)) {
            $this->error = self::UPLOAD_ERR_DIR_NOT_WRITABLE;
        }

        $this->uploadDir = $dir;
        return $this;
    }

    /**
     * Set the upload directory
     *
     * @param  int $maxSize
     * @return Upload
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = (int)$maxSize;
        return $this;
    }

    /**
     * Set the allowed types
     *
     * @param  array $allowedTypes
     * @return Upload
     */
    public function setAllowedTypes(array $allowedTypes)
    {
        foreach ($allowedTypes as $type) {
            $this->addAllowedType($type);
        }
        return $this;
    }

    /**
     * Set the disallowed types
     *
     * @param  array $disallowedTypes
     * @return Upload
     */
    public function setDisallowedTypes(array $disallowedTypes)
    {
        foreach ($disallowedTypes as $type) {
            $this->addDisallowedType($type);
        }
        return $this;
    }

    /**
     * Add an allowed type
     *
     * @param  string $type
     * @return Upload
     */
    public function addAllowedType($type)
    {
        if (!in_array(strtolower($type), $this->allowedTypes)) {
            $this->allowedTypes[] = strtolower($type);
        }
        return $this;
    }

    /**
     * Add a disallowed type
     *
     * @param  string $type
     * @return Upload
     */
    public function addDisallowedType($type)
    {
        if (!in_array(strtolower($type), $this->disallowedTypes)) {
            $this->disallowedTypes[] = strtolower($type);
        }
        return $this;
    }

    /**
     * Remove an allowed type
     *
     * @param  string $type
     * @return Upload
     */
    public function removeAllowedType($type)
    {
        if (in_array(strtolower($type), $this->allowedTypes)) {
            unset($this->allowedTypes[array_search(strtolower($type), $this->allowedTypes)]);
        }
        return $this;
    }

    /**
     * Remove a disallowed type
     *
     * @param  string $type
     * @return Upload
     */
    public function removeDisallowedType($type)
    {
        if (in_array(strtolower($type), $this->disallowedTypes)) {
            unset($this->disallowedTypes[array_search(strtolower($type), $this->disallowedTypes)]);
        }
        return $this;
    }

    /**
     * Set the overwrite flag
     *
     * @param  boolean $overwrite
     * @return Upload
     */
    public function overwrite($overwrite)
    {
        $this->overwrite = (bool)$overwrite;
        return $this;
    }

    /**
     * Get the upload directory
     *
     * @return string
     */
    public function getUploadDir()
    {
        return $this->uploadDir;
    }

    /**
     * Get uploaded file
     *
     * @return string
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * Get uploaded file full path
     *
     * @return string
     */
    public function getUploadedFullPath()
    {
        return $this->uploadDir . DIRECTORY_SEPARATOR . $this->uploadedFile;
    }

    /**
     * Get the max size allowed
     *
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * Get the disallowed file types
     *
     * @return array
     */
    public function getDisallowedTypes()
    {
        return $this->disallowedTypes;
    }

    /**
     * Get the allowed file types
     *
     * @return array
     */
    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }

    /**
     * Determine if a file type is allowed
     *
     * @param  string $ext
     * @return boolean
     */
    public function isAllowed($ext)
    {
        $disallowed = ((count($this->disallowedTypes) > 0) && (in_array(strtolower($ext), $this->disallowedTypes)));
        $allowed    = ((count($this->allowedTypes) == 0) ||
            ((count($this->allowedTypes) > 0) && (in_array(strtolower($ext), $this->allowedTypes))));

        return ((!$disallowed) && ($allowed));
    }

    /**
     * Determine if a file type is not allowed
     *
     * @param  string $ext
     * @return boolean
     */
    public function isNotAllowed($ext)
    {
        $disallowed = ((count($this->disallowedTypes) > 0) && (in_array(strtolower($ext), $this->disallowedTypes)));
        $allowed    = ((count($this->allowedTypes) == 0) ||
            ((count($this->allowedTypes) > 0) && (in_array(strtolower($ext), $this->allowedTypes))));

        return (($disallowed) && (!$allowed));
    }

    /**
     * Determine if the overwrite flag is set
     *
     * @return boolean
     */
    public function isOverwrite()
    {
        return $this->overwrite;
    }

    /**
     * Determine if the upload was a success
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return ($this->error == UPLOAD_ERR_OK);
    }

    /**
     * Determine if the upload was an error
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->error != UPLOAD_ERR_OK);
    }

    /**
     * Get the upload error code
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->error;
    }

    /**
     * Get the upload error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return self::$errorMessages[$this->error];
    }

    /**
     * Check filename for duplicates, returning a new filename appended with _#
     *
     * @param  string $file
     * @return string
     */
    public function checkFilename($file)
    {
        $newFilename  = $file;
        $parts        = pathinfo($file);
        $origFilename = $parts['filename'];
        $ext          = (isset($parts['extension']) && ($parts['extension'] != '')) ? '.' . $parts['extension'] : null;

        $i = 1;

        while (file_exists($this->uploadDir . DIRECTORY_SEPARATOR . $newFilename)) {
            $newFilename = $origFilename . '_' . $i . $ext;
            $i++;
        }

        return $newFilename;
    }

    /**
     * Test a file upload before moving it
     *
     * @param  array  $file
     * @return boolean
     */
    public function test($file)
    {
        if ($this->error != 0) {
            return false;
        } else {
            if (!isset($file['error']) || !isset($file['size']) || !isset($file['tmp_name']) || !isset($file['name'])) {
                return false;
            } else {
                $this->error = $file['error'];
                if ($this->error != 0) {
                    return false;
                } else {
                    $fileSize  = $file['size'];
                    $fileParts = pathinfo($file['name']);
                    $ext       = (isset($fileParts['extension'])) ? $fileParts['extension'] : null;

                    if (($this->maxSize > 0) && ($fileSize > $this->maxSize)) {
                        $this->error = self::UPLOAD_ERR_USER_SIZE;
                        return false;
                    } else if ((null !== $ext) && (!$this->isAllowed($ext))) {
                        $this->error = self::UPLOAD_ERR_NOT_ALLOWED;
                        return false;
                    } else if ($this->error == 0) {
                        return true;
                    } else {
                        $this->error = self::UPLOAD_ERR_UNEXPECTED;
                        return false;
                    }
                }
            }
        }
    }

    /**
     * Upload file to the upload dir, returns the newly uploaded file
     *
     * @param  array  $file
     * @param  string $to
     * @return mixed
     */
    public function upload($file, $to = null)
    {
        if ($this->test($file)) {
            if (null === $to) {
                $to = $file['name'];
            }
            if (!$this->overwrite) {
                $to = $this->checkFilename($to);
            }

            $this->uploadedFile = $to;
            $to = $this->uploadDir . DIRECTORY_SEPARATOR . $to;

            // Move the uploaded file, creating a file object with it.
            if (move_uploaded_file($file['tmp_name'], $to)) {
                return $this->uploadedFile;
            } else {
                $this->error = self::UPLOAD_ERR_UNEXPECTED;
                return false;
            }
        } else {
            return false;
        }

    }

}
