<?php
    /*
     * class for Markov's chains realization
     * (c) Andrey Kravchuk dushik@gmail.com, 2006
     *
     * Typical usage:
     * $m = new Markov(2); // 2 - markov chain length, depends on source
     *                     // file size, 2 for small files, 3-4 for large ones
     * $m->initFromFile("somefile.txt") or die("shit happens.");
     * $bullshit = $m->generate(2000); // generates bullshit 2000 chars long.
     * $m->initFromString(file_get_contents("somefile.txt")) or die("shit happens.");
     * $bullshit = $m->generate(100, MARKOV_OPT_WORDS); // generates bullshit 100 words long.
     */

    define("MARKOV_OPT_CHARACTERS", 1); // option: character limit for generate()
    define("MARKOV_OPT_WORDS",      2); // option: word limit for generate()
    define("MARKOV_DEFAULT_K",      3); // default Markov chain length
    define("MARKOV_TIME_LIMIT",     120); // time limit for initFromFile()

    define("MARKOV_MAX_RECURSION_LEVEL",     20);

    class Markov
    {
        var $k = MARKOV_DEFAULT_K;
        var $k_sets = array();
        var $split_method = MARKOV_OPT_WORDS;

        function Markov($k, $split_method = MARKOV_OPT_WORDS)
        {
            $this->k = $k;
            $this->split_method = $split_method;
        }

        /*
         * inits class' Markov k-sets with a text from a text file.
         *
         * returns true on success, false on failure
         */
        function initFromFile($filename)
        {
            set_time_limit(MARKOV_TIME_LIMIT);

            if(!is_readable($filename)) return false;

            $fc = file_get_contents($filename);
            $this->initFromString($fc);

            return true;
        }
        
        /*
         * inits class' Markov k-sets with a text from a string.
         *
         * returns true on success, false on failure
         */
        function initFromString($str)
        {
            if(strlen($str) <= $this->k) return false;
            $this->k_sets = array();
            $set = array();
            
            if($this->split_method == MARKOV_OPT_WORDS)
            {
                $words = preg_split('/\s+/', trim($str));
                if(count($words) <= $this->k) return false;
                foreach($words as $w)
                {
                    $this->_addToSets($set, $w);
                }
            }
            else
            {                
                for($i=0; $i<strlen($str); $i++)
                {
                    $this->_addToSets($set, substr($str, $i, 1));
                }
            }
            
            return true;
        }
        
        function _addToSets(&$set, $w)
        {
	        $set[] = $w;
            if(count($set) == $this->k)
            {
                $key = "";
                for($i=0; $i<$this->k - 1; $i++)
                {
                    $key .= "[\$set[$i]]";
                }
                eval("\$this->k_sets{$key}[] = \$set[$i];");
                array_shift($set);
            }
        }

        /*
         * you must re-init the class after calling setK()
         */
        function setK($k) { $this->k = $k; $this->k_sets = array(); }
        
        /*
         * generates random word $length characters long
         * (available only if split_method is MARKOV_OPT_CHARACTERS
         */
        function getWord($length)
        {
            if($this->split_method != MARKOV_OPT_CHARACTERS) return false;
            $res = "";
            $set = array();
            while(strlen($res) < $length)
            {
                if(count($set) == $this->k)
                {
                    $word = array_shift($set);
                    if(preg_match('/[\s\.,:\?!;"]/', $word))
                    {
                        $res = "";
                        continue;
                    }
                    
                    $res .= $word;
                }
                
                if(strlen($res) == $length) break;

                if(count($set)) $element =& $this->k_sets[$set[0]];
                else $element = null;
                foreach($set as $i => $word)
                {
                    if(isset($element[$word])) $element =& $element[$word];
                }
                if(is_array($element))
                {
                    $word = $this->random_key($element);
                    if(is_array($element[$word])) $set[] = $word;
                    else $set[] = $this->random_value($element);
                }
                else $set[] = $this->random_key($this->k_sets);

            }
            return $res;
        }

        /*
         * generates Markov's string, max $how_much long,
         * in words (if $how==MARKOV_OPT_WORDS)
         * or in characters (if $how==MARKOV_OPT_CHARACTERS, default)
         */
        function generate($how_much, $how=MARKOV_OPT_CHARACTERS, $sentences=false, $recursion_level=0)
        {
            $res = "";
            $n_words = 0;
            $set = array();
            $sentence_started = false;
            while(($how == MARKOV_OPT_CHARACTERS && (strlen($res) < $how_much)) ||
                  ($how == MARKOV_OPT_WORDS && ($n_words < $how_much)))
            {
                if(count($set) == $this->k)
                {
                    $res .= ($word = array_shift($set));
                    if($this->split_method == MARKOV_OPT_WORDS)
                    { 
                        $res .= " ";
                        $n_words++;
                    }
                    elseif(preg_match('/[\s\.,:\?!;"]/', $word))
                    {
                        $n_words++;
                    }
                    if($sentences && !$sentence_started && preg_match('/\.$/', $word))
                    {
                        $sentence_started = true;
                        $res = "";
                        $n_words = 0;
                    }
                }

                if(count($set)) $element =& $this->k_sets[$set[0]];
                else $element = null;
                foreach($set as $i => $word)
                {
                    if(isset($element[$word])) $element =& $element[$word];
                }
                if(is_array($element))
                {
                    $word = $this->random_key($element);
                    if(is_array($element[$word])) $set[] = $word;
                    else $set[] = $this->random_value($element);
                }
                else $set[] = $this->random_key($this->k_sets);

            }
            $res = rtrim($res);
            if($sentences)
            {
                $res = preg_replace('/\.[^\.]+$/', '.', $res);
                if(!preg_match('/\.$/', $res) && ($recursion_level < MARKOV_MAX_RECURSION_LEVEL))
                    return($this->generate($how_much, $how, $sentences, $recursion_level+1));
            }
            return $res;
        }

        /*
         * helper function, returns random array key
         */
        function random_key(&$array)
        {
            $rand = mt_rand(0, count($array) - 1);
            $i = 0;
            foreach($array as $key => $value) if($i++ == $rand) return $key;
        }
        /*
         * helper function, returns random array value
         */
        function random_value(&$array)
        {
            $rand = mt_rand(0, count($array) - 1);
            $i = 0;
            foreach($array as $key => $value) if($i++ == $rand) return $value;
        }
    }

