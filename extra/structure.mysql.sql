
DROP TABLE IF EXISTS `message`;

CREATE TABLE `message` (
  `message_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) unsigned NOT NULL,
  `handle` char(32) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `md5` char(32) NOT NULL DEFAULT '',
  `timeout` double NOT NULL,
  `created` int(11) unsigned NOT NULL,
  `log` text NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table queue
# ------------------------------------------------------------

DROP TABLE IF EXISTS `queue`;

CREATE TABLE `queue` (
  `queue_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queue_name` varchar(100) NOT NULL DEFAULT '',
  `timeout` int(10) unsigned NOT NULL DEFAULT '30',
  PRIMARY KEY (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;