<?php

namespace React\Tests\EventLoop;

use React\Tests\Socket\TestCase;

abstract class AbstractLoopTest extends TestCase
{
    protected $loop;

    public function setUp()
    {
        $this->loop = $this->createLoop();
    }

    abstract public function createLoop();

    public function testAddReadStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableExactly(2));

        fwrite($input, "foo\n");
        rewind($input);
        $this->loop->tick();

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testAddWriteStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addWriteStream($input, $this->expectCallableExactly(2));
        $this->loop->tick();
        $this->loop->tick();
    }

    public function testRemoveReadStreamInstantly()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableNever());
        $this->loop->removeReadStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveReadStreamAfterReading()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableOnce());

        fwrite($input, "foo\n");
        rewind($input);
        $this->loop->tick();

        $this->loop->removeReadStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveWriteStreamInstantly()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addWriteStream($input, $this->expectCallableNever());
        $this->loop->removeWriteStream($input);
        $this->loop->tick();
    }

    public function testRemoveWriteStreamAfterWriting()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addWriteStream($input, $this->expectCallableOnce());
        $this->loop->tick();

        $this->loop->removeWriteStream($input);
        $this->loop->tick();
    }

    public function testRemoveStreamInstantly()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableNever());
        $this->loop->addWriteStream($input, $this->expectCallableNever());
        $this->loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableOnce());
        $this->loop->addWriteStream($input, $this->expectCallableOnce());

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();

        $this->loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }
}
