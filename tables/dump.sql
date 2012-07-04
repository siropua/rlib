/*
SQLyog Enterprise - MySQL GUI v8.14 
MySQL - 4.1.8-max : Database - megatask2
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `blog_comments` */

CREATE TABLE `blog_comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `entry_id` int(10) unsigned NOT NULL default '0',
  `datepost` int(10) unsigned NOT NULL default '0',
  `sort` int(11) NOT NULL default '0',
  `level` int(11) NOT NULL default '0',
  `parent_id` int(10) unsigned NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  `blocked` tinyint(4) NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `text` text NOT NULL,
  `approved` int(10) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `username` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `sort` (`sort`),
  KEY `level` (`level`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_comments` */

/*Table structure for table `blog_comments_t` */

CREATE TABLE `blog_comments_t` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `entry_id` int(10) unsigned NOT NULL default '0',
  `viewed` int(10) unsigned NOT NULL default '0',
  `viewed_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_comments_t` */

/*Table structure for table `blog_favorites` */

CREATE TABLE `blog_favorites` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `post_id` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

/*Data for the table `blog_favorites` */

/*Table structure for table `blog_posts` */

CREATE TABLE `blog_posts` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `url` varchar(255) NOT NULL default '',
  `owner_id` bigint(20) unsigned NOT NULL default '0',
  `blog_id` int(11) NOT NULL default '0',
  `parent_blog_id` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `lastmodified` int(10) unsigned NOT NULL default '0',
  `datepost` int(10) unsigned NOT NULL default '0',
  `visible` tinyint(4) NOT NULL default '0',
  `allow_comments` tinyint(1) NOT NULL default '0',
  `comments` int(10) unsigned NOT NULL default '0',
  `last_comment` int(10) unsigned NOT NULL default '0',
  `last_comment_uid` int(10) unsigned NOT NULL default '0',
  `thumb` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `main_text` longtext NOT NULL,
  `original_text` longtext NOT NULL,
  `full_text` longtext NOT NULL,
  `tags_cache` text NOT NULL,
  `have_cut` tinyint(1) NOT NULL default '0',
  `views` int(10) unsigned NOT NULL default '0',
  `rating` float NOT NULL default '0',
  `ref_clicks` int(10) unsigned NOT NULL default '0',
  `rating_total` int(11) NOT NULL default '0',
  `rating_count` int(11) NOT NULL default '0',
  `copyright_str` varchar(255) NOT NULL default '',
  `source_url` varchar(255) NOT NULL default '',
  `status` enum('posted','deferred','in_moderation','cancelled','deleted','day') default 'posted',
  `when_post` int(10) unsigned NOT NULL default '0',
  `has_mainpic` tinyint(3) unsigned NOT NULL default '0',
  `mainpic` varchar(255) NOT NULL default '',
  `geo_lat` double NOT NULL default '0',
  `geo_lng` double NOT NULL default '0',
  `geo_address` varchar(255) NOT NULL default '',
  `video_type` varchar(20) NOT NULL default '',
  `video_id` varchar(50) NOT NULL default '',
  `video_link` varchar(255) NOT NULL default '',
  `pin_n` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `visible` (`visible`),
  KEY `status` (`status`),
  KEY `last_comment` (`last_comment`),
  KEY `blog_id` (`blog_id`),
  KEY `comments` (`comments`,`status`),
  KEY `list_post` (`status`,`blog_id`,`datepost`),
  KEY `datepost` (`datepost`,`status`),
  KEY `pin_n` (`pin_n`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_posts` */

/*Table structure for table `blog_posts_visits_map` */

CREATE TABLE `blog_posts_visits_map` (
  `visit_id` int(10) unsigned NOT NULL default '0',
  `item_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`visit_id`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_posts_visits_map` */

/*Table structure for table `blog_posts_votes` */

CREATE TABLE `blog_posts_votes` (
  `post_id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `vote` tinyint(4) NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`post_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

/*Data for the table `blog_posts_votes` */

/*Table structure for table `blog_sources` */

CREATE TABLE `blog_sources` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `hash` varchar(32) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `search` varchar(255) NOT NULL default '',
  `search_engine` varchar(20) NOT NULL default '',
  `total_visits` int(10) unsigned NOT NULL default '0',
  `first_visit` int(10) unsigned NOT NULL default '0',
  `last_visit` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_sources` */

/*Table structure for table `blog_tags` */

CREATE TABLE `blog_tags` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `datepost` int(10) unsigned NOT NULL default '0',
  `creator_id` int(10) unsigned NOT NULL default '0',
  `used` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_tags` */

/*Table structure for table `blog_tags_map` */

CREATE TABLE `blog_tags_map` (
  `entry_id` int(10) unsigned NOT NULL default '0',
  `tag_id` int(10) unsigned NOT NULL default '0',
  `datepost` int(10) unsigned NOT NULL default '0',
  `filter_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`entry_id`,`tag_id`),
  KEY `article` (`entry_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

/*Data for the table `blog_tags_map` */

/*Table structure for table `blog_visits` */

CREATE TABLE `blog_visits` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL default '0',
  `visitor_id` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `post_id` int(10) unsigned NOT NULL default '0',
  `blog_id` int(10) unsigned NOT NULL default '0',
  `datevisit` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

/*Data for the table `blog_visits` */

/*Table structure for table `blog_visits_map` */

CREATE TABLE `blog_visits_map` (
  `visit_id` int(10) unsigned NOT NULL default '0',
  `item_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`visit_id`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blog_visits_map` */

/*Table structure for table `blogs` */

CREATE TABLE `blogs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(10) unsigned NOT NULL default '0',
  `url` varchar(50) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `description` text NOT NULL,
  `custom_css` varchar(30) NOT NULL default '',
  `feedburner` varchar(255) NOT NULL default '',
  `list_id` int(10) unsigned NOT NULL default '0',
  `only_domain` varchar(255) NOT NULL default '',
  `posts` int(10) unsigned NOT NULL default '0',
  `ordr` int(11) NOT NULL default '0',
  `keywords` varchar(255) NOT NULL default '',
  `icon` varchar(30) NOT NULL default '',
  `thumb` varchar(50) NOT NULL default '',
  `main_tag_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`,`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `blogs` */

/*Table structure for table `feedbacks` */

CREATE TABLE `feedbacks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `datepost` int(10) unsigned NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `firm` varchar(255) NOT NULL default '',
  `phone` varchar(255) NOT NULL default '',
  `text` text NOT NULL,
  `answer` text NOT NULL,
  `answered` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `feedbacks` */

/*Table structure for table `pages` */

CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `url` varchar(50) NOT NULL default '',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `last_modified` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `kws` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `text` longtext NOT NULL,
  `parent_id` int(11) NOT NULL default '0',
  `comments` int(11) NOT NULL default '0',
  `last_comment` int(11) NOT NULL default '0',
  `last_comment_uid` int(11) NOT NULL default '0',
  `allow_comment` int(11) NOT NULL default '0',
  `ordr` int(11) NOT NULL default '0',
  `show_in_menu` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `pages` */

/*Table structure for table `pages_comments` */

CREATE TABLE `pages_comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `entry_id` int(10) unsigned NOT NULL default '0',
  `datepost` int(10) unsigned NOT NULL default '0',
  `sort` int(11) NOT NULL default '0',
  `level` int(11) NOT NULL default '0',
  `parent_id` int(10) unsigned NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  `blocked` tinyint(4) NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `text` text NOT NULL,
  `approved` int(10) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `username` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `sort` (`sort`),
  KEY `level` (`level`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `pages_comments` */

/*Table structure for table `pages_comments_t` */

CREATE TABLE `pages_comments_t` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `entry_id` int(10) unsigned NOT NULL default '0',
  `viewed` int(10) unsigned NOT NULL default '0',
  `viewed_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `pages_comments_t` */

/*Table structure for table `pages_items` */

CREATE TABLE `pages_items` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `section` varchar(30) NOT NULL default '',
  `item_id` varchar(30) NOT NULL default '',
  `value` text NOT NULL,
  `last_changed` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `section` (`section`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `pages_items` */

/*Table structure for table `photo_albums` */

CREATE TABLE `photo_albums` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `ordr` int(11) NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `photo_albums` */

/*Table structure for table `photos` */

CREATE TABLE `photos` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `album_id` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `photos` */

/*Table structure for table `ref_landings` */

CREATE TABLE `ref_landings` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uri` varchar(255) NOT NULL default '',
  `lands_count` int(10) unsigned NOT NULL default '0',
  `first_land` int(10) unsigned NOT NULL default '0',
  `last_land` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uri` (`uri`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `ref_landings` */

/*Table structure for table `ref_sources` */

CREATE TABLE `ref_sources` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `hash` varchar(32) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `search` varchar(255) NOT NULL default '',
  `search_engine` varchar(20) NOT NULL default '',
  `total_visits` int(10) unsigned NOT NULL default '0',
  `first_visit` int(10) unsigned NOT NULL default '0',
  `last_visit` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `ref_sources` */

/*Table structure for table `ref_visits` */

CREATE TABLE `ref_visits` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL default '0',
  `visitor_id` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `datevisit` date NOT NULL default '0000-00-00',
  `ip` int(11) unsigned NOT NULL default '0',
  `visits` int(10) unsigned NOT NULL default '1',
  `land_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `visitor_ip` (`source_id`,`datevisit`,`ip`,`land_id`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `ref_visits` */

/*Table structure for table `site_settings` */

CREATE TABLE `site_settings` (
  `id` varchar(50) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `value` text NOT NULL,
  `int_value` int(11) NOT NULL default '0',
  `type` enum('text','string','int','bool') NOT NULL default 'text',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `site_settings` */

/*Table structure for table `stat_agents` */

CREATE TABLE `stat_agents` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `stat_agents` */

/*Table structure for table `tags` */

CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `datepost` int(10) unsigned NOT NULL default '0',
  `creator_id` int(10) unsigned NOT NULL default '0',
  `used` int(11) NOT NULL default '0',
  `is_main` int(11) NOT NULL default '0',
  `ordr` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `ordr` (`ordr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tags` */

/*Table structure for table `user_stats` */

CREATE TABLE `user_stats` (
  `id` int(10) unsigned NOT NULL default '0',
  `profile_changes` int(10) unsigned NOT NULL default '0',
  `last_profile_update` int(10) unsigned NOT NULL default '0',
  `comments` int(11) NOT NULL default '0',
  `chat_msg` int(11) NOT NULL default '0',
  `photos` int(11) NOT NULL default '0',
  `profile_views` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `user_stats` */

/*Table structure for table `users` */

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `ordr` int(10) unsigned NOT NULL default '5000',
  `full_name` varchar(50) NOT NULL default '',
  `nick` varchar(50) NOT NULL default '',
  `login` varchar(30) NOT NULL default '',
  `password` varchar(50) NOT NULL default '',
  `salt` int(10) unsigned NOT NULL default '0',
  `email` varchar(200) NOT NULL default '',
  `sex` tinyint(4) NOT NULL default '1',
  `datereg` int(10) unsigned NOT NULL default '0',
  `dateenter` int(10) unsigned NOT NULL default '0',
  `lastenter` int(10) unsigned NOT NULL default '0',
  `last_login` int(10) unsigned NOT NULL default '0',
  `ip` varchar(25) NOT NULL default '',
  `zvan` varchar(100) NOT NULL default '',
  `icon` varchar(30) NOT NULL default 'smile',
  `userpic` varchar(255) NOT NULL default '',
  `refer` varchar(255) NOT NULL default '',
  `res` varchar(15) NOT NULL default '',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `rights` text NOT NULL,
  `immuned` tinyint(1) NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  `lj_confirmed` tinyint(4) NOT NULL default '0',
  `lastpage` varchar(250) NOT NULL default '',
  `hits` int(10) unsigned NOT NULL default '0',
  `last_online` int(10) unsigned NOT NULL default '0',
  `nick_color` varchar(7) NOT NULL default '#000000',
  `msg_color` varchar(7) NOT NULL default '#000000',
  `money` int(11) NOT NULL default '0',
  `skillpoints` double unsigned NOT NULL default '0',
  `last_level` tinyint(3) unsigned NOT NULL default '0',
  `rating` int(11) NOT NULL default '0',
  `rating_count` int(10) unsigned NOT NULL default '0',
  `int_rating` int(11) NOT NULL default '0',
  `int_rating_count` int(10) unsigned NOT NULL default '0',
  `status_title` varchar(255) NOT NULL default '',
  `invite_id` int(10) unsigned NOT NULL default '0',
  `ref_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `immuned` (`immuned`),
  KEY `lastpage_d` (`last_online`),
  KEY `lj_confirmed` (`lj_confirmed`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users` */

/*Table structure for table `users_info` */

CREATE TABLE `users_info` (
  `id` int(10) unsigned NOT NULL default '0',
  `email` varchar(100) NOT NULL default '',
  `phone` varchar(100) NOT NULL default '',
  `about` text NOT NULL,
  `skype` varchar(50) NOT NULL default '',
  `has_map` tinyint(3) unsigned NOT NULL default '0',
  `lat` double NOT NULL default '0',
  `lng` double NOT NULL default '0',
  `icq` varchar(12) NOT NULL default '',
  `email2` varchar(50) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `birthday` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users_info` */

/*Table structure for table `users_money_trans` */

CREATE TABLE `users_money_trans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `admin_id` int(10) unsigned NOT NULL default '0',
  `money` float NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  `current_money` float NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users_money_trans` */

/*Table structure for table `users_temp` */

CREATE TABLE `users_temp` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pass_key` varchar(32) NOT NULL default '',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `last_update` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users_temp` */

/*Table structure for table `users_twitter_auth` */

CREATE TABLE `users_twitter_auth` (
  `user_id` int(10) unsigned NOT NULL default '0' COMMENT 'ID юзера',
  `is_valid` tinyint(4) NOT NULL default '1',
  `access_token` varchar(255) NOT NULL default '',
  `oauth_token` varchar(255) default NULL,
  `oauth_token_secret` varchar(255) default NULL,
  `twitter_login` varchar(50) NOT NULL default '',
  `twitter_uid` int(10) unsigned NOT NULL default '0',
  `dateadd` int(11) NOT NULL default '0',
  `can_grab_statuses` tinyint(3) unsigned NOT NULL default '1' COMMENT 'Разрешено ли парсить статусы',
  `post_statuses_in_chat` tinyint(3) unsigned NOT NULL default '1' COMMENT 'Разрешено ли постить статусы в чят',
  `last_status_grab` int(10) unsigned NOT NULL default '0' COMMENT 'Когда последний раз проверяли статус',
  `can_login` tinyint(3) unsigned NOT NULL default '1' COMMENT 'Можно ли входить через твиттер',
  PRIMARY KEY  (`user_id`),
  KEY `last_status_grab` (`last_status_grab`),
  KEY `twitter_uid` (`twitter_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users_twitter_auth` */

/*Table structure for table `users_twitts` */

CREATE TABLE `users_twitts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `twitter_id` bigint(20) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `status_id` bigint(20) unsigned NOT NULL default '0',
  `reply_to` bigint(20) unsigned NOT NULL default '0',
  `source` varchar(160) NOT NULL default '',
  `text` varchar(160) NOT NULL default '',
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `created_at_int` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `status_id` (`status_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users_twitts` */

/*Table structure for table `users_userpics` */

CREATE TABLE `users_userpics` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `filename` varchar(255) NOT NULL default '',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `size` int(10) unsigned NOT NULL default '0',
  `type` enum('uploaded','imported') NOT NULL default 'uploaded',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `owner_id` (`owner_id`,`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Userpics';

/*Data for the table `users_userpics` */

/*Table structure for table `users_views` */

CREATE TABLE `users_views` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `item_type` enum('news','photo','flood') NOT NULL default 'news',
  `item_id` int(10) unsigned NOT NULL default '0',
  `view_date` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`item_type`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `users_views` */

/*Table structure for table `vote_answers` */

CREATE TABLE `vote_answers` (
  `question_id` int(10) unsigned NOT NULL default '0',
  `answer_id` int(10) unsigned NOT NULL default '0',
  `user_hash` char(32) NOT NULL default '',
  `dateadd` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`question_id`,`user_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `vote_answers` */

/*Table structure for table `vote_questions` */

CREATE TABLE `vote_questions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `question` text NOT NULL,
  `type` enum('radio','checkbox') NOT NULL default 'radio',
  `min_versions` tinyint(3) unsigned NOT NULL default '1',
  `max_versions` tinyint(3) unsigned NOT NULL default '1',
  `answers` int(10) unsigned NOT NULL default '0',
  `dateadd` int(10) unsigned NOT NULL default '0',
  `datestart` int(10) unsigned NOT NULL default '0',
  `datestop` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `vote_questions` */

/*Table structure for table `vote_variants` */

CREATE TABLE `vote_variants` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `question_id` int(10) unsigned NOT NULL default '0',
  `name` text NOT NULL,
  `answers` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `vote_variants` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
