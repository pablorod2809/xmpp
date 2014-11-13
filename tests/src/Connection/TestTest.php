<?php

namespace Fabiang\Xmpp\Connection;

use Fabiang\Xmpp\Options;
use Fabiang\Xmpp\Connection\Socket;
use Fabiang\Xmpp\EventListener\Stream\Stream;
use Fabiang\Xmpp\Event\Event;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-01-20 at 15:29:46.
 *
 * @coversDefaultClass Fabiang\Xmpp\Connection\Test
 */
class TestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Test
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $options = new Options;
        $options->setTo('test');
        $this->object = new Test;
        $this->object->setOptions($options);
        $options->setConnection($this->object);
    }

    /**
     * Test connect.
     *
     * @covers ::connect
     * @return void
     */
    public function testConnect()
    {
        $this->object->connect();
        $this->assertContains(
            sprintf(Socket::STREAM_START, 'test'),
            $this->object->getBuffer()
        );
        $this->assertTrue($this->object->isConnected());
    }

    /**
     * Test disconnect.
     *
     * @covers ::disconnect
     * @return void
     */
    public function testDisconnect()
    {
        $this->object->connect();
        $this->assertTrue($this->object->isConnected());
        $this->object->disconnect();
        $this->assertContains(Socket::STREAM_END, $this->object->getBuffer());
        $this->assertFalse($this->object->isConnected());
    }

    /**
     * Test receiving data.
     *
     * @covers ::receive
     * @return void
     */
    public function testReceive()
    {
        $received1 = '<?xml version="1.0"?><test xmlns="test">';
        $received2 = '<test></test>';

        $this->object->setData(array($received1, $received2));

        $this->assertSame($received1, $this->object->receive());
        $this->assertSame($received2, $this->object->receive());
        $this->assertNull($this->object->receive());
    }

    /**
     * Test sending data.
     *
     * @covers ::send
     * @covers ::getBuffer
     * @return void
     */
    public function testSend()
    {
        $this->object->connect();
        $this->object->send('<test></test>');
        $buffer = $this->object->getBuffer();
        $this->assertSame('<test></test>', $buffer[1]);
    }

    /**
     * Test setting and getting data.
     *
     * @covers ::setData
     * @covers ::getData
     * @return void
     */
    public function testSetAndGetData()
    {
        $this->assertSame(array(1, 2, 3), $this->object->setData(array(1, 2, 3))->getData());
    }

    /**
     *
     *
     * @covers ::checkBlockingListeners
     * @return void
     */
    public function testBlockingListener()
    {
        $eventManager = $this->object->getEventManager();

        $eventListener = new Stream;
        $eventListener->setEventManager($eventManager)
            ->setOptions($this->object->getOptions())
            ->attachEvents();
        $this->object->addListener($eventListener);

        $calls = 0;
        $lastMessage = null;
        $eventManager->attach('logger', function (Event $event) use (&$calls, &$lastMessage) {
            $calls++;
            $lastMessage = $event->getParameter(0);
        });

        $this->object->setData(array(
           "<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' "
            . "id='1234567890' from='localhost' version='1.0' xml:lang='en'><stream:features></stream:features>"
        ));
        $this->object->connect();
        $this->assertContains(sprintf(Socket::STREAM_START, 'test'), $this->object->getBuffer());
        $this->assertSame(1, $calls, 'logger was called more then one time');
        $this->assertSame('Listener "Fabiang\Xmpp\EventListener\Stream\Stream" is currently blocking', $lastMessage);
    }

    /**
     * Check timeout when not receiving input.
     *
     * @covers ::checkTimeout
     * @expectedException \Fabiang\Xmpp\Exception\TimeoutException
     * @expectedExceptionMessage Connection lost after 0 seconds
     * @medium
     * @return void
     */
    public function testReceiveWithTimeout()
    {
        $this->object->getOptions()->setTimeout(0);
        $this->object->connect();
        $this->object->setData(array(
           "<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' "
            . "id='1234567890' from='localhost' version='1.0' xml:lang='en'><stream:features></stream:features>"
        ));
        $this->object->receive();
        $this->object->receive();
        $this->object->receive();
    }

    /**
     * @covers ::isReady
     * @covers ::setReady
     */
    public function testSetAndIsReady()
    {
        $this->assertFalse($this->object->isReady());
        $this->object->setReady(1);
        $this->assertTrue($this->object->isReady());
    }

    /**
     * @covers ::setOptions
     * @covers ::getOptions
     */
    public function testSetAndGetOptions()
    {
        $options = new Options;
        $this->assertSame($options, $this->object->setOptions($options)->getOptions());
    }
}
