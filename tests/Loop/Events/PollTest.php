<?php
namespace Icicle\Tests\Loop\Events;

use Icicle\Loop\Events\Poll;
use Icicle\Tests\TestCase;

class PollTest extends TestCase
{
    const TIMEOUT = 0.1;
    
    protected $loop;

    protected $manager;
    
    public function setUp()
    {
        $this->loop = $this->getMock('Icicle\Loop\LoopInterface');

        $this->manager = $this->getMock('Icicle\Loop\Manager\PollManagerInterface');

        $this->loop->method('createPoll')
            ->will($this->returnCallback(function ($resource, callable $callback) {
                return new Poll($this->manager, $resource, $callback);
            }));
    }
    
    public function createSockets()
    {
        return stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    }
    
    public function testGetResource()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));
        
        $this->assertSame($socket, $poll->getResource());
    }
    
    /**
     * @depends testGetResource
     * @expectedException \Icicle\Loop\Exception\InvalidArgumentException
     */
    public function testInvalidResource()
    {
        $poll = $this->loop->createPoll(1, $this->createCallbacK(0));
    }
    
    public function testGetCallback()
    {
        list($socket) = $this->createSockets();
        
        $callback = $this->createCallback(1);
        $callback->method('__invoke')
                 ->with($this->identicalTo($socket), $this->identicalTo(false));
        
        $poll = $this->loop->createPoll($socket, $callback);
        
        $callback = $poll->getCallback();
        
        $this->assertTrue(is_callable($callback));
        
        $callback($socket, false);
    }
    
    public function testCall()
    {
        list($socket) = $this->createSockets();
        
        $callback = $this->createCallback(2);
        $callback->method('__invoke')
                 ->with($this->identicalTo($socket), $this->identicalTo(false));
        
        $poll = $this->loop->createPoll($socket, $callback);
        
        $poll->call($socket, false);
        $poll->call($socket, false);
    }
    
    /**
     * @depends testCall
     */
    public function testInvoke()
    {
        list($socket) = $this->createSockets();
        
        $callback = $this->createCallback(2);
        $callback->method('__invoke')
                 ->with($this->identicalTo($socket), $this->identicalTo(false));
        
        $poll = $this->loop->createPoll($socket, $callback);
        
        $poll($socket, false);
        $poll($socket, false);
    }
    
    /**
     * @depends testGetCallback
     */
    public function testSetCallback()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));
        
        $callback = $this->createCallback(1);
        $callback->method('__invoke')
                 ->with($this->identicalTo($socket), $this->identicalTo(false));
        
        $poll->setCallback($callback);
        
        $callback = $poll->getCallback();
        
        $this->assertTrue(is_callable($callback));
        
        $callback($socket, false);
    }
    
    public function testListen()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('listen')
            ->with($this->identicalTo($poll));
        
        $poll->listen();
    }
    
    /**
     * @depends testListen
     */
    public function testListenWithTimeout()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));

        $this->manager->expects($this->once())
            ->method('listen')
            ->with($this->identicalTo($poll), $this->identicalTo(self::TIMEOUT));
        
        $poll->listen(self::TIMEOUT);
    }
    
    public function testIsPending()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('isPending')
            ->with($this->identicalTo($poll))
            ->will($this->returnValue(true));
        
        $this->assertTrue($poll->isPending());
    }
    
    public function testFree()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('free')
            ->with($this->identicalTo($poll))
            ->will($this->returnValue(true));
        
        $this->manager->expects($this->once())
            ->method('isFreed')
            ->with($this->identicalTo($poll))
            ->will($this->returnValue(true));
        
        $poll->free();
        
        $this->assertTrue($poll->isFreed());
    }
    
    public function testCancel()
    {
        list($socket) = $this->createSockets();
        
        $poll = $this->loop->createPoll($socket, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('cancel')
            ->with($this->identicalTo($poll));
        
        $poll->cancel();
    }
}
