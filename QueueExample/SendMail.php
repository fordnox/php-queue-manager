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
 * An email sending message
 * 
 * Message contains enough parameters to send an email
 * 
 * @author Andrius Putna <andrius.putna@gmail.com>
 */
class SendMail extends Message
{
    public function execute()
    {
        $message = wordwrap($this->message, 70);
        mail($this->to_email, $this->subject, $message);
    }
}