<?php
    /*
     * cache.class.php
     *
     * A class for caching & locks.
     *
     * Transparently uses memcache when available, database when not.
     *
     * Must be initialized with PEAR::DB database object to use the
     * database. Also, you'll need a table like
     *
     * CREATE TABLE cache(`cache_key` VARCHAR(64) NOT NULL PRIMARY KEY,
     *  `cache_value` LONGBLOB,
     *  `cache_expires` INT NOT NULL DEFAULT 0,
     *  KEY `cache_expires` (`cache_expires`)
     * );
     *
     * Database use is not recommended, of course, if you're using it
     * for something more than locks, so please install pecl-memcache,
     * It can be downloaded from http://pecl.php.net/package/memcache
     *
     * More info about memcache: http://www.danga.com/memcached/
     *
     * $Id: cache.inc.php,v 1.1 2007/11/18 12:40:44 steel Exp $
     *
     * TODO: maybe make it work through shm_
     *
     */

    define('CACHE_MEMCACHE_HOST', '127.0.0.1');
    define('CACHE_MEMCACHE_PORT', 11211);
    define('CACHE_TABLE', 'cache');
    define('LOCK_TIMEOUT', 60); // default lock timeout (seconds)
    define('LOCK_ACQUIRE_TIMEOUT', 600); // default lock acquire timeout (seconds)
    define('DB_LOCK_CHECK_PERIOD', 5000); // database lock check period (milliseconds)
    define('MEMCACHE_LOCK_CHECK_PERIOD', 100); // memcache lock check period (milliseconds)
    define('MEMCACHE_COMPRESSION', false);

    class Cache
    {
        var $memcache = null;
        var $db = null;
        var $last_error = '';

        // init
        function Cache(&$db)
        {
            $this->db =& $db;

            // connect to memcache daemon
            if(class_exists('Memcache') && USE_MEMCACHE)
            {
                $this->memcache = new Memcache;
                if(!@$this->memcache->connect(CACHE_MEMCACHE_HOST, CACHE_MEMCACHE_PORT))
                {
                    $this->memcache = null;
                    $this->last_error = "Warning: memcached connection failed";
                }
            }
        }

        function lastError() { return $this->last_error; }

        // put an $item to cache under key $key for $expire seconds.
        // expire=0 means "never expires" but keep in mind
        // that memcache doesn't keep items older than
        // 2592000 seconds (30 days)
        // so does this class.
        //
        // Also consider that not all PHP items can be cached.
        // Item must be serializable, i.e. database connections or
        // filesystem descriptors can not be cached.
        //
        // returns true on success, false on error (also sets
        // last_error of course).
        //
        // TODO: make an internal cleanup for cache table,
        // database item compression maybe
        //
        function Set($key, $item, $use_compression=false, $expire=0)
        {
            $this->last_error = '';

            if($this->memcache)
            {
                $res = $this->memcache->Set($key, $item, $use_compression?MEMCACHE_COMPRESSION:0, $expire);
                if(!$res)
                {
                    $this->last_error = "memcache set() failed";
                    return false;
                }
                return true;
            }
            elseif($this->db)
            {
                $item_serialized = serialize($item);
                if(($expire <= 0) || ($expire > 2592000)) $expire = 2592000;

                $this->db->Query("LOCK TABLES ".CACHE_TABLE." WRITE");
                $check = $this->db->selectCell("SELECT count(*) FROM ".CACHE_TABLE.
                    " WHERE cache_key='".addcslashes($key, "'")."'");
                if($check)
                {
                    $res = $this->db->Query("UPDATE ".CACHE_TABLE.
                        " SET cache_value='".addcslashes($item_serialized, "'")."',".
                        " cache_expires=".(time() + $expire).
                        " WHERE cache_key='".addcslashes($key, "'")."'");
                }
                else
                {
                    $res = $this->db->Query("INSERT INTO ".CACHE_TABLE."(cache_key, cache_value, cache_expires)
                        VALUES('".addcslashes($key, "'")."', '".addcslashes($item_serialized, "'")."',
                        ".(time() + $expire).")");
                }
                $this->db->Query("UNLOCK TABLES");


                return true;
            }
            else
            {
                $this->last_error = "Neither memcache, nor db is active now.";
                return false;
            }
        }

        // returns an item from the cache
        // or false on cache miss or database error
        function Get($key)
        {
            $this->last_error = '';

            if($this->memcache)
            {
                $res =& $this->memcache->Get($key);
                return $res;
            }
            elseif($this->db)
            {
                $this->db->Query("LOCK TABLES ".CACHE_TABLE." WRITE");
                $sql = "SELECT cache_value FROM ".CACHE_TABLE.
                    " WHERE cache_key='".addcslashes($key, "'")."' AND cache_expires>".time();
                $row = $this->db->selectCell($sql);
                if(!$row) return false;
                $this->db->Query("UNLOCK TABLES");

                $result = unserialize($row); // unserialize() itself returns false on error
                if($result === false)
                {
                    $this->last_error = "unserialize() failed.";
                }
                return $result;
            }
            else
            {
                $this->last_error = "Neither memcache, nor db is active now.";
                return false;
            }
        }

        // deletes an item from the cache
        // returns true on success, false on error
        function Delete($key)
        {
            global $db;
            $this->last_error = '';

            if($this->memcache)
            {
                return $this->memcache->Delete($key);
            }
            elseif($this->db)
            {
                $this->db->Query("LOCK TABLES ".CACHE_TABLE." WRITE");
                $res = $db->Query("DELETE FROM ".CACHE_TABLE.
                    " WHERE cache_key='".addcslashes($key, "'")."'");
                $this->db->Query("UNLOCK TABLES");


                return true;
            }
            else
            {
                $this->last_error = "Neither memcache, nor db is active now.";
                return false;
            }
        }

        // tries to acquire a lock named $lock_name.
        //
        // if $blocking is true, setLock() locks script
        // execution until lock is released, then returns true.
        //
        // if $blocking is false (default), it returns false
        // if lock is already set, and sets the lock and returns true
        // otherwize.
        //
        // lock is set to $expire seconds, after that it is released
        // automatically.
        //
        // returns false and sets last_error on any error
        //
        // TODO: make the same function using semaphores
        //
        function setLock($lock_name, $blocking=false, $expire=LOCK_TIMEOUT)
        {
            $this->last_error = '';

            $lock = $this->Get($lock_name);

            // lock is free on can't be acquired
            if($lock === false)
            {
                if($this->last_error) return false;
                $this->Set($lock_name, 1, false, $expire);
                return true;
            }

            if($blocking)
            {
                list($usec, $sec) = explode(' ', microtime());
                $time_start = (float)$sec + (float)$usec;
                do
                {
                    usleep(($this->memcache?MEMCACHE_LOCK_CHECK_PERIOD:DB_LOCK_CHECK_PERIOD)*1000);
                    $lock = $this->Get($lock_name);
                    list($usec, $sec) = explode(' ', microtime());
                    $time = (float)$sec + (float)$usec;
                }
                while( ($lock === false) && ($time - $time_start < LOCK_ACQUIRE_TIMEOUT));

                if( $lock === false ) // lock acquire timeout
                {
                    $this->last_error = "Lock acquire timeout";
                    return false;
                }
                $this->Set($lock_name, 1, false, $expire);
                return true;
            }

            return false;
        }

        // releases set lock
        // returns false if no such lock had been set or other error,
        // true on success
        function releaseLock($lock_name)
        {
            $this->last_error = '';

            return $this->Delete($lock_name);
        }
    }
