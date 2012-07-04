-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Фев 08 2011 г., 13:16
-- Версия сервера: 5.1.41
-- Версия PHP: 5.3.2-1ubuntu4.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `newproject`
--

-- --------------------------------------------------------

--
-- Структура таблицы `blogs`
--

CREATE TABLE IF NOT EXISTS `blogs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `custom_css` varchar(30) NOT NULL DEFAULT '',
  `feedburner` varchar(255) NOT NULL DEFAULT '',
  `list_id` int(10) unsigned NOT NULL DEFAULT '0',
  `only_domain` varchar(255) NOT NULL DEFAULT '',
  `posts` int(10) unsigned NOT NULL DEFAULT '0',
  `ordr` int(11) NOT NULL DEFAULT '0',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(30) NOT NULL DEFAULT '',
  `thumb` varchar(50) NOT NULL DEFAULT '',
  `main_tag_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`,`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `blogs`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_comments`
--

CREATE TABLE IF NOT EXISTS `blog_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  `level` int(11) NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `blocked` tinyint(4) NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `approved` int(10) unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `sort` (`sort`),
  KEY `level` (`level`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=67 ;

--
-- Дамп данных таблицы `blog_comments`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_comments_t`
--

CREATE TABLE IF NOT EXISTS `blog_comments_t` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `viewed` int(10) unsigned NOT NULL DEFAULT '0',
  `viewed_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `blog_comments_t`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_favorites`
--

CREATE TABLE IF NOT EXISTS `blog_favorites` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

--
-- Дамп данных таблицы `blog_favorites`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_posts`
--

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `blog_id` int(11) NOT NULL DEFAULT '0',
  `parent_blog_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  `lastmodified` int(10) unsigned NOT NULL DEFAULT '0',
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL DEFAULT '0',
  `allow_comments` tinyint(1) NOT NULL DEFAULT '0',
  `comments` int(10) unsigned NOT NULL DEFAULT '0',
  `last_comment` int(10) unsigned NOT NULL DEFAULT '0',
  `last_comment_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `thumb` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `main_text` longtext NOT NULL,
  `original_text` longtext NOT NULL,
  `full_text` longtext NOT NULL,
  `tags_cache` text NOT NULL,
  `have_cut` tinyint(1) NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `rating` float NOT NULL DEFAULT '0',
  `ref_clicks` int(10) unsigned NOT NULL DEFAULT '0',
  `rating_total` int(11) NOT NULL DEFAULT '0',
  `rating_count` int(11) NOT NULL DEFAULT '0',
  `copyright_str` varchar(255) NOT NULL,
  `source_url` varchar(255) NOT NULL,
  `status` enum('posted','deferred','in_moderation','cancelled','deleted','day') DEFAULT 'posted',
  `when_post` int(10) unsigned NOT NULL DEFAULT '0',
  `has_mainpic` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `mainpic` varchar(255) NOT NULL,
  `geo_lat` double NOT NULL DEFAULT '0',
  `geo_lng` double NOT NULL DEFAULT '0',
  `geo_address` varchar(255) NOT NULL,
  `video_type` varchar(20) NOT NULL,
  `video_id` varchar(50) NOT NULL,
  `video_link` varchar(255) NOT NULL,
  `pin_n` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `visible` (`visible`),
  KEY `status` (`status`),
  KEY `last_comment` (`last_comment`),
  KEY `blog_id` (`blog_id`),
  KEY `comments` (`comments`,`status`),
  KEY `list_post` (`status`,`blog_id`,`datepost`),
  KEY `datepost` (`datepost`,`status`),
  KEY `pin_n` (`pin_n`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=147 ;

--
-- Дамп данных таблицы `blog_posts`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_posts_visits_map`
--

CREATE TABLE IF NOT EXISTS `blog_posts_visits_map` (
  `visit_id` int(10) unsigned NOT NULL DEFAULT '0',
  `item_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`visit_id`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `blog_posts_visits_map`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_posts_votes`
--

CREATE TABLE IF NOT EXISTS `blog_posts_votes` (
  `post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `vote` tinyint(4) NOT NULL DEFAULT '0',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`post_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

--
-- Дамп данных таблицы `blog_posts_votes`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_sources`
--

CREATE TABLE IF NOT EXISTS `blog_sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `search` varchar(255) NOT NULL DEFAULT '',
  `search_engine` varchar(20) NOT NULL DEFAULT '',
  `total_visits` int(10) unsigned NOT NULL DEFAULT '0',
  `first_visit` int(10) unsigned NOT NULL DEFAULT '0',
  `last_visit` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `blog_sources`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_tags`
--

CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0',
  `used` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `blog_tags`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_tags_map`
--

CREATE TABLE IF NOT EXISTS `blog_tags_map` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `tag_id` int(10) unsigned NOT NULL DEFAULT '0',
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `filter_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`,`tag_id`),
  KEY `article` (`entry_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED;

--
-- Дамп данных таблицы `blog_tags_map`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_visits`
--

CREATE TABLE IF NOT EXISTS `blog_visits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) unsigned NOT NULL DEFAULT '0',
  `visitor_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  `post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `blog_id` int(10) unsigned NOT NULL DEFAULT '0',
  `datevisit` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=FIXED AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `blog_visits`
--


-- --------------------------------------------------------

--
-- Структура таблицы `blog_visits_map`
--

CREATE TABLE IF NOT EXISTS `blog_visits_map` (
  `visit_id` int(10) unsigned NOT NULL DEFAULT '0',
  `item_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`visit_id`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `blog_visits_map`
--


-- --------------------------------------------------------

--
-- Структура таблицы `feedbacks`
--

CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `firm` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `answer` text NOT NULL,
  `answered` int(11) NOT NULL,
  `ip` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `feedbacks`
--


-- --------------------------------------------------------

--
-- Структура таблицы `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(50) NOT NULL DEFAULT '',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  `last_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `kws` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL,
  `text` longtext NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `comments` int(11) NOT NULL DEFAULT '0',
  `last_comment` int(11) NOT NULL DEFAULT '0',
  `last_comment_uid` int(11) NOT NULL DEFAULT '0',
  `allow_comment` int(11) NOT NULL DEFAULT '0',
  `ordr` int(11) NOT NULL DEFAULT '0',
  `show_in_menu` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=66 ;

--
-- Дамп данных таблицы `pages`
--

INSERT INTO `pages` (`id`, `url`, `dateadd`, `last_modified`, `title`, `kws`, `description`, `text`, `parent_id`, `comments`, `last_comment`, `last_comment_uid`, `allow_comment`, `ordr`, `show_in_menu`) VALUES
(62, 'company', 1282048052, 1282048052, 'О компании', '', '', '<p>О компании, трулюлю</p>', 0, 0, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `pages_comments`
--

CREATE TABLE IF NOT EXISTS `pages_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  `level` int(11) NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `blocked` tinyint(4) NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `approved` int(10) unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `sort` (`sort`),
  KEY `level` (`level`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `pages_comments`
--


-- --------------------------------------------------------

--
-- Структура таблицы `pages_comments_t`
--

CREATE TABLE IF NOT EXISTS `pages_comments_t` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `viewed` int(10) unsigned NOT NULL DEFAULT '0',
  `viewed_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `pages_comments_t`
--


-- --------------------------------------------------------

--
-- Структура таблицы `pages_items`
--

CREATE TABLE IF NOT EXISTS `pages_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section` varchar(30) NOT NULL DEFAULT '',
  `item_id` varchar(30) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `last_changed` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `section` (`section`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `pages_items`
--


-- --------------------------------------------------------

--
-- Структура таблицы `photos`
--

CREATE TABLE IF NOT EXISTS `photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(10) unsigned NOT NULL,
  `dateadd` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=56 ;

--
-- Дамп данных таблицы `photos`
--


-- --------------------------------------------------------

--
-- Структура таблицы `photo_albums`
--

CREATE TABLE IF NOT EXISTS `photo_albums` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ordr` int(11) NOT NULL,
  `dateadd` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

--
-- Дамп данных таблицы `photo_albums`
--


-- --------------------------------------------------------

--
-- Структура таблицы `ref_landings`
--

CREATE TABLE IF NOT EXISTS `ref_landings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL DEFAULT '',
  `lands_count` int(10) unsigned NOT NULL DEFAULT '0',
  `first_land` int(10) unsigned NOT NULL DEFAULT '0',
  `last_land` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uri` (`uri`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=197 ;

--
-- Дамп данных таблицы `ref_landings`
--


-- --------------------------------------------------------

--
-- Структура таблицы `ref_sources`
--

CREATE TABLE IF NOT EXISTS `ref_sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `search` varchar(255) NOT NULL DEFAULT '',
  `search_engine` varchar(20) NOT NULL DEFAULT '',
  `total_visits` int(10) unsigned NOT NULL DEFAULT '0',
  `first_visit` int(10) unsigned NOT NULL DEFAULT '0',
  `last_visit` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1390 ;

--
-- Дамп данных таблицы `ref_sources`
--


-- --------------------------------------------------------

--
-- Структура таблицы `ref_visits`
--

CREATE TABLE IF NOT EXISTS `ref_visits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) unsigned NOT NULL DEFAULT '0',
  `visitor_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  `datevisit` date NOT NULL DEFAULT '0000-00-00',
  `ip` int(11) unsigned NOT NULL DEFAULT '0',
  `visits` int(10) unsigned NOT NULL DEFAULT '1',
  `land_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `visitor_ip` (`source_id`,`datevisit`,`ip`,`land_id`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1837 ;

--
-- Дамп данных таблицы `ref_visits`
--


-- --------------------------------------------------------

--
-- Структура таблицы `site_settings`
--

CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `int_value` int(11) NOT NULL DEFAULT '0',
  `type` enum('text','string','int','bool') NOT NULL DEFAULT 'text',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `site_settings`
--

INSERT INTO `site_settings` (`id`, `name`, `value`, `int_value`, `type`) VALUES
('analytics_code', 'Код Гугл Аналитикс', '', 0, 'text'),
('default_title', 'Заголовок сайта по умолчанию', 'Новый сайт', 0, 'string'),
('site_head_area', 'теги в META-секции', '', 0, 'text'),
('admin_email', 'Инфо-емайл адрес', '', 0, 'string'),
('default_descr', 'Дескрипшн для главной', '', 0, 'string'),
('default_kws', 'Кейвордсы для главной', '', 0, 'string'),
('counters', 'Счетчики', '', 0, 'text'),
('default_keywords', 'Кейвордсы для главной', '', 0, 'string');

-- --------------------------------------------------------

--
-- Структура таблицы `stat_agents`
--

CREATE TABLE IF NOT EXISTS `stat_agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `stat_agents`
--


-- --------------------------------------------------------

--
-- Структура таблицы `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `datepost` int(10) unsigned NOT NULL DEFAULT '0',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0',
  `used` int(11) NOT NULL DEFAULT '0',
  `is_main` int(11) NOT NULL,
  `ordr` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `ordr` (`ordr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=208 ;

--
-- Дамп данных таблицы `tags`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(50) NOT NULL DEFAULT '',
  `full_name` varchar(150) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL DEFAULT '',
  `email_confirmed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `datereg` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` int(11) NOT NULL DEFAULT '0',
  `userpic` varchar(255) NOT NULL DEFAULT '',
  `rights` text NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `lastpage` varchar(250) NOT NULL DEFAULT '',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `last_online` int(10) unsigned NOT NULL DEFAULT '0',
  `salt` varchar(10) NOT NULL DEFAULT '',
  `invite_id` int(10) unsigned NOT NULL DEFAULT '0',
  `invite_from` int(10) unsigned NOT NULL DEFAULT '0',
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gender` enum('u','m','f') NOT NULL DEFAULT 'u',
  `has_mainpic` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rating` int(11) NOT NULL,
  `rating_count` int(10) unsigned NOT NULL,
  `int_rating` int(11) NOT NULL,
  `int_rating_count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `nick`, `full_name`, `password`, `email`, `email_confirmed`, `datereg`, `last_login`, `ip`, `userpic`, `rights`, `deleted`, `lastpage`, `hits`, `last_online`, `salt`, `invite_id`, `invite_from`, `agent_id`, `gender`, `has_mainpic`, `rating`, `rating_count`, `int_rating`, `int_rating_count`) VALUES
(1, 'admin', 'admin', '5c7e0052f8eb830c8bc623ebebaefac3', 'admin@newproject.com', 0, 1297161940, 1297162157, 1270, '', 'a:2:{s:5:"admin";s:1:"1";s:9:"allow_all";s:2:"on";}', 0, '/admin/module/administrative/admins/', 1, 1297162157, '', 0, 0, 0, 'u', 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `users_info`
--

CREATE TABLE IF NOT EXISTS `users_info` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `birthday` date NOT NULL DEFAULT '0000-00-00',
  `about` text NOT NULL,
  `email` varchar(50) NOT NULL DEFAULT '',
  `icq` int(10) unsigned NOT NULL DEFAULT '0',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `skype` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users_info`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users_temp`
--

CREATE TABLE IF NOT EXISTS `users_temp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pass_key` varchar(32) NOT NULL,
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  `last_update` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `users_temp`
--


-- --------------------------------------------------------

--
-- Структура таблицы `vote_answers`
--

CREATE TABLE IF NOT EXISTS `vote_answers` (
  `question_id` int(10) unsigned NOT NULL DEFAULT '0',
  `answer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_hash` char(32) NOT NULL DEFAULT '',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`question_id`,`user_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `vote_answers`
--


-- --------------------------------------------------------

--
-- Структура таблицы `vote_questions`
--

CREATE TABLE IF NOT EXISTS `vote_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `type` enum('radio','checkbox') NOT NULL DEFAULT 'radio',
  `min_versions` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `max_versions` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `answers` int(10) unsigned NOT NULL DEFAULT '0',
  `dateadd` int(10) unsigned NOT NULL DEFAULT '0',
  `datestart` int(10) unsigned NOT NULL DEFAULT '0',
  `datestop` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Дамп данных таблицы `vote_questions`
--


-- --------------------------------------------------------

--
-- Структура таблицы `vote_variants`
--

CREATE TABLE IF NOT EXISTS `vote_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `answers` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=37 ;

--
-- Дамп данных таблицы `vote_variants`
--

