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
 * Message abstract class
 * 
 * This class should be extended by your custom Message.
 * Every message has its own execute method.
 * 
 * @author Andrius Putna <andrius.putna@gmail.com>
 */
abstract class Message
{
    protected $_data = array();

    public function __construct(array $options = array())
    {
        $this->_data = $options;
    }

    abstract public function execute();
    
    public function toArray()
    {
        return $this->_data;
    }
    
    public function __get($key)
    {
        if (!array_key_exists($key, $this->_data)) {
            throw new Exception("Specified field \"$key\" is not in the message");
        }
        return $this->_data[$key];
    }

    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    public function __sleep()
    {
        return array('_data');
    }

    public function __toString()
    {
        return sprintf('Message %s with params %s'.PHP_EOL, get_class($this), print_r($this->_data, true));
    }
}