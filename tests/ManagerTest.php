<?php

class Queue_ManagerTest extends PHPUnit_Framework_TestCase
{
    private $m;

    public function setUp()
    {
        $dbh = getConnection();
        $this->m = new \Queue\Manager();
        $this->m->setPdo($dbh);
    }
    
    public function tearDown()
    {
        $this->m->clearQueues();
    }
    
    public function testManager()
    {
        $this->assertInstanceOf('\Queue\Manager', $this->m);
        $this->assertInstanceOf('PDO', $this->m->getDb());
    }

    public function testDefaultFlow()
    {
        $q = $this->m->getQueue('default');
        $bool = $q->send(new \QueueExample\Dummy());
        $this->assertTrue($bool);

        $bool = $this->m->addMessageToQueue(new \QueueExample\Dummy(), 'other');
        $this->assertTrue($bool);
    }

    public function testCreateQueue()
    {
        $queue = $this->m->getQueue('UNIVERSAL');
        $this->assertInstanceOf('\Queue\Queue', $queue);
        $this->assertEquals('UNIVERSAL', $queue->getName());

        $queues = $this->m->getQueues();
        $this->assertInstanceOf('ArrayObject', $queues);
        $this->assertInstanceOf('\Queue\Queue', $queues[0]);
        $this->assertEquals(1, count($queues));

        $this->m->executeAll(50);
        $this->m->getStuckMessages();
        $this->m->getWaitingMessages();
        
        $this->m->clearQueue('UNIVERSAL');
        $queues = $this->m->getQueues();
        $this->assertEquals(0, count($queues));
    }
    
    public function testCreateQueueInstance()
    {
        $q = new \Queue\Queue('test');
        $q->setManager($this->m);
        $queues = $this->m->getQueues();
        $this->assertEquals(0, count($queues));
        
        $m = new \QueueExample\Dummy();
        $q->send($m);
        
        $msgs = $this->m->getTotalMessages();
        $this->assertEquals(1, $msgs);

        $queues = $this->m->getQueues();
        $this->assertEquals(1, count($queues));
    }
    
    public function testSummaries()
    {
        $queue = $this->m->getQueue('UNIVERSAL');
        
        $list = $this->m->summary();
        $this->assertInternalType('array', $list);
        
        $list = $this->m->queueSummary($queue->getName());
        $this->assertInternalType('array', $list);
    }
}
