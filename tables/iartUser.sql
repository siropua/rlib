CREATE TABLE `users` (
  `id` bigint(20) NOT NULL default '0',
  `login` varchar(64) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `email` varchar(64) NOT NULL default '',
  `name` varchar(32) NOT NULL default '',
  `admin` tinyint(4) NOT NULL default '0',
  `date_reg` int(10) unsigned NOT NULL default '0',
  `last_login` int(10) unsigned NOT NULL default '0',
  `last_ip` varchar(15) NOT NULL default '',
  `reset_pass_code` varchar(32) NOT NULL default '',
  `reset_pass_timeout` int(10) unsigned NOT NULL default '0',
  `deleted` int(10) unsigned NOT NULL default '0',
  `rights` text NOT NULL,
  
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
