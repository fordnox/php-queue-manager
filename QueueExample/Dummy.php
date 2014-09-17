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
 * A Dummy Queue Message.
 * 
 * Acts as an example message. It can be safely removed or replaced.
 * 
 * @author Andrius Putna <andrius.putna@gmail.com>
 */
class Dummy extends Message
{
    /**
     * This Message purpose is to return true
     * 
     * @return bool
     */
    public function execute()
    {
        return true;
    }
}