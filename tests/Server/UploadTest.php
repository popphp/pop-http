<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Server\Upload;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{

    public function testConstructor()
    {
        $upload = new Upload(__DIR__ . '/../tmp', 10000, ['php'], ['txt']);
        $upload->overwrite(true);
        $this->assertInstanceOf('Pop\Http\Server\Upload', $upload);
        $this->assertEquals(10000, $upload->getMaxSize());
        $this->assertEquals('php', $upload->getDisallowedTypes()[0]);
        $this->assertEquals('txt', $upload->getAllowedTypes()[0]);
        $this->assertEquals(__DIR__ . '/../tmp', $upload->getUploadDir());
        $this->assertNull($upload->getUploadedFile());
        $this->assertEquals(__DIR__ . '/../tmp/', $upload->getUploadedFullPath());
        $this->assertTrue($upload->isOverwrite());
        $this->assertTrue($upload->isSuccess());
        $this->assertFalse($upload->isError());
    }

    public function testCreate()
    {
        $upload = Upload::create(__DIR__ . '/../tmp', 10000, ['php'], ['txt']);
        $this->assertInstanceOf('Pop\Http\Server\Upload', $upload);
    }

    public function testSetUploadDirException()
    {
        $upload = new Upload(__DIR__ . '/bad');
        $this->assertEquals(Upload::UPLOAD_ERR_DIR_NOT_EXIST, $upload->getErrorCode());
        $this->assertEquals('The specified upload directory does not exist', $upload->getErrorMessage());
    }

    public function testUseDefaults()
    {
        $upload = new Upload(__DIR__ . '/../tmp');
        $upload->setDefaults();
        $this->assertEquals(14, count($upload->getDisallowedTypes()));
        $this->assertEquals(50, count($upload->getAllowedTypes()));
        $upload->removeAllowedType('ai');
        $upload->removeDisallowedType('css');
        $this->assertEquals(13, count($upload->getDisallowedTypes()));
        $this->assertEquals(49, count($upload->getAllowedTypes()));
        $this->assertTrue($upload->isAllowed('psd'));
        $this->assertTrue($upload->isNotAllowed('php'));
    }

    public function testCheckFilename()
    {
        $upload = new Upload(__DIR__ . '/../tmp');
        $this->assertEquals('response_1.txt', $upload->checkFilename('response.txt'));
        $this->assertEquals('response_1.txt', Upload::checkDuplicate(__DIR__ . '/../tmp', 'response.txt'));
    }

    public function testDoesFileExist()
    {
        $upload = new Upload(__DIR__ . '/../tmp');
        $this->assertTrue($upload->fileExists('response.txt'));
        $this->assertTrue(Upload::doesFileExists(__DIR__ . '/../tmp', 'response.txt'));
    }

    public function testTest()
    {
        $file = [
            'name'     => 'upload.txt',
            'type'     => 'text/plain',
            'size'     => 10234,
            'tmp_name' => 'jskn892342',
            'error'    => 0
        ];
        $upload = new Upload(__DIR__ . '/../tmp');
        $this->assertTrue($upload->test($file));
    }

    public function testTestMaxsize()
    {
        $file = [
            'name'     => 'upload.txt',
            'type'     => 'text/plain',
            'size'     => 1023400000000,
            'tmp_name' => 'jskn892342',
            'error'    => 0
        ];
        $upload = new Upload(__DIR__ . '/../tmp', 10000);
        $this->assertFalse($upload->test($file));
        $this->assertEquals(Upload::UPLOAD_ERR_USER_SIZE, $upload->getErrorCode());
    }

    public function testTestNotAllowed()
    {
        $file = [
            'name'     => 'upload.php',
            'type'     => 'text/plain',
            'size'     => 102340,
            'tmp_name' => 'jskn892342',
            'error'    => 0
        ];
        $upload = new Upload(__DIR__ . '/../tmp');
        $upload->setDefaults();
        $this->assertFalse($upload->test($file));
        $this->assertEquals(Upload::UPLOAD_ERR_NOT_ALLOWED, $upload->getErrorCode());
    }

}