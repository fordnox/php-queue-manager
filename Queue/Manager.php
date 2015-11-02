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
 * Queue Manager class
 * 
 * Manage queues using this class
 * 
 * @author Andrius Putna <andrius.putna@gmail.com>
 */
class Manager
{
    /**
     * @var $_pdo \PDO
     */
    protected $_pdo;
    
    /* tables for MQ */
    protected $queueTable   = 'queue'; // list of allowed queues
    protected $messageTable = 'message'; // list of current messages in allowed queues
    
    public function setPdo(\PDO $pdo)
    {
        $this->_pdo = $pdo;
    }

    /**
     * Configure database
     *
     * @throws Exception
     * @return \PDO
     */
    public function getDb()
    {
        if(!is_object($this->_pdo)) {
            throw new Exception('Database connection was not set');
        }
        return $this->_pdo;
    }
    
    /**
     * Return Queue summary 
     * 
     * @param string $name
     * @return array
     */
    public function queueSummary($name)
    {
        $qid = $this->getQueueId($name);
        
        $db = $this->getDb();
        $list = array();
        $sql = 'SELECT * FROM ' . $this->messageTable . ' WHERE queue_id = :queue_id';
        $sth = $db->prepare($sql);
        $sth->execute(array('queue_id'=>$qid));
        foreach($sth->fetchAll() as $msg) {
            $o = unserialize(base64_decode($msg['body']));
            $list[] = array(
                'queue_name'    => $name,
                'message_id'    => $msg['message_id'],
                'message_class' => get_class($o),
                'handle'        => $msg['handle'],
                'log'           => $msg['log'],
                'created'       => $msg['created'],
                'params'        => $o->toArray(),
                'timeout'       => $msg['timeout'],
            );
        }
        return $list;
    }
    
    /**
     * Return all Queues summary
     * 
     * @return array
     */
    public function summary()
    {
        $list = array();
        foreach($this->getQueues() as $queue) {
            $list = array_merge($list, $this->queueSummary($queue->getName()));
        }
        return $list;
    }

    /**
     * Select unselected messages from queue
     *
     * @param Queue $queue
     * @param int $max
     * @param int $timeout
     * @throws Exception
     * @return \ArrayObject
     */
    private function receiveQueueMessages(Queue $queue, $max, $timeout)
    {
        $messages      = array();
        $microtime = microtime(true); // cache microtime
        $db        = $this->getDb();
        $qid       = $this->getQueueId($queue->getName());
        
        // start transaction handling
        try {
            if ( $max > 0 ) {
                $db->beginTransaction();

                $sql = "SELECT *
                        FROM " . $this->messageTable . "
                        WHERE queue_id = :queue_id
                        AND (handle IS NULL OR timeout+" . (int)$timeout . " < " . (int)$microtime .")
                        LIMIT ".$max;
                $stmt = $db->prepare($sql);
                $stmt->execute(array('queue_id'=>$qid));

                foreach ($stmt->fetchAll() as $data) {
                    $data['handle'] = md5(uniqid(rand(), true));

                    $sql = "UPDATE " . $this->messageTable . "
                            SET
                                handle = :handle,
                                timeout = :timeout
                            WHERE
                                message_id = :id
                                AND (handle IS NULL OR timeout+" . (int)$timeout . " < " . (int)$microtime.")";

                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':handle', $data['handle'], \PDO::PARAM_STR);
                    $stmt->bindParam(':id', $data['message_id'], \PDO::PARAM_STR);
                    $stmt->bindValue(':timeout', $microtime);
                    $updated = $stmt->execute();
                    
                    if ($updated) {
                        $messages[] = $data;
                    }
                }
                $db->commit();
            }
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
        $m = array();
        foreach($messages as $msg) {
            $message = unserialize(base64_decode($msg['body']));
            if($message instanceof Message) {
                $message->message_id = $msg['message_id'];
                $m[] = $message;
            }
        }

        return new \ArrayObject($m);
    }
    
    /**
     * Execute messages in queue
     * 
     * @param Queue $queue
     * @param int $max
     * @param int $timeout 
     */
    public function executeQueue(Queue $queue, $max, $timeout = 30)
    {
        foreach($this->receiveQueueMessages($queue, $max, $timeout) as $message) {
            try {
                $message->execute();
                $this->deleteMessage($message);
            } catch(\Exception $e) {
                $this->log($message, $e);
            }
        }
    }
    
    /**
     * Execute all queues
     * 
     * @param int $max
     */
    public function executeAll($max = 50)
    {
        $sth = $this->getDb()->prepare('SELECT * FROM ' . $this->queueTable . ' WHERE 1');
        $sth->execute();
        foreach($sth->fetchAll() as $queue) {
            $q = new Queue($queue['queue_name'], $queue['timeout']);
            $q->setManager($this);
            $q->execute($max);
        }
    }
    
