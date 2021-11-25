<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\DoSpaces;

class DoSpacesTest extends TestCase
{
    /**
     * @var DoSpaces
     */
    protected $object = null;
    protected $root = '/root';

    public function setUp(): void
    {
        $this->root = '/root';
        $key = $_SERVER['DO_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['DO_SECRET'] ?? '';
        $bucket = "utopia-storage-tests";

        $this->object = new DoSpaces($this->root, $key, $secret, $bucket, DoSpaces::NYC3, DoSpaces::ACL_PUBLIC_READ);

        $this->uploadTestFiles();
    }

    private function uploadTestFiles()
    {
        $this->object->upload(__DIR__ . '/../../resources/disk-a/kitten-1.jpg', $this->object->getPath('testing/kitten-1.jpg'));
        $this->object->upload(__DIR__ . '/../../resources/disk-a/kitten-2.jpg', $this->object->getPath('testing/kitten-2.jpg'));
        $this->object->upload(__DIR__ . '/../../resources/disk-b/kitten-1.png', $this->object->getPath('testing/kitten-1.png'));
        $this->object->upload(__DIR__ . '/../../resources/disk-b/kitten-2.png', $this->object->getPath('testing/kitten-2.png'));
    }

    private function removeTestFiles()
    {
        $this->object->delete($this->object->getPath('testing/kitten-1.jpg'));
        $this->object->delete($this->object->getPath('testing/kitten-2.jpg'));
        $this->object->delete($this->object->getPath('testing/kitten-1.png'));
        $this->object->delete($this->object->getPath('testing/kitten-2.png'));
    }

    public function tearDown(): void
    {
        $this->removeTestFiles();
    }

    public function testName()
    {
        $this->assertEquals($this->object->getName(), 'Digitalocean Spaces Storage');
    }

    public function testDescription()
    {
        $this->assertEquals($this->object->getDescription(), 'Digitalocean Spaces Storage');
    }

    public function testRoot()
    {
        $this->assertEquals($this->object->getRoot(), $this->root);
    }

    public function testPath()
    {
        $this->assertEquals($this->object->getPath('image.png'), $this->root . '/i/m/a/g/image.png');
        $this->assertEquals($this->object->getPath('x.png'), $this->root . '/x/./p/n/x.png');
        $this->assertEquals($this->object->getPath('y'), $this->root . '/y/x/x/x/y');
    }

    public function testWrite()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text.txt'), 'Hello World', 'text/plain'), true);

        $this->object->delete($this->object->getPath('text.txt'));
    }

    public function testRead()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-read.txt'), 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-read.txt')), 'Hello World');

        $this->object->delete($this->object->getPath('text-for-read.txt'));
    }

    public function testMove()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-move.txt'), 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move.txt')), 'Hello World');
        $this->assertEquals($this->object->move($this->object->getPath('text-for-move.txt'), $this->object->getPath('text-for-move-new.txt')), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move-new.txt')), 'Hello World');
        $this->assertEquals($this->object->exists($this->object->getPath('text-for-move.txt')), false);

        $this->object->delete($this->object->getPath('text-for-move-new.txt'));
    }

    public function testDelete()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-delete.txt'), 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-delete.txt')), 'Hello World');
        $this->assertEquals($this->object->delete($this->object->getPath('text-for-delete.txt')), true);
    }

    public function testDeletePath()
    {
        // Test Single Object
        $path = $this->object->getPath('text-for-delete-path.txt');
        $path = str_ireplace($this->object->getRoot(), $this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket', $path);
        $this->assertEquals($this->object->write($path, 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->exists($path), true);
        $res = $this->object->deletePath($this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket');
        $this->assertEquals($this->object->exists($path), false);
        
        // Test Multiple Objects
        $path = $this->object->getPath('text-for-delete-path1.txt');
        $path = str_ireplace($this->object->getRoot(), $this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket', $path);
        $this->assertEquals($this->object->write($path, 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->exists($path), true);

        $path2 = $this->object->getPath('text-for-delete-path2.txt');
        $path2 = str_ireplace($this->object->getRoot(), $this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket', $path2);
        $this->assertEquals($this->object->write($path2, 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->exists($path2), true);

        $this->assertEquals($this->object->deletePath($this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket'), true);
        $this->assertEquals($this->object->exists($path), false);
        $this->assertEquals($this->object->exists($path2), false);
        

    }

    public function testFileSize()
    {
        $this->assertEquals($this->object->getFileSize($this->object->getPath('testing/kitten-1.jpg')), 599639);
        $this->assertEquals($this->object->getFileSize($this->object->getPath('testing/kitten-2.jpg')), 131958);
    }

    public function testFileMimeType()
    {
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-1.jpg')), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-2.jpg')), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-1.png')), 'image/png');
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-2.png')), 'image/png');
    }

    public function testFileHash()
    {
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-1.jpg')), '7551f343143d2e24ab4aaf4624996b6a');
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-2.jpg')), '81702fdeef2e55b1a22617bce4951cb5');
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-1.png')), '03010f4f02980521a8fd6213b52ec313');
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-2.png')), '8a9ed992b77e4b62b10e3a5c8ed72062');
    }

    public function testDirectorySize()
    {
        $this->assertEquals(-1, $this->object->getDirectorySize('resources/disk-a/'));
    }

    public function testPartitionFreeSpace()
    {
        $this->assertEquals(-1, $this->object->getPartitionFreeSpace());
    }

    public function testPartitionTotalSpace()
    {
        $this->assertEquals(-1, $this->object->getPartitionTotalSpace());
    }

    public function testPartUpload() {
        $source = __DIR__ . "/../../resources/disk-a/large_file.mp4";
        $dest = $this->object->getPath('uploaded.mp4');
        $totalSize = \filesize($source);
        // AWS S3 requires each part to be at least 5MB except for last part
        $chunkSize = 5*1024*1024;

        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $metadata = [
            'parts' => [],
            'chunks' => 0,
            'uploadId' => null,
            'content_type' => \mime_content_type($source),
        ];
        $handle = @fopen($source, "rb");
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $etag = $this->object->upload($op, $dest, $chunk, $chunks, $metadata);
            $parts[] = ['partNumber' => $chunk, 'etag' => $etag];
            $start += strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);
        unlink($op);

        $this->assertEquals(\filesize($source), $this->object->getFileSize($dest));

        // S3 doesnt provide a method to get a proper MD5-hash of a file created using multipart upload
        // https://stackoverflow.com/questions/8618218/amazon-s3-checksum
        // More info on how AWS calculates ETag for multipart upload here
        // https://savjee.be/2015/10/Verifying-Amazon-S3-multi-part-uploads-with-ETag-hash/
        // TODO
        // $this->assertEquals(\md5_file($source), $this->object->getFileHash($dest));
        $this->object->delete($dest);
    }
}
