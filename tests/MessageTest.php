<?php

class Queue_MessageTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->m = new \QueueExample\Dummy();
    }
    
    public function testQueue()
    {
        $this->assertInstanceOf('\Queue\Message', $this->m);

        $this->m->variable = 'value';
        
        try {
            $this->m->fake;
        } catch(Exception $e) {
            
        }
        
        $data = $this->m->toArray();
        $this->assertInternalType('array', $data);
        $str = $this->m->__toString();
        $this->assertInternalType('string', $str);
    }
}