    /**
     * Add new message to queue
     * 
     * @param Message $message
     * @param string $name
     * @return bool
     */
    public function addMessageToQueue(Message $message, $name)
    {
        $q      = $this->getQueue($name);
        $qid    = $this->getQueueId($q->getName());
        $body   = base64_encode(serialize($message));
        $md5    = md5($body);

        $sql = 'INSERT INTO ' . $this->messageTable . '
            (queue_id, body, created, timeout, md5)
            VALUES
            (:queue_id, :body, :created, :timeout, :md5)
            ';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindParam(':queue_id', $qid, \PDO::PARAM_INT);
        $stmt->bindParam(':body', $body, \PDO::PARAM_STR);
        $stmt->bindParam(':md5', $md5, \PDO::PARAM_STR);
        $stmt->bindValue(':created', time(), \PDO::PARAM_INT);
        $stmt->bindValue(':timeout', 30, \PDO::PARAM_INT);
        $stmt->execute();
        return true;
    }

    /**
     * Return new queue instance
     *
     * @param string $name
     * @param int $timeout
     * @throws Exception
     * @return Queue
     */
    public function getQueue($name, $timeout = null)
    {
        if (!is_string($name)) {
            throw new Exception('$name is not a string');
        }

        if ((null !== $timeout) && !is_integer($timeout)) {
            throw new Exception('$timeout must be an integer');
        }

        if(!$this->getQueueId($name)) {
            $this->create($name, $timeout);
        }

        $q = new Queue($name, $timeout);
        $q->setManager($this);
        return $q;
    }

    /**
     * Returns queue id or bool false
     * @param string $name
     * @return int 
     */
    public function getQueueId($name)
    {
        $sql = 'SELECT queue_id FROM ' . $this->queueTable . ' WHERE queue_name = ? LIMIT 1';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute(array($name));
        return $stmt->fetchColumn();
    }
    
    /**
     * Creates new Queue
     * 
     * @param string $name
     * @param int $timeout
     * @return bool 
     */
    private function create($name, $timeout = null)
    {
        if(null === $timeout) {
            $timeout = 10000;
        }

        $sql = 'INSERT INTO ' . $this->queueTable . '
            (queue_name, timeout)
            VALUES
            (:queue_name, :timeout)
            ';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindParam(':queue_name', $name, \PDO::PARAM_STR);
        $stmt->bindParam(':timeout', $timeout, \PDO::PARAM_INT);
        $stmt->execute();
        return true;
    }

    public function getQueues()
    {
        $sql = 'SELECT * FROM ' . $this->queueTable . ' WHERE 1';
        $sth = $this->getDb()->prepare($sql);
        $sth->execute();
        $list = array();
        foreach($sth->fetchAll() as $q) {
            $list[] = new Queue($q['queue_name'], $q['timeout']);
        }
        return new \ArrayObject($list);
    }
    
    public function clearQueues()
    {
        $sth = $this->getDb()->prepare('DELETE FROM ' . $this->messageTable . ' WHERE 1');
        $sth->execute();
        $sth = $this->getDb()->prepare('DELETE FROM ' . $this->queueTable . ' WHERE 1');
        $sth->execute();
    }
    
    public function clearQueue($name)
    {
        $qid = $this->getQueueId($name);
        $sth = $this->getDb()->prepare('DELETE FROM ' . $this->messageTable . ' WHERE queue_id = ?');
        $sth->execute(array($qid));
        $sth = $this->getDb()->prepare('DELETE FROM ' . $this->queueTable . ' WHERE queue_id = ?');
        $sth->execute(array($qid));
    }
    
    public function getWaitingMessages()
    {
        $db = $this->getDb();
        $sql = 'SELECT * FROM ' . $this->messageTable . ' WHERE handle IS NULL';
        $sth = $db->prepare($sql);
        $sth->execute();
        $list = array();
        foreach($sth->fetchAll() as $msg) {
            $list[] = unserialize(base64_decode($msg['body']));
        }
        return $list;
    }
    
    public function getStuckMessages()
    {
        $db = $this->getDb();
        $sql = 'SELECT * FROM ' . $this->messageTable . ' WHERE handle IS NOT NULL';
        $sth = $db->prepare($sql);
        $sth->execute();
        $list = array();
        foreach($sth->fetchAll() as $msg) {
            $list[] = unserialize(base64_decode($msg['body']));
        }
        return $list;
    }
    
    /**
     * Remove message from queue
     * 
     * @param Message $message
     * @return bool 
     */
    public function deleteMessage(Message $message)
    {
        return $this->deleteMessageById($message->message_id);
    }
    
    /**
     * Remove message from queue by id
     * 
     * @param int $id
     * @return bool 
     */
    public function deleteMessageById($id)
    {
        $sql = 'DELETE FROM ' . $this->messageTable . ' WHERE message_id = ?';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute(array($id));
        return true;
    }
    
    /**
     * Saves error message to message on execution failure
     *
     * @param Message $message
     * @param \Exception $e
     * @return void
     */
    public function log(Message $message, \Exception $e)
    {
        $sql = "UPDATE " . $this->messageTable . "
                SET log = :log
                WHERE message_id = :id
        ";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':log', $e->getMessage(), \PDO::PARAM_STR);
        $stmt->bindValue(':id', $message->message_id, \PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * Get total messages in all queues
     * 
     * @return int
     */
    public function getTotalMessages()
    {
        $sql = 'SELECT COUNT(message_id) FROM ' . $this->messageTable . ' WHERE 1';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
