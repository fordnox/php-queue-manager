PHP Queue Manager and Task Executor
=================

[![Build Status](https://travis-ci.org/fordnox/php-queue-manager.svg)](https://travis-ci.org/fordnox/php-queue-manager)

Queue manager - A simple, fast queue manager and tasks executor.
Stack your tasks to queue and execute them later.


PHP Queue Manager is a very light component designed to be simple, fast and secure queue manager and executor for PHP.
It is a way to manage asynchronous, potentially long-running PHP tasks such as
API requests, database export/import operations, email sending, payment notification handlers, feed generation etc.

Queue manager can be integrated to any PHP based application with minimum effort, because it does not depend on any framework.
PDO extension is the only requirement.
PHP Queue manager is for those who wants to have a queue running in less than a minute. No additional installation is required.

You can have more than one queue manager running on a single application. Manager instances will always select unique messages from queue.
Messages that fail to execute (throws Exception) will stay in queue. Exception message can be viewed in queue manager.
Executor will try to execute failed message once again after timeout is reached.

Component does not handle dependencies with other messages. That means that message in a queue knows nothing about other messages in queue.
For those who searches for solution to this problem - Gearman is the way to go, but requires time for setup.

Requirements
=================

* PHP >5.3
* PDO
* Database backend (sqlite, sqlite memory, mysql, postgres, any PDO supported database)


Install with Composer
=================

* Install dependency

```json
{
    "require": {
        "fordnox/php-queue-manager": "0.3"
    }
}
```

* Use included sqlite file (extra/queue.sqlite) for basic backend system to connect to via PDO

```php
$dbh = new PDO('sqlite:queue.sqlite');
$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
$dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
$dbh->exec($structure);
```

Example Queue
=================

```php

$message = new \QueueExample\Dummy();

$manager = new \Queue\Manager();
$manager->setPdo($dbh);
$manager->addMessageToQueue($message, 'AmazonEmailsQueue');

// Usually this call should be executed via cron or some other worker
$amazonEmails = $manager->getQueue('AmazonEmailsQueue');
$amazonEmails->execute();

```

Todo
=================

- [ ] Write tests for all SQL backends
- [ ] Create simple Web Interface