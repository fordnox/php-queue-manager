PHP Queue Manager and Task Executor
=================

[![Build Status](https://travis-ci.org/fordnox/php-queue-manager.svg)](https://travis-ci.org/fordnox/php-queue-manager)

Queue manager - A simple, fast queue manager and tasks executor.
Stack your tasks to queue and execute them later.

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
        "fordnox/php-queue-manager": "0.1"
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