<?php
/*
 * This file is part of Queue Manager.
 *
 * (c) 2011 Andrius Putna <andrius.putna@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace QueueExample;

use Queue\Message;

/**
 * An example message with error
 * 
 * Acts as an example message. It can be safely removed or replaced.
 * 
 * @author Andrius Putna <andrius.putna@gmail.com>
 */
class Error extends Message
{
    /**
     * This Message purpose is to throw an Exception
     * 
     * @return bool
     * @throws \LogicException
     */
    public function execute()
    {
        throw new \LogicException('This message should be logged to queue', 123);
    }
}