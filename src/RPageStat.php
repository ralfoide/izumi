<?php
// vim: set tabstop=4 shiftwidth=4: //
//************************************************************************
/*
	$Id$

	Copyright 2004, Raphael MOLL.

	This file is part of Izumi.

	Izumi is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	Izumi is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Izumi; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
//************************************************************************


//-----------------------------------------------------------------------

define("DB_HANDLER",	"db4");
define("DB_REFERAL",	"_referal.");
define("DB_KEY_TOTAL",	"_total_");

define("DB_KEY_AGENT_COUNT",	"_agname_");
define("DB_KEY_AGENT_LAST",		"_aglast_");

// Agents of interest: db/display_name => ('u' => url, 'p' => match pattern)
$izu_rps_agents_table = array( "Google"		=> array('u' => "http://www.google.com", 	 'p' => "/[gG]ooglebot/"),
							   "AllTheWeb"	=> array('u' => "http://www.alltheweb.com/", 'p' => "/alltheweb/"),
							   "Feedster"	=> array('u' => "http://www.feedster.com/",	 'p' => "/Feedster/"),
							   "Yahoo!"		=> array('u' => "http://www.yahoo.com/",	 'p' => "/Yahoo![ ]*Slurp/"),
							   "Alexa"		=> array('u' => "http://www.alexa.com/",	 'p' => "/ia_archiver/"),
							   "Teoma"		=> array('u' => "http://www.teoma.com/",	 'p' => "/Teoma/"),
							   "MSN"		=> array('u' => "http://search.msn.com/",	 'p' => "/msnbot/")
							 );
		

//-----------------------------------------------------------------------


//*************
class RPageStat
//*************
{
	var $mPath;
	var $mDbId;
	var	$mTotal;
	var $mIp;
	var	$mIpCount;
	var $mAgent;
	var $mAgentStat;
	var	$mIpExcluded;
	var $mSearchAgent;


	//************************
	function RPageStat(&$path)
	//************************
	{
		$this->mDbId		= NULL;
		$this->mTotal		= NULL;
		$this->mIpCount		= NULL;
		$this->mIp			= izu_get($_SERVER, 'REMOTE_ADDR');
		$this->mAgent		= izu_get($_SERVER, 'HTTP_USER_AGENT');
		$this->mAgentStat	= array();
		$this->mIpExcluded	= FALSE;
		$this->mSearchAgent	= FALSE;


		// DEBUG -- override the user agent
		// $this->mAgent = izu_get($_GET, 'agent', $this->mAgent);


		// check if this IP should be filtered out
		global $pref_page_stats_exclude;
		if (is_string($pref_page_stats_exclude) && $pref_page_stats_exclude != "")
			if (preg_match($pref_page_stats_exclude, $this->mIp, $matches) == 1)
			{
				$this->mIp = NULL;
				$this->mIpExcluded = TRUE;
			}
		
		// get initial path -- it is up to caller to use izu_decode_argument()
		// and provide a clean RPagePath instance
		$this->mPath = $path;
	}


	//****************
	function Release()
	//****************
	// Must be called explicitly to release resources
	{
		$this->closeDb();
	}


	//*********************
	function IsIpExcluded()
	//*********************
	// Set when some special local IPs are excluded (cf prefs setting $pref_page_stats_exclude)
	{
		return $this->mIpExcluded;
	}


	//**********************
	function IsSearchAgent()
	//**********************
	// Set when user agent matches one of the search agents listed above
	{
		return $this->mSearchAgent;
	}

	

	//*******************************
	function Load($keep_open = FALSE)
	//*******************************
	{
		global $izu_rps_agents_table;
		
		if (!$this->openDb())
			return FALSE;
		
		if ($this->mDbId != NULL)
		{
			// load total
			$this->mTotal   = $this->getDbValue(DB_KEY_TOTAL);

			if (!isset($this->mTotal) || !is_string($this->mTotal))
				$this->mTotal = 0;

			// load counts for this ip
			$this->mIpCount = $this->getDbValue($this->mIp);

			if (!isset($this->mIpCount) || !is_string($this->mIpCount))
				$this->mIpCount = 0;
				
			// load agent stats
			foreach($izu_rps_agents_table as $key => $info)
			{
				$count = $this->getDbValue(DB_KEY_AGENT_COUNT . $key); 
				$last  = $this->getDbValue(DB_KEY_AGENT_LAST . $key); 
				
				if (   is_string($count) && $count != ""
					&& is_string($last)  && $last  != "")
				{
					$this->mAgentStat[$key] = array("c" => $count, "l" => $last);
				}
			}
		}
		
		if (!$keep_open)
			$this->closeDb();

		return TRUE;
	}


	//**************************************
	function AddRemoteIp($keep_open = FALSE)
	//**************************************
	{
		global $izu_rps_agents_table;

		$this->Load(TRUE);

		if ($this->mDbId != NULL)
		{
			// ip
			
			$ip = $this->mIp;
			
			if ($ip != NULL && is_string($ip))
			{
				// increment the total access count
				
				$count = $this->mTotal;
				$count++;

				$this->setDbValue(DB_KEY_TOTAL, $count);
				$this->mTotal = $count;
				
				// increment the count for this ip

				$count = $this->mIpCount;
				$count++;

				$this->setDbValue($ip, $count);
				$this->mIpCount = $count;
			}
			
			// agents
			
			$agent = $this->mAgent;
			
			if ($agent != NULL && is_string($agent))
			{
				foreach($izu_rps_agents_table as $key => $info)
				{
					if (array_key_exists('p', $info) && preg_match($info['p'], $agent) == 1)
					{
						$count = 0;
						if (array_key_exists($key, $this->mAgentStat))
							$count = $this->mAgentStat[$key]['c'];
						
						$count = ((int)$count)+1;
						$last = time();
						
						$this->setDbValue(DB_KEY_AGENT_COUNT . $key, $count);
						$this->setDbValue(DB_KEY_AGENT_LAST  . $key, $last ); 

						$this->mAgentStat[$key] = array('c' => $count, 'l' => $last);
						
						// Make note that this is a search agent
						$this->mSearchAgent = TRUE;
					}
				}
			}
			
		}

		if (!$keep_open)
			$this->closeDb();
	}


	//****************
	function GetStat()
	//****************
	{
		global $izu_rps_agents_table;
		
		
		$ip = $this->mIp;
		$t  = $this->mTotal;
		$c  = $this->mIpCount;
		
		
		if ($t == 1)
			$p .= $t . " access";
		else if ($t > 1)
			$p .= $t . " accesses";

		if ($ip != NULL)
		{
			if ($c == 1)
				$p .= ", " . $c . " access from " . $ip;
			else if ($c > 1)
				$p .= ", " . $c . " accesses from " . $ip;
		}

		foreach($this->mAgentStat as $key => $stat)
		{
			$name = $key;
			if (array_key_exists($key, $izu_rps_agents_table))
				$name = "<a href=\"" . $izu_rps_agents_table[$key]['u'] . "\">" . $key . "</a>";
			
			$s = "Visited [count] times by [name], last [time]";
			$t = str_replace('[name]', $name, $s);
			$t = str_replace('[count]', (int)($stat['c']), $t);
			$t = str_replace('[time]', strftime("%Y/%m/%d %H:%M", $stat['l']), $t);
			$p .= "\n<br>" . $t;
			
		}

		return $p;
	}


	//-----------------------------------------------------------------------
	// private methods


	//***************
	function openDb()
	//***************
	{
		if ($this->mPath == NULL)
			return FALSE;

		// RM 20040314: make it safe to open twice
		if ($this->mDbId == NULL && $this->mPath->PageExists())
		{
			$file = $this->mPath->GetCachePath(FALSE, FALSE, FALSE, DB_REFERAL . DB_HANDLER);
			
			if (!izu_is_file($file))
			{
				// RM 20040314: if files does not exist, try to create the database first.
				// http://www.php.net/manual/en/function.dba-open.php indicates that the "c" mode is broken
				// or even worse may truncate a DB! So instead let's use "n" explicitely

				$this->mDbId = @dba_open($file, "n", DB_HANDLER);
			}
			else
			{
				// otherwise open for writing (w) with a wait lock (d)
				
		        $this->mDbId = @dba_open($file, "wd", DB_HANDLER);
		    }

			// RM 20040911 added a debug comment when it works, so that I can check it
			// just by looking at the source.
			// RM 20040926 only emit debug output if http headers have already been sent
			if (headers_sent())
			{
				if ($this->mDbId == NULL)
					echo "\n<b> (Internal error: stat db not available) </b>\n";
				else
					echo "\n<!-- DEBUG: stat db available -->\n";
			}
	    }
	    
	    return TRUE;
	}


	//****************
	function closeDb()
	//****************
	{
		if ($this->mDbId != NULL)
		{
			dba_sync($this->mDbId);
            dba_close($this->mDbId);
            
            $this->mDbId = NULL;
		}
	}
	
	
	//***********************
	function getDbValue($key)
	//***********************
	{
		if ($this->mDbId != NULL)
			return dba_fetch($key, $this->mDbId);

		return "";
	}
	
	
	//*****************************
	function setDbValue($key, $val)
	//*****************************
	{
		$result = false;

		if ($this->mDbId != NULL && $key != NULL && $val != NULL)
		{
		    if (dba_exists($key, $this->mDbId))
				$result = dba_replace($key, $val, $this->mDbId);
			else
				$result = dba_insert($key, $val, $this->mDbId);
		}

		return $result;
	}
	

} // end RPageStat

//-----------------------------------------------------------------------




//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.7  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.6  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.5  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.4  2004/11/22 19:03:20  ralf
//	Added Ask Jeeves/Teoma
//	
//	Revision 1.3  2004/09/26 19:33:14  ralf
//	Added MSN bot.
//	Added IsIpExcluded() and IsSearchAgent() methods.
//	
//	Revision 1.2  2004/05/09 19:06:10  ralf
//	Use url rewrite. Allow for not having index.php in the URL.
//	Added izu:image, #links, fixed some regexps. Log http-language-accept.
//	Added site license generated on every page.
//	Added izumi favicon. Fixed dba_open n vs c, using wait lock.
//	Support HTTP 304 Not Modified, ETag, Last-Modified headers and If-counterparts.
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//-------------------------------------------------------------
?>
