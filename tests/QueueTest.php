<?php

class Queue_QueueTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dbh = getConnection();
        $m = new \Queue\Manager();
        $m->setPdo($dbh);

        $q = new \Queue\Queue('PHPUNIT', 5000);
        $q->setManager($m);

        $this->m = $m;
        $this->q = $q;
    }
    
    public function tearDown()
    {
        $this->m->clearQueues();
    }
    
    public function testQueue()
    {
        $this->assertInstanceOf('\Queue\Queue', $this->q);
        
        $name = $this->q->getName();
        $this->assertEquals('PHPUNIT', $name);
        
        $timeout = $this->q->getTimeout();
        $this->assertEquals(5000, $timeout);
        
        $m = new \QueueExample\Dummy();
        $bool = $this->q->send($m);
        $this->assertTrue($bool);
        
        $this->q->execute(1);
        
        $total = $this->m->getTotalMessages();
        $this->assertEquals(0, $total);
        $str = $this->q->__toString();
        $this->assertInternalType('string', $str);
    }
}
