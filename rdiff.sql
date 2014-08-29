

DROP TABLE IF EXISTS `access`;

CREATE TABLE `access` (
  `id` int(11) NOT NULL auto_increment,
  `backupsetupid` int(11) default NULL,
  `usersid` int(11) default NULL,
  UNIQUE KEY `id` (`id`)
); 

DROP TABLE IF EXISTS `backupsetup`;
CREATE TABLE `backupsetup` (
  `id` int(11) NOT NULL auto_increment,
  `sequence` int(11) default NULL,
  `path_to_file` varchar(255) default NULL,
  `path_to_backup` varchar(255) default NULL,
  `includes` varchar(255) default NULL,
  `exception` varchar(255) default NULL,
  `keep_for` varchar(5) default NULL,
  `connect_type` varchar(10) default NULL,
  `mountoptions` varchar(255) default NULL,
  `active` tinyint(4) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`)
); 

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `email` varchar(50) default NULL,
  `username` varchar(15) default NULL,
  `password` varchar(50) default NULL,
  `administrator` tinyint(4) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`)
); 

INSERT INTO `users` VALUES (1,'admin','admin@domain.com','admin','21232f297a57a5a743894a0e4a801fc3',1),(11,'test','test@example.com','test','098f6bcd4621d373cade4e832627b4f6',0);
