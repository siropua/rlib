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
                 PRIMARY KEY  (`id`),                                
                 KEY `entry_id` (`entry_id`),                        
                 KEY `sort` (`sort`),                                
                 KEY `level` (`level`),                              
                 FULLTEXT KEY `text` (`text`)                        
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			   
CREATE TABLE `blog_comments_t` (                      
                   `user_id` int(10) unsigned NOT NULL default '0',    
                   `entry_id` int(10) unsigned NOT NULL default '0',   
                   `viewed` int(10) unsigned NOT NULL default '0',     
                   `viewed_id` int(10) unsigned NOT NULL default '0',  
                   PRIMARY KEY  (`user_id`,`entry_id`)                 
                 ) ENGINE=MyISAM DEFAULT CHARSET=utf8      ;

CREATE TABLE `blog_favorites` (                                                     
                  `user_id` int(10) unsigned NOT NULL default '0',                                  
                  `post_id` int(10) unsigned NOT NULL default '0',                                  
                  `dateadd` int(10) unsigned NOT NULL default '0',                                  
                  PRIMARY KEY  (`user_id`,`post_id`)                                                
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				
CREATE TABLE `blog_posts` (                                                                   
              `id` bigint(20) unsigned NOT NULL auto_increment,                                           
              `url` varchar(255) NOT NULL default '',                                                     
              `owner_id` bigint(20) unsigned NOT NULL default '0',                                        
              `blog_id` int(11) NOT NULL default '0',                                                     
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
              `rating_total` int(11) NOT NULL default '0',                                                
              `rating_count` int(11) NOT NULL default '0',                                                
              `source_url` varchar(255) NOT NULL default '',                                              
              `status` enum('posted','deferred','in_moderation','cancelled','deleted') default 'posted',  
              `when_post` int(10) unsigned NOT NULL default '0',                                          
              `has_mainpic` tinyint(3) unsigned NOT NULL default '0',                                     
              `mainpic` varchar(255) NOT NULL default '',                                                 
              `episode` varchar(10) NOT NULL default '',                                                  
              PRIMARY KEY  (`id`),                                                                        
              KEY `visible` (`visible`),                                                                  
              KEY `status` (`status`),                                                                    
              KEY `last_comment` (`last_comment`),                                                        
              KEY `blog_id` (`blog_id`),                                                                  
              KEY `comments` (`comments`,`status`),                                                       
              KEY `list_post` (`status`,`blog_id`,`datepost`),                                            
              KEY `datepost` (`datepost`,`status`)                                                        
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			
CREATE TABLE `blog_posts_votes` (                                                   
                    `post_id` int(10) unsigned NOT NULL default '0',                                  
                    `user_id` int(10) unsigned NOT NULL default '0',                                  
                    `vote` int(10) unsigned NOT NULL default '0',                                     
                    `dateadd` int(10) unsigned NOT NULL default '0',                                  
                    PRIMARY KEY  (`post_id`,`user_id`)                                                
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
				  
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
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8    ;

CREATE TABLE `blog_tags` (                             
             `id` int(10) unsigned NOT NULL auto_increment,       
             `name` varchar(255) NOT NULL default '',             
             `url` varchar(255) NOT NULL default '',              
             `datepost` int(10) unsigned NOT NULL default '0',    
             `creator_id` int(10) unsigned NOT NULL default '0',  
             `used` int(11) NOT NULL default '0',                 
             PRIMARY KEY  (`id`),                                 
             UNIQUE KEY `url` (`url`)                             
           ) ENGINE=MyISAM DEFAULT CHARSET=utf8  ;
		   
CREATE TABLE `blog_tags_map` (                                                      
                 `entry_id` int(10) unsigned NOT NULL default '0',                                 
                 `tag_id` int(10) unsigned NOT NULL default '0',                                   
                 `datepost` int(10) unsigned NOT NULL default '0',                                 
                 `filter_id` int(10) unsigned NOT NULL default '0',                                
                 PRIMARY KEY  (`entry_id`,`tag_id`),                                               
                 KEY `article` (`entry_id`),                                                       
                 KEY `tag_id` (`tag_id`)                                                           
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			   
			   
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
             ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
			 
			 
CREATE TABLE `blogs` (                              
          `id` int(10) unsigned NOT NULL auto_increment,    
          `url` varchar(50) NOT NULL default '',            
          `name` varchar(100) NOT NULL default '',          
          `description` text NOT NULL,                      
          `custom_css` varchar(30) NOT NULL default '',     
          `feedburner` varchar(255) NOT NULL default '',    
          `list_id` int(10) unsigned NOT NULL default '0',  
          `only_domain` varchar(255) NOT NULL default '',   
          `posts` int(10) unsigned NOT NULL default '0',    
          PRIMARY KEY  (`id`),                              
          UNIQUE KEY `url` (`url`)                          
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8   ;
		
		
			
			
			
			
