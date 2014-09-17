<?php
/*
 * This file is part of Queue Manager.
 *
 * (c) 2011 Andrius Putna <andrius.putna@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Queue class
 * 
 * Main Queue class represents One Queue and is a Proxy for all queues.
 * 
 * @author Andrius Putna <andrius.putna@gmail.com>
 */
class Queue
{
    /**
     * Message will be selected 
     * once again after 30s for execution.
     * Message must be removed from queue in order to not select it
     */
    const RECEIVE_TIMEOUT_DEFAULT = 30;
    
    private $name;
    private $timeout;

    /**
     * @var $manager Manager
     */
    protected $manager;

    public function __construct($name = 'UNIVERSAL', $timeout = 10000)
    {
        $this->name = $name;
        $this->timeout = $timeout;
    }

    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Return Queue manager
     *
     * @throws Exception
     * @return Manager
     */
    private function getManager()
    {
        if(!$this->manager) {
            throw new Exception('Queue manager was not set');
        }
        return $this->manager;
    }
    
    /**
     * Return queue id
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Return queue timeout
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
    
    /**
     * Send message to Queue
     * 
     * @param Message $message
     * @return bool
     */
    public function send(Message $message)
    {
        return $this->getManager()->addMessageToQueue($message, $this->name);
    }

    /**
     * Executes $max messages from queue
     * 
     * @param int $max 
     * @param int $timeout 
     */
    public function execute($max = 50, $timeout = self::RECEIVE_TIMEOUT_DEFAULT)
    {
        $this->getManager()->executeQueue($this, $max, $timeout);
    }
    
    /**
     * Prints class
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf('Queue: %s Timeout: %s'.PHP_EOL,$this->name, $this->timeout);
    }
}