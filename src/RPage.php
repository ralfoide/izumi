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



//-----------------------------------------------------------------------


//*********
class RPage
//*********
{
	var $mPath;
	var	$mCache;
	var $mPageList;
	var $mRedirect;
	var $mTempBuffer;
	var $mFolderList;
	var $mNotModified;
	var $mAutoIgnoreKeywords;
	
	// stats


	//********************
	function RPage(&$path)
	//********************
	{
		$this->mAutoIgnoreKeywords = array();
		$this->mNotModified = FALSE;
		$this->mTempBuffer = NULL;
		$this->mRedirect = '';
		$this->mCache = '';

		// get initial path -- it is up to caller to use izu_decode_argument()
		// and provide a clean RPagePath instance
		$this->mPath = $path;
	}


	//*****************************
	function Prepare($title = NULL)
	//*****************************
	{
		global $display_title;
		global $display_page_title;
		global $html_content, $html_none;
		global $pref_title_name;			// RM 20040314

		// Setup titles
		
		if (!$title)
			$title = $html_content;
	
		if (is_string($pref_title_name) && $pref_title_name != '')
			$name = ": $pref_title_name - ";
		else
			$name = ' - ';
			
	
		if ($this->mPath->IsPage())
		{
			$pretty = $this->mPath->GetPrettyName();
		}
		else
		{
			$pretty = $this->mPath->GetPrettyName();
			if ($pretty == '')
				$pretty = $html_none;
		}

		$display_title      = $title        . $name . $pretty;
		$display_page_title = $html_content . $name . $pretty;


		// Handle HTTP Last-Modified/ETag and If-Modified-Since/If-None-Match
		// only continue if modified
		if (!$this->handleLastModified())
		{
			// Parse first line for specific headers (refuse, encoding, etc)
			// Check if the default encoding must be changed (needs to go in HTTP headers)
			// Check if izu:refuse is set, if it is abort processing
	
			if (!$this->parseEarlyTags())
				return FALSE;
		}


		return TRUE;
	}


	//****************
	function GetPath()
	//****************
	{
		return $this->mPath;
	}



	//*********************
	function BeginProcess()
	//*********************
	{
		$p = $this->processPage();
		
		if (!is_string($p))
			$p = $this->processDirectory();
		
		return $p;
	}


	//*******************
	function EndProcess()
	//*******************
	{
		if (is_string($this->mTempBuffer))
			$this->stopBuffering();
	}


	//**********************
	function IsNotModified()
	//**********************
	// Returns TRUE if the page is set as HTTP/1.x 304 Not Modified
	// in which case no output should be sent.
	{
		return $this->mNotModified;
	}
	
	
	//**********************
	function NeedsRedirect()
	//**********************
	// Returns TRUE if the page wants the request to be redirected
	{
		return ($this->mRedirect != '');
	}

	
	//********************
	function RedirectUrl()
	//********************
	{
		return $this->mRedirect;
	}

		
	//********************
	function DirHasPages()
	//********************
	// Returns TRUE if there are pages to display
	{
		return is_array($this->mPageList) && count($this->mPageList) > 0;
	}
	
	
	
	//**********************
	function DirHasFolders()
	//**********************
	// Returns TRUE if there are folders to display
	{
		return is_array($this->mFolderList) && count($this->mFolderList) > 0;
	}
	

	//******************************
	function DisplayDirectoryPages()
	//******************************
	// Returns TRUE if could display the directory, FALSE otherwise
	{
		// display pages list
	
		foreach($this->mPageList as $path)
		{
			$pretty = $path->GetPrettyName();
			$link   = $path->GetSelfUrl();
	
			echo "[&nbsp;] <a href=\"$link\">$pretty</a> <br>\n";
		}
	
		return TRUE;
	}
	
	
	//********************************
	function DisplayDirectoryFolders()
	//********************************
	// Returns TRUE if could display the directory, FALSE otherwise
	{
		// display directories list
		
		foreach($this->mFolderList as $path)
		{
			$pretty = $path->GetPrettyName();
			$link   = $path->GetSelfUrl();
	
			echo "[+] <a href=\"$link\">$pretty</a> <br>\n";
		}
	
	
		return TRUE;
	}


	//***************************
	function DisplayPageContent()
	//***************************
	{
		$this->renderFile($this->mPath->GetSourcePath());
	}


	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------
	// Main HTML rendering of the page

	//*******************
	function RenderPage()
	//*******************
	{
		global $display_title;
		global $display_page_title;
		global $color_title_bg;
		global $color_title_text;
		global $izu_stat;

		//-----------------------------------------------------------------------
		// HTML header and body
	
		izu_display_header($display_title, $this->ExtraHeaders());
		izu_display_body();
		
		// HTML top header
		
		echo "<center>\n";
		
		izu_display_section("<font size=\"+2\"><b> $display_page_title </b></font><br>",
							$color_title_bg,
							$color_title_text);
		
		echo "</center>\n";
		
		//-----------------------------------------------------------------------
		// Page content
		
		$p = $this->BeginProcess();
		
		if (is_string($p))
			include($p);
		
		$this->EndProcess();
		
		//-----------------------------------------------------------------------
		// HTML options/credits/gen-time information at bottom
		
		echo "<center><br>\n";
		
		izu_display_site_license();
	
		// RM 20040926 only display options if page stats are not active or if the
		// user agent is not a search bot
	
		if ($izu_stat == NULL || !$izu_stat->IsSearchAgent())
		{
			izu_display_options();
			izu_display_search();
			izu_display_related();
		}
		
		//-----------------------------------------------------------------------
		// RM 20041204 display credits instead of a link when visited by a search agent
		
		izu_display_credits($izu_stat->IsSearchAgent() ? "no-link" : -1);
		
		//-----------------------------------------------------------------------
		// Stat access
	
		// RM 20041204 do not display stats when visited by a search agent
		
		if ($izu_stat != NULL && !$izu_stat->IsSearchAgent())
		{
			$p = $izu_stat->GetStat();
		
			if (is_string($p))
			{
				izu_display_section("<b>Stats</b>");
				echo $p . '<p>';
			}
		}
		
		//-----------------------------------------------------------------------
		izu_display_footer();
		
		echo "</center>\n</body>\n</html>\n";
	}


	//*********************
	function ExtraHeaders()
	//*********************
	// Returns extra lines to insert in the HTML's <head>
	{
		global $color_section_bg;
		global $color_section_text;

		$section = ".izu-section { ";
		$section .= "  color: " . $color_section_text;
		$section .= "; background-color: " . $color_section_bg;
		$section .= "; padding: 1px 10px 1px 10px; ";
		$section .= "; font-weight: bold ";
		$section .= "; display: inline";
		$section .= "; border-bottom: 1px solid";
		$section .= "; border-top: none";
		$section .= "; border-left: none";
		$section .= "; border-right: none";
		$section .= "; }\n";

		$hN = "H1, H2, H3, H4, H5 { ";
		$hN .= "  color: " . $color_section_text;
		$hN .= "; background-color: " . $color_section_bg;
		$hN .= "; padding: 1px 10px 1px 10px; ";
		$hN .= "; border-bottom: 1px solid";
		$hN .= "; border-top: none";
		$hN .= "; border-left: none";
		$hN .= "; border-right: none";
		$hN .= ";  display: inline";
		$hN .= "; }\n";
		//$hN .= "H1 {";
		//$hN .= "  display: block";
		//$hN .= "; }\n";

		return "<style type='text/css'>\n $section $hN </style>\n";
	}



	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------
	// P R I V A T E   M E T H O D S
	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------


	//***************************
	function handleLastModified()
	//***************************
	// This function has three roles:
	// 3- Check if we're going to use a cache and if yes, cache the cache's modification timestamp
	// 2- When replying to a normal GET query, send Last-Modified and ETag headers.
	//	  The ETag header is simply the unix date timestamps (seconds since Jan 1st 1970)
	// 1- When replying to a GET query with If-None-Match, simply check if the ETag
	//    value. If the current modification date is lower or equal, the page is not modified.
	//    When replying to a GET query with If-Modified-Since, reply with 304 Not Modified
	//    if the cache is older or equal to the diff timestamp.
	//
	// Apparently If-Modified-Since is supported in HTTP 1.0 and HTTP 1.1.
	// ETag and If-None-Match seem to appear in HTTP 1.1.
	// I simply send both. The HTTP 1.0 browser will discard what they can't handle I guess.
	//
	// References:
	// - 304 Not Modified in HTTP 1.0:
	//   http://rfc.net/rfc1945.html#s9.3
	// - Last-Modified in HTTP 1.1:
	//   http://www.rfc.net/rfc2616.html#s14.29
	// - Time format: cf RFC 1123 ch5.2.14, which is like RFC 822 ch5 with 4-digit years
	//   http://www.rfc.net/rfc2616.html#s14.29
	//   http://rfc.net/rfc822.html#s5.
	{
		// Check if we'll be using the local cache, if any
		
		$this->mCache = $this->checkCache();

		// DEBUG
		// var_dump($this->mCache);

		// if there is a string, we have a cache
		
		if (is_string($this->mCache) && $this->mCache != '')
		{
			// current modification time stamp of the cache -- our Last-Modified time
			$date = $this->modifDate($this->mCache);

			$$this->mNotModified = FALSE;

			$ifnone  = izu_get($_SERVER, 'HTTP_IF_NONE_MATCH', '');
			
			if (is_string($ifnone) && $ifnone != '')
				$this->mNotModified = ($date <= (int)$ifnone);

			if (!$this->mNotModified)
			{
				$ifmodif = izu_get($_SERVER, 'HTTP_IF_MODIFIED_SINCE', '');

				if (is_string($ifnone) && $ifnone != '')
				{
					$ifmodif = strtotime($ifmodif);
					
					if ($ifmodif !== -1)
						$this->mNotModified = ($date <= (int)$ifmodif);
				}
			}

			if ($this->mNotModified)
			{
				// // get HTTP/1.0 or HTTP/1.1
				// // reply with 304 Not Modified
				header(izu_get($_SERVER, 'SERVER_PROTOCOL') . " 304 Not Modified");
				header('Status: 304 Not Modified');

				// don't continue
				return $this->mNotModified;
			}


			// send a Last-Modified & ETag header matching the cache modification date
			header("Last-Modified: " . date('r', $date));
			header("ETag: " . $date);
		}

		return $this->mNotModified;
	}


	//***********************
	function parseEarlyTags()
	//***********************
	// If the source file exists, check the very first line
	// for specific izumi tags.
	// Currently two tags are handled:
	// - "refuse", to block a page from being served.
	// - encoding string.
	// - detect blog pages
	// This needs to be performed in RPage::Prepare, before
	// the HTML encoding is send to the browser by izu_display_header.
	{
		// check if we're processing an existing page...

		$abs_dir = $this->mPath->GetSourcePath();

		// check file exists

		if (!is_string($abs_dir) || !izu_is_file($abs_dir))
			return FALSE;

		// open the file

		$file = @fopen($abs_dir, "rt");

		if (!$file)
			return FALSE;

		// get first line

		$temp = fgets($file, 1023);

		// close the file

		fclose($file);

		// process the line

		// check for page that shouldn't be served unless some very specific
		// query is provided
		if (preg_match('/\[izu:refuse(?::(.*?))\]/', $temp, $matches) == 1)
		{
			$valid = FALSE;

			// don't serve this page unless the optional condition
			// (in the form attr=value,attr=value) can be verified
			if ($matches[1] != NULL && is_string($matches[1]))
			{
				$conds = split(",", trim($matches[1]));
				foreach($conds as $c)
				{
					$v = split("=", trim($c));
					if (is_array($v) && count($v) >= 2)
					{
						$attr = $v[0];
						$val  = $v[1];
						if (is_string($attr) && is_string($val))
							$valid = (izu_get($_GET, $attr) == $val);
						if ($valid)
							break;
					}
				}
			}

			if (!$valid)
				return FALSE;
		}

		// check for pages that should be blocked for given IPs
		if (preg_match('/\[izu:ip-deny(?::(.*?))\]/', $temp, $matches) == 1)
		{
			$valid = TRUE;

			// don't serve this page if the current IP is listed
			// TBDL RM 20041213: use a mask as in 192.168.0.0/16
			if ($matches[1] != NULL && is_string($matches[1]))
			{
				$curr_ip = izu_get($_SERVER, 'REMOTE_ADDR');
				
				$ips = split(",", trim($matches[1]));
				foreach($ips as $ip)
				{
					$ip = trim($ip);
					if (gethostbyname($ip) == $curr_ip)
					{
						$valid = false;
						break;
					}
				}
			}

			if (!$valid)
				return FALSE;
		}
		
		// is this a blog?	
		if (strpos($temp, '[izu:blog]') !== FALSE)
		{
			// this is a blog page. ask for a redirect

			$this->mRedirect = $this->mPath->GetSelfUrl() . EXT_BLOG;

			// RM 20050403 add existing query string
			$qs = izu_get($_SERVER, 'QUERY_STRING', '');
			if ($qs != '')
				$this->mRedirect .= '?' . $qs;
		}
	
		if (preg_match('/\[izu:html-charset:(.*?)\]/', $temp, $matches) == 1)
		{
			// set the HTML encoding
			global $html_encoding;
			$html_encoding = $matches[1];
		}

		if (preg_match('/\[izu:html-lang:(.*?)\]/', $temp, $matches) == 1)
		{
			// set the HTML language code
			global $html_language_code;
			$html_language_code = $matches[1];
		}


		return TRUE;
	}
	



	//***********************
	function modifDate($path)
	//***********************
	{
		if (is_string($path) && (izu_is_dir($path) || izu_is_file($path)))
			return filemtime($path);
		else
			return 0;
	}
	
	
	//***********************************************
	function checkExpired($compare_date, &$path_list)
	//***********************************************
	// This function checks to see if any of the path in the path_list
	// contains something newer than compare_date. If yes, the comparison
	// date is expired and TRUE is returned. If no, the comparison date
	// is valid and FALSE is returned.
	//
	// The path_list may contain either folders or files paths.
	// When a folder is given, the folder's modif date is compared and
	// then all its first-level files are compared.
	// Note that no implicit recursion is used.
	// Note that if a similar path is given twice, it will be checked twice.
	//
	// On a filesystem, the modification date of a folder will reflect
	// folder's content update (i.e. files deletes, added, etc.). It will
	// not reflect if the *inside* of a file is modified.
	{
		foreach($path_list as $path)
		{
			if (!is_string($path) || $path == '')
				continue;
	
			if (izu_is_file($path))
			{
				// check if file date is newer
	
				$tm = filemtime($path);
				
				if ($tm > $compare_date)
					return TRUE;
			}
			else if (izu_is_dir($path))
			{
				// check if dir modif date is newer
				
				$tm = filemtime($path);
	
				if ($tm > $compare_date)
					return TRUE;
	
				// if not, check all files in the directory (no recursion)
				// note that since there is no semantic associated to the path,
				// so it is not possible to exclude files based on the content
				// of pref_album_ignore_list or pref_image_ignore_list.
	
				$path = izu_post_sep($path);
	
				$handle = @opendir($path);
				if ($handle)
				{
					while (($file = readdir($handle)) !== FALSE)
					{
						// check if file date is newer
			
						$tm = filemtime($path . $file);
						
						if ($tm > $compare_date)
						{
							closedir($handle);
							return TRUE;				
						}
					}
					
					closedir($handle);
				}
			}
		}
		
		// comparison date not expired
		return FALSE;
	}


	//*******************
	function checkCache()
	//*******************
	// Return the existing cache path if valid (a string)
	// Returns FALSE if not valid
	{
		global $dir_install;
		global $dir_globset;
		global $dir_locset;
		global $dir_src;

		global $abs_album_path;
	
		// fail if there is no abs path
		if (!is_string($abs_album_path) || $abs_album_path == '')
			return FALSE;

		// get the abs directory & abs html cache

		$abs_dir  = $this->mPath->GetSourcePath();
		$abs_html = $this->mPath->GetCachePath();
		
		// DEBUG
		// var_dump($abs_dir);
		// var_dump($abs_html);

		// make sure appropriate subdirs exist

		$this->mPath->CreateTgDirs();

		// does it need to be rebuild?
		// it does if cached html doesn't exist
		
		$is_valid = izu_is_file($abs_html);
	
		// compare cached file date with directory's filedate
		if ($is_valid)
		{
			// To be valid, the cache must exist and must be older than:
			// - the album folder
			// - the local  pref folder modification date (can affect album visibility)
			// - the global pref folder modification date (can affect album visibility)
			// - the RIG source  folder modification date (can affect album content)
			// (in that order, most likely to change tested first)

			$tm_html   = $this->modifDate($abs_html);

			// set the list of files or folders to check
			$check_list = array($abs_dir,
								$dir_install    . izu_prep_sep($dir_src),
								$dir_install    . izu_prep_sep($dir_globset),
								$dir_locset);
	
			// cache is valid if not expired
			$is_valid  = !$this->checkExpired($tm_html, $check_list);

			// If cache exists yet is invalid, remove now
			if (!$is_valid)
				unlink($abs_html);
		}

		if ($is_valid)
			return $abs_html;			// no need to rebuild
		else
			return FALSE;				// needs to be rebuild
	}


	//*************************
	function processDirectory()
	//*************************
	// Returns FALSE if there is no page to include
	// Returns a string if there is a page to include
	{
		global $dir_src;

		$cache = $this->mCache;
		if ($cache === FALSE || !is_string($cache) || $cache == '')
			$cache = $this->checkCache();

		if (is_string($cache) && $cache != '')
		{
			// no need to rebuild
			return $cache;
		}
		else
		{
			// read directory content
		
			$this->readDirectory();

			// start buffering

			$this->startBuffering();

			// render directory
	
			return izu_require_once($this->TemplateName(TRUE), $dir_src);
		}
	
		return FALSE;
	}
	
	
	//**********************
	function readDirectory()
	//**********************
	{	
		$this->mFolderList = array();
		$this->mPageList   = array();

		$rel_dir = $this->mPath->mDir;
		$abs_dir = $this->mPath->GetSourcePath();

		$handle = @opendir($abs_dir);
		if ($handle)
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				if ($file != '.' && $file != '..' && izu_is_visible(-1, $rel_dir, $file))
				{
					$d = $abs_dir . $file;

					if (izu_is_dir($d))
					{
						$this->mFolderList[] = new RPagePath(NULL, $rel_dir . $file, '');;
					}
					else if (izu_valid_ext($file))
					{
						$this->mPageList[] = new RPagePath(NULL, $rel_dir, $file);
				    }
				}
			}
			closedir($handle);
		}
	
		return TRUE;
	}
	
	
	
	//-----------------------------------------------------------------------
	
	
	
	//********************
	function processPage()
	//********************
	// Returns FALSE if there is no page to include
	// Returns a string if there is a page to include
	{
		global $dir_src;

		// fail if there is no current page
		if (!$this->mPath->IsPage())
			return FALSE;
	
		// give up if source does not exists
		if (!$this->mPath->PageExists())
			return FALSE;

		$cache = $this->mCache;
		if ($cache === FALSE || !is_string($cache) || $cache == '')
			$cache = $this->checkCache();

		if (is_string($cache) && $cache != '')
		{
			// no need to rebuild
			return $cache;
		}
		else
		{
			// start buffering

			$this->startBuffering();

			// render page
			return izu_require_once($this->TemplateName(FALSE), $dir_src);
		}
	
		return FALSE;
	}

	//*********************************
	function TemplateName($type = NULL)
	//*********************************
	// Returns a template PHP filename. To be derived.
	// Type can be anything the class wants.
	{
		if (is_bool($type) && $type === TRUE)
			return "template_dir.php";
		else
			return "template_page.php";
	}

	// ---------------------------------------------


	//****************************
	function renderFile($filepath)
	//****************************
	// Process the given file and render it to the output
	// Returns TRUE or FALSE
	{
		// DEBUG
		// echo "<p>izu_renderFile: '$filepath'<p>";

		$state = array();

		$this->renderInit($state, $filepath, 'izu_print2echo');

		// -------	
		
		$file = @fopen($filepath, "rt");
	
		if (!$file)
			return FALSE;

		while(!feof($file))
			$this->renderLine($state, fgets($file, 1023));

		fclose($file);

		// -------	

		$this->renderTerminate($state);
		
		return TRUE;
	}
	

	//*************************
	function renderString($str)
	//*************************
	// Returns the output string, maybe empty
	{
		// DEBUG
		// echo "<p>izu_renderString: '$str'<p>";

		$state = array();

		$this->renderInit($state, '', 'izu_print2string');

		// -------	

		$lines = split("\n", $str);

		$nb = count($lines);
		for($i = 0; $i < $nb; $i++)
			$this->renderLine($state, $lines[$i] . "\n");

		// -------	

		$this->renderTerminate($state);

		return $state['output'];
	}

	// ---------------------------------------------


	//******************************************************
	function renderInit(&$state, $filepath, $print_function)
	//******************************************************
	{
		// -------
		// Store this object in a global so that the static functions
		// called by preg_replace/e can access this object.

		global $izu_current_page;
		$izu_current_page = $this;		

		// -------
		$state['level_pre']	 = 0;
		$state['level_ul']	 = 0;
		$state['level_bq']	 = 0;
		$state['level_table']= array();
		$state['is_comment'] = FALSE;
		$state['line']		 = '';
		$state['same_line']	 = FALSE;
		$state['filepath']   = $filepath;
		$state['printer']    = $print_function;
		$state['output']     = '';
		$state['last_p']	 = FALSE;
	}



	//*********************************
	function renderLine(&$state, $temp)
	//*********************************
	{
		$level_pre		= $state['level_pre'];
		$level_ul		= $state['level_ul'];
		$level_bq		= $state['level_bq'];
		$level_table	= $state['level_table'];
		$is_comment		= $state['is_comment'];
		$line			= $state['line'];
		$same_line		= $state['same_line'];
		$filepath		= $state['filepath'];
		$print_function	= $state['printer'];
		$last_p			= $state['last_p'];


		$continue_process = TRUE;
		

		if (is_string($temp))
		{
			if (substr($temp, -1) == "\n")
			{
				$temp = substr($temp, 0, -1);
				$same_line = FALSE;
			}
	
			if (substr($temp, -1) == "\r")
			{
				$temp = substr($temp, 0, -1);
				$same_line = FALSE;
			}

			// concatenate lines that end with a backslash [RM 20031111]
			if (substr($temp, -1) == "\\")
			{
				$temp = substr($temp, 0, -1);
				$same_line = TRUE;
			}
	
			$line .= $temp;
		}

		// -------
		// Process line if not empty and not to be merged with next line

		if (is_string($line) && ! $same_line)
		{
			// -------

			// ----- comments ----
			if (!$is_comment)
			{
				if (preg_match('/(^|[^\[])\[!--(.*)$/', $line, $mt) == 1)
				{
					// A comment is being open. Skip the rest of the line.
					$is_comment = TRUE;
					
					// Remove the comment part
					$line = str_replace($mt[0], '', $line);

					if ($line == '')
					{
						// result line is empty. skip and do not insert a <p>
						$continue_process = FALSE;
					}
				}
			}
			
			if (is_comment)
			{
				// we're skipping lines till we hit the end of a comment
				// note that comments cannot be embedded in other comments.

				$pos = strpos($line, '--]');
				if ($pos !== FALSE)
				{
					// A comment is being closed. Skip the rest of the line.
					$is_comment = FALSE;

					// process the end of the line as usual
					$line = substr($line, $pos+3); // 3=strlen('--]')
				}
				
				// skip commented lines
				if ($is_comment)
				{
					$line = '';
					$continue_process = FALSE;
				}
			}
			
			
			// -----
	
	/*
			if ($continue_process)
			{
				// 
				if ($line == '')
				{
					$line = '<p>';
					$continue_process = FALSE;
				}
			}
*/
			// -- performs basic replacements --

			if ($continue_process)
			{
				$p = array();
				$r = array();
	
	
				// This heavily uses PCRE syntax:
				// http://www.php.net/manual/en/pcre.pattern.syntax.php
				//
				// Some comments about expressions used below:
				//	+? or *?: ? after a repetitor makes it non-greedy i.e. stops as soon as it can
				//				ex: \[ .*? \] can be used instead of \[ [^\]]* \]
				//	(?:...) creates a non-capturing subpatterns.
				//	(^|abc) matches abc or the beginning of the line
				//
				// Flag e (i.e. /toto/e ) means to execute some PHP code in the replace expression
				// that must produce a string, so you need to do "func() . "\1") to convert \1 into a
				// a concatenated string.
				//
				// Use 'string' with single-quotes in PHP if you don't need to put ' inside
				// otherwise use "double-quoted strings" but then you must escape any backslash \ twice
				// (one for PHP and one for PCRE). I.e. use '\1' or "\\1"
	
				
				// -- HTML < > --
				// disable < > as early as possible, thus invalidating any HTML
				// or scripting present in the source text
	
				$p[] = '/>/';
				$r[] = '&gt;';
	
				$p[] = '/</';
				$r[] = '&lt;';
	

				// -- empty lines are paragraphs --
				
				$p[] = '/^$/';
				$r[] = '<p>';

	
				// --- Izu processing codes --
	
				// Include external file using Enscript to perform pretty HTML
				
				$p[] = '/(^|[^\[])\[izu:enscript-file:(.*?)\]/e';		// anywhere, without [[
				$r[] = '"\1" . izu_enscript_file("\2", "' . $filepath . '")';
	
				// Izu:image with optional align tag, optional link and optional label
				// Format is [izu:image:url_img(,align=blah)(|url_link)(:label)]
				// Url_link must start with http. The link cannot contain " : or < >
				
				//       \1:delim             \2:url img                                      \3:tag   \4:value      \5:link_url                           \6:label
				$p[] = '@(^|[^\[])\[izu:image:(https?://[^\],"<>]+\.(?:gif|jpe?g|png|svg))(?:,([a-z]+)=([a-z]+))?(?:\|((?:https?://|ftp://|#)[^:"<>]+))?(?::([^\]]+))?\]@e';	// anywhere, without [[
				$r[] = '"\1" . izu_image_tag("\2", NULL, "\3", "\4", "\5", "\6")';

				// This one is for local links. They'll be escaped and prefixed with the site's URL
				//       \1:delim             \2:url img                             \3:tag   \4:value      \5:link_url                           \6:label
				$p[] = '@(^|[^\[])\[izu:image:([^\],"<>]+\.(?:gif|jpe?g|png|svg))(?:,([a-z]+)=([a-z]+))?(?:\|((?:https?://|ftp://|#)[^:"<>]+))?(?::([^\]]+))?\]@e';	// anywhere, without [[
				$r[] = '"\1" . izu_image_tag(NULL, "\2", "\3", "\4", "\5", "\6")';
	
				// Izu:blogrefs
				
				$p[] = '/(^|[^\[])\[izu:blog-refs:([^\]]+)\]/e';	// anywhere, without [[
				$r[] = '"\1" . izu_blog_refs("\2")';

				
				// Izu:permalink for blogs
				
				$p[] = '/(^|[^\[])\[izu:permalink:([^:]*):([^:]+):(.*)\]/e';	// anywhere, without [[
				$r[] = '"\1" . izu_blog_permalink("\2", "\3", "\4")';

	
				// extract and remove remaining izu processing codes
	
				$p[] = '/(^|[^\[])\[izu:.+?\]/';		// anywhere, without [[
				$r[] = '\1';
	
	
	
	
				// -- HTML codes --
				// '----' at the beginning is an <hr>

				$p[] = '/^----/';
				$r[] = '<hr>';
						
				// '%%%' at the end means a line break is necessary
				// - loosen this to be "anywhere"
				// - changed again to be only at the end with whitespace after [RM 20031111]
				// - added the forward slash / for the same semantic. [RM 20031111]
	
				$p[] = '/%%%[ \t]*$/';
				$r[] = '<br>';
	
				$p[] = '@/[ \t]*$@';
				$r[] = '<br>';
		
				// set __xx__ as bold
	
				$p[] = '/(^|[^_])__([^_].*?)__($|[^_])/';
				$r[] = '\1<b>\2</b>\3';
				
				// set ''xx'' as italics
	
				$p[] = "/(^|[^'])''([^'].*?)''($|[^'])/";
				$r[] = '\1<i>\2</i>\3';
				
				// set ==xx== as code
	
				$p[] = "/(^|[^=])==([^=].*?)==($|[^=])/";
				$r[] = '\1<code>\2</code>\3';
	
				// transforms ___ (3 _) in two of them
	
				$p[] = '/(^|[^_])___(_*)/';
				$r[] = '\1__\2';
	
				// transforms ''' (3 ') in two of them
	
				$p[] = "/(^|[^'])'''('*)/";
				$r[] = "\\1''\\2";

				// transforms === (3 =) in two of them
	
				$p[] = "/(^|[^=])===(=*)/";
				$r[] = "\\1==\\2";
	
				// -- izu formating codes --
				
				// [h1], [h2]... [h9]
	
				$p[] = '/(^|[^\[])\[[h|H]([0-9])\](.*)$/';	// anywhere, without [[
				$r[] = '\1<h\2>\3</h\2>';
	
				// [c] center
	
				$p[] = '/(^|[^\[])\[c\](.*)$/';	// anywhere, without [[
				$r[] = '\1<center>\2</center>';

				// [s] blog section

				$p[] = '/(^|[^\[])\[s:([^:]+)(?::([^\]]+))?\]/e';	// anywhere, without [[
				$r[] = '"\1" . izu_blog_section("\2","\3")';


				// -- table of content entries --
	
				// format is n.n.n. + tab + title
				// and this generates H2/H3/H4 (ihmo H1 is too much)
	
				$p[] = '/^([0-9]\.)\t(.*)$/';
				$r[] = '<h2>\1 \2</h2>';
	
				$p[] = '/^([0-9]\.[0-9]\.)\t(.*)$/';
				$r[] = '<h3>\1 \2</h3>';
	
				$p[] = '/^([0-9]\.[0-9]\.[0-9]\.)\t(.*)$/';
				$r[] = '<h4>\1 \2</h4>';
	
				// -- format internal link part 1 --
	
				// direct internal anchor blog reference [#s:YYYYMMDD:title]
				$p[] = '@(^|[^\[])\[(#s:[0-9]+(?::([^\]]+))?)\]@e';
				$r[] = '"\1" . izu_create_url("", "\3", "\2")';
	
				// named link: [name|#s:YYYYMMDD:title], without [[
				$p[] = '@(^|[^\[])\[([^\|\]]+)\|(#s:[0-9]+(?::[^\]]+)?)\]@e';
				$r[] = '"\1" . izu_create_url("","\2","\3")';

				// -- format external links --
				
				// named image link: [title|http://blah/blah.gif,jpeg,jpg,png,svg], without [[
				$p[] = '@(^|[^\[])\[([^\|\[\]]+)\|(https?://[^\]"<>]+\.(?:gif|jpe?g|png|svg))\]@';
				$r[] = '\1<img alt="\2" title="\2" src="\3">';
	
				// unnamed image link: [http://blah/blah.gif,jpeg,jpg,png,svg], without [[
				$p[] = '@(^|[^\[])\[(https?://[^\]"<>]+\.(?:gif|jpe?g|png|svg))\]@';
				$r[] = '\1<img src="\2">';
	
				// named link: [name|http://blah/blah], accept ftp:// and #name, without [[
				$p[] = '@(^|[^\[])\[([^\|\[\]]+)\|((?:https?://|ftp://|#)[^"<>]+?)\]@';
				$r[] = '\1<a href="\3">\2</a>';
	
				// unnamed link: [http://blah/blah], accepts ftp:// and #name, without [[
				$p[] = '@(^|[^\[])\[((?:https?://|ftp://|#)[^"<>]+?)\]@';
				$r[] = '\1<a href="\2">\2</a>';
	
				// unformated link: http://blah or ftp:// (link cannot contain quotes)
				// and must not be surrounded by quotes
				// and must not be surrounded by brackets
				// and must not be surrounded by < >		-- RM 20041120 fixed
				// and must not be prefixed by [] or |
				// (all these exceptions to prevent processing twice links in the form <a href="http...">http...</a>
				$p[] = '@(^|[^\[]\]|[^"\[\]\|>])((?:https?://|ftp://)[^ "<]+)($|[^"\]])@';
				$r[] = '\1<a href="\2">\2</a>\3';
	
				// -- format internal links part 2 --
	
				// Special: named link to content root (i.e. [name|])
				$p[] = '/\[([^\|\]]+)\|\]/e';
				$r[] = 'izu_create_url("","\1")';
	
				// named link: [name|Dir/Dir/WordTwoThree#part], without [[
				$p[] = '@(^|[^\[])\[([^\|\]]+)\|((?:[A-Z][a-z]*)+(?:/(?:[A-Z][a-z]*)+)*)(#[a-zA-Z][a-zA-Z0-9_]+)?\]@e';
				$r[] = '"\1" . izu_create_url("\3","\2","\4")';

				// named link: [name|Dir/Dir/WordTwoThree#s:YYYYMMDD:title], without [[
				$p[] = '@(^|[^\[])\[([^\|\]]+)\|((?:[A-Z][a-z]*)+(?:/(?:[A-Z][a-z]*)+)*)(#s:[0-9]+(?::[^\]]+)?)?\]@e';
				$r[] = '"\1" . izu_create_url("\3","\2","\4")';
	
				// unnamed link: [Dir/Dir/WordTwoThree#part]
				$p[] = '@(^|[^\[])\[((?:[A-Z][a-z]*)+(?:/(?:[A-Z][a-z]*)+)*)(#[a-zA-Z][a-zA-Z0-9_]+)?\]@e';
				$r[] = '"\1" . izu_create_url("\2", "", "\3")';

				// unnamed link: [Dir/Dir/WordTwoThree#s:YYYYMMDD:title]
				$p[] = '@(^|[^\[])\[((?:[A-Z][a-z]*)+(?:/(?:[A-Z][a-z]*)+)*)(#s:[0-9]+(?::[^\]]+)?)?\]@e';
				$r[] = '"\1" . izu_create_url("\2", "", "\3")';
	
				// unformated link: AtLeastTwoWords
				// and must be surrounded by space, !, comma, tab or punctuation
				// and must not be prefixed by []
	
				$p[] = '/(^|[^\[])(^|[ \t.,;:\!])([A-Z][a-z]+(?:[A-Z][a-z]*)+)($|[ \t.,;:\!])/e';
				$r[] = '"\1\2" . izu_create_url("\3") . "\4"';
	
	
				// -- html references --
				
				// reference hlink: [a:name] without [[
				$p[] = '/(^|[^\[])\[a:([a-zA-Z][a-zA-Z0-9_-]+)\]/';
				$r[] = '\1<a name="\2" /a>';
	
				// -- remove exception formaters --
	
				// remove []WordOneOrMore
				
				$p[] = '/(^|[^\[])\[\]((?:[A-Z][a-z]*)+)/';
				$r[] = '\1\2';
	
				// perform actions

				$line = preg_replace($p, $r, $line);


				// -- table management
				
				if (preg_match('/(^|[^\[])\[table:begin(?::([0-9:%px]*))?\]/', $line, $m_t) == 1)
				{
					$wt = '';
					$wd = '';

					$prefix = $m_t[1];

					$v = split(':', $m_t[2]);
					$info = array('hw' => $v, 'nb_col' => 1);

					$a = $v[0];
					if (is_string($a) && strlen($a) > 0)
						$wt .= " width=\"" . $a . "\"";

					$a = $v[1];
					if (is_string($a) && strlen($a) > 0)
						$wd .= " width=\"" . $a . "\"";

					$s = "$prefix<table border=\"0\" $wt><tr valign=\"top\"><td $wd>";
					$line = str_replace($m_t[0], $s, $line);
					
					array_push($level_table, $info);
				}
				
				if (preg_match('/(^|[^\[])\[row\]/', $line, $m_t) == 1)
				{
					$prefix = $m_t[1];

					$info = array_pop($level_table);
					$nb_col = 1;

					$wd = '';
					$a = $info['hw'][$nb_col];
					if (is_string($a) && strlen($a) > 0)
						$wd .= " width=\"" . $a . "\"";

					$line = str_replace($m_t[0], "$prefix</td></tr><tr valign=\"top\"><td $wd>", $line);

					$info['nb_col'] = $nb_col;
					array_push($level_table, $info);
				}
				
				if (preg_match('/(^|[^\[])\[col\]/', $line, $m_t) == 1)
				{
					$prefix = $m_t[1];

					$info = array_pop($level_table);
					$nb_col = ++ $info['nb_col'];

					$wd = '';
					$a = $info['hw'][$nb_col];
					if (is_string($a) && strlen($a) > 0)
						$wd .= " width=\"" . $a . "\"";

					$line = str_replace($m_t[0], "$prefix</td><td $wd>", $line);

					$info['nb_col'] = $nb_col;
					array_push($level_table, $info);
				}

				if (preg_match('/(^|[^\[])\[table:end\]/', $line, $m_t) == 1)
				{
					$prefix = $m_t[1];
					$line = str_replace($m_t[0], "$prefix</td></tr></table>", $line);
					array_pop($level_table);
				}
				

				// -- bracket exceptions cleanup
				// (must be done _after_ the table processing)

				$p = array();
				$r = array();

				// remove [] which was used to escape automatic linking
				// except if it started with [[
				
				$p[] = '/(^|[^\[])\[\]/';
				$r[] = '\1';

				// remove the first bracket in [[...]
				
				$p[] = '/(^|[^\[])\[(\[.*?\])/';
				$r[] = '\1\2';

				// perform actions

				$line = preg_replace($p, $r, $line);


				// [RM 20050512 added]
				// Empty lines that consist of solely white-space characters
				// Must be done at the very end but before block management
				if (preg_match('/^(?: |\n|\r|\t)+$/', $line, $m) == 1)
					$line = '';

				// -- blockquote & pre management --
				// -- list management --
				
				// Note: bq and ul are mutually exclusive.
				// RM 20040220 fix: blockquote is tabs+ + (not tab nor *) + stuff, was missing the "not tab"
	
				$is_bq  = (preg_match("/^(\t+)([^\t\*].*)$/",  $line, $m_bq ) == 1);
				$is_ul  = (preg_match("/^(\t*)\*[ \t]+(.*)$/", $line, $m_ul ) == 1);
				$is_pre = (preg_match("/^ (.*)$/",			   $line, $m_pre) == 1);


				$new_lbq  = ($is_bq  ? strlen($m_bq[1])   : 0);
				$new_lul  = ($is_ul  ? strlen($m_ul[1])+1 : 0);
				$new_lpre = ($is_pre ? 1                  : 0);

				// DEBUG
				//var_dump($m_ul);
				//var_dump($level_ul);
				//var_dump($new_lul);

				// close previous

				for(; $level_ul  > $new_lul;  $level_ul-- )
					$print_function($state, "</ul>\n");
	
				for(; $level_pre > $new_lpre; $level_pre--)
					$print_function($state, "</pre>\n");
	
				for(; $level_bq  > $new_lbq;  $level_bq-- )
					$print_function($state,  "</blockquote>\n");

				// open new ones

				for(; $level_bq  < $new_lbq;  $level_bq++ )
					$print_function($state, "<blockquote>\n");
	
				for(; $level_pre < $new_lpre; $level_pre++)
					$print_function($state, "<pre>\n");
	
				for(; $level_ul  < $new_lul;  $level_ul++ )
					$print_function($state, "<ul>\n");


				// transform string
	
				if ($is_bq)
				{
					$line = $m_bq[2];
				}
				else if ($is_pre)
				{
					$line = $m_pre[1];
				}
				else if ($is_ul)
				{
					// make line a list item
					$line = "<li>" . $m_ul[2] . "</li>";
				}
			}
	
			// -- output final line --

			// Do not output multiple <p> lines nor empty lines.
			
			if ($last_p)
			{
				while(strncmp($line, "<p>", 3) == 0)
					$line = substr($line, 3);
			}
			
			if (   $line != '' 
			    && $line != ' ' 
			    && $line != '\n')
			{
				$print_function($state, $line . "\n");
					
				$n = strlen($line);
				$last_p = ($n >= 3 && strpos($line, "<p>", $n-3) == $n-3);
			}
	
	
			// -------
			// line has been used, prepare to process next
			$line = '';
		}
		
		// -------

		$state['level_pre']	  = $level_pre;
		$state['level_ul']	  = $level_ul;
		$state['level_bq']	  = $level_bq;
		$state['level_table'] = $level_table;
		$state['is_comment']  = $is_comment;
		$state['line']		  = $line;
		$state['same_line']	  = $same_line;
		$state['last_p']	  = $last_p;

	}
	


	//*******************************
	function renderTerminate(&$state)
	//*******************************
	{
		$level_pre 		= $state['level_pre'];
		$level_ul 		= $state['level_ul'];
		$level_bq 		= $state['level_bq'];
		$print_function = $state['printer'];

		// close previous

		for(; $level_ul  > 0;  $level_ul-- )
			$print_function($state, "</ul>\n");

		for(; $level_pre > 0; $level_pre--)
			$print_function($state, "</pre>\n");

		for(; $level_bq  > 0;  $level_bq-- )
			$print_function($state, "</blockquote>\n");
	}


	// ---------------------------------------------

	
	//-----------------------------------------------------------------------


	//***********************
	function startBuffering()
	//***********************
	{
		// Simple version:
		// - Make sure that PHP ini's "implicit_flush" is off
		// - Use ob_start
		// when stopping:
		// - Get everything from ob_get_contents into the cache html file
		// - Use ob_end_flush

		// Advanced version:
		// - Use a callback to flush output regularly
		// - Regularly copy this output to a temporary file
		// - When stopped, swap the temp file with the destination file
		//   (using system's move, dest file must be on same volume)

		// RM 20030809 => Implementation of simple version

		$this->mTempBuffer = $this->mPath->GetCachePath();
		
		ob_implicit_flush(0);
		ob_start();
	}
	
	
	//**********************
	function stopBuffering()
	//**********************
	{
		$file = fopen($this->mTempBuffer, "wt");
		
		if ($file)
		{
			fwrite($file, ob_get_contents());
			fclose($file);
		}

		ob_end_flush();
		$this->mTempBuffer = NULL;
	}


} // end RPage

//-----------------------------------------------------------------------
// Callback for the regexp processing


//************************************
function izu_print2echo(&$state, $str)
//************************************
{
	echo $str;
}


//**************************************
function izu_print2string(&$state, $str)
//**************************************
{
	$state['output'] .= $str;
}


//***************************************************
function izu_create_url($page, $name = "", $ref = "")
//***************************************************
// $page = the main IzumiPageName
// $name = the text of the link
// $ref  = the #ref at the end of the URL
//
// Regexp Callback functions must return a string, not output to echo
{
	global $izu_current_page;
	$create_link = TRUE;


	// Is the page keyword in the mAutoIgnoreKeywords list?
	// (note that if a name is provided, then we enforce this as an izu page link)
	
	if ($name == "")
	{
		$ignore = array_search($page, $izu_current_page->mAutoIgnoreKeywords, TRUE);
		$create_link = ($ignore === NULL || $ignore === FALSE);

		if ($create_link)
		{
			// Check that the page really is an existing izu page
			$path = new RPagePath($page);
			
			if ($path->IsEmpty() || !$path->IsPage() || !$path->PageExists())
			{
				// don't create the link if page does not exists
				$create_link = FALSE;
				// and add to the ignore list
				$izu_current_page->mAutoIgnoreKeywords[] = $page;
			}
		}
	}
	else
	{
		// RM 20040926 hack: fix single quotes
		$name = str_replace('\\\'', '\'', $name);
	}

	// RM 20050403 process blog anchors: #s:YYYMMDD:title
	if (preg_match('/#s:([^:]+)(?::(.*))?/', $ref, $matches) == 1)
	{
		// compute section key
		$date = $matches[1];
		$title = $matches[2];
		$ref = "s=" . RBlog::BlogEntryKey($date, $title);
		
		// we need a page name. If it's an empty string
		// use -1 to mean this current page
		if (is_string($page) && strlen($page) == 0)
			$page = -1;
	}



	if ($create_link)
	{
		if ($name == "")
			$name = izu_pretty_name($page);

		$link = izu_self_url($page, -1, -1, $ref);

		// When processing some page dynamically generated by a blog
		// we don't have a valid page's pretty name
		if (is_string($page) && strlen($page) > 0)
			return "<a href=\"$link\" title=\"$page\" alt=\"$page\">$name</a>";
		else
			return "<a href=\"$link\" title=\"$name\" alt=\"$name\">$name</a>";
	}
	else
	{
		// The page was in the ignore list or needs to be ignore... simply return the keyword as-is
		return $page;
	}
}


//***************************************************
function izu_enscript_file($enscript_file, $izu_file)
//***************************************************
// Regexp Callback functions must return a string, not output to echo
{
	// enscript_file must be a local name. Remove any "../" or "//" from it.
	$enscript_file = izu_decode_argument($enscript_file);

	// izu_file is the absolute path of the current izu file.
	// make enscript_file an absolute too

	if (preg_match("@^(/.*/)[^/]+.izu$@", $izu_file, $matches) == 1)
	{
		$path = $matches[1] . $enscript_file;

		if (izu_is_file($path))
		{
			// run this command line in a shell and past output directly to PHP:
			$cmd = "enscript --quiet -B -h --output=- --language=html --highlight --color $path | sed -n '/^<PRE>\$/,/<\/PRE>\$/p'";

			passthru($cmd);
		}		
	}
}


//*******************************************
function izu_image_tag($http_url,
					   $local_url,
					   $tag_name, $tag_value,
					   $link_url,
					   $label_url)
//*******************************************
// Regexp Callback functions must return a string, not output to echo
// RM 20040118
// RM 20041205 Added link url and label.
// RM 20050123 make img_url relative to global $dir_album. Also cleanup dir to avoid .. and meta-shell characters.
{
	global $dir_album;

	$img_url = "";

	if ($local_url != NULL) {
		// Cleanup & format local URL
		$img_url = izu_decode_argument($local_url);
		$img_url = izu_post_sep($dir_album) . $img_url;
		$img_url = izu_self_url($img_url);
	} else if ($http_url != NULL) {
		// The regexp prevents " and < > from appearing so this is good enough
		$img_url = $http_url;
	} else {
		return "<!-- invalid izu:image -->";
	}

	$s = "<img src=\"$img_url\"";

	if (is_string($tag_name) && is_string($tag_value) && $tag_name != "" && $tag_value != "")
		$s .= " $tag_name=\"$tag_value\"";

	if (is_string($label_url) && $label_url != "")
		$s .= " alt=\"$label_url\" title=\"$label_url\"";

	if (is_string($link_url) && $link_url != "")
		$s .= " border=\"0\"";

	$s .= "/>";

	if (is_string($link_url) && $link_url != "")
		$s = "<a href=\"$link_url\">$s</a>";

	return $s;
}


//***********************************************
function izu_blog_permalink($mode, $title, $link)
//***********************************************
// Regexp Callback functions must return a string, not output to echo
{
	if (is_string($link) && strlen($link) > 0)
		$link = "s=" . $link;
	else
		$link = '';

	$name = izu_self_url(-1, -1, -1, $link);

	$link = "<a href=\"$name\">$title</a>";

	if ($mode == "hr")
		return "<p>$link<p><hr><p>\n";
	if ($mode == "br")
		return "$link<br>\n";
	else
		return "$link\n";
}


//***************************
function izu_blog_refs($refs)
//***************************
// Regexp Callback functions must return a string, not output to echo
{
	$ret = '<p><hr><p>Related Blogs:&nbsp;';

	$refs = str_replace(' ', '', $refs);
	$refs = split(',', $refs);

	$n = count($refs);
	for($i = 0; $i < $n; $i++)
	{
		$s = $refs[$i];

		if (preg_match('/([^|]+)\|([^|]+)/', $s, $matches) == 1)
			$ret .= izu_create_url($matches[2], $matches[1]);
		else
			$ret .= izu_create_url($s);

		if ($i < $n-1)
			$ret .= "&nbsp;|&nbsp;";
	}

	$ret .= "<hr><p>\n";
	return $ret;
}

//**************************************
function izu_blog_section($date, $title)
//**************************************
// Regexp Callback functions must return a string, not output to echo
{
	global $color_section_bg;
	global $color_section_text;

	// RM 20041203 hack: fix single quotes
	$title = str_replace('\\\'', '\'', $title);

	$style  = "color: " . $color_section_text . "; background-color: " . $color_section_bg;
	$style .= "; padding: 1px 10px 1px 10px; font-weight: bold; display: inline";
	$style .= "; border-bottom: 1px solid; border-top: none; border-left: none; border-right: none";

	// Format output
	$str  = "&laquo;&raquo;&nbsp;&nbsp;";
	$str .= izu_pretty_date($date);
	if (is_string($title) && $title != '')
		$str .= '&nbsp;&laquo;&raquo;&nbsp;' . $title;
	$str .= "&nbsp;&nbsp;&laquo;&raquo;";

	// return "<p><span class=\"izu-section\" style=\"$style\">$str</span><p>\n";
	return "<p><div class=\"izu-section\">$str</div><p>\n";
}


//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.6  2006-09-13 05:58:42  ralfoide
//	[1.1.4] Fixed izu:image with external http:// urls.
//	[1.1.3] Source: Added Google Related Links display.
//
//	Revision 1.5  2006/02/27 03:45:47  ralfoide
//	Fixes
//	
//	Revision 1.4  2005/05/12 15:50:27  ralfoide
//	Fix: Empty lines that consist of solely white-space characters in RPage
//	Fix: Remove unnecessary <p> at beginning of RSS post content
//	
//	Revision 1.3  2005/04/26 00:45:28  ralfoide
//	Updating DEB to 1.1
//	
//	Revision 1.2  2005/04/05 18:54:01  ralfoide
//	Started work on version 1.1
//	Changed blog entries keys from MD5 to encoded date/title clear text.
//	Added internal anchor references to blog entries.
//	
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.15  2004/12/20 07:01:37  ralf
//	New minor features. Version 0.9.4
//	
//	Revision 1.14  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.13  2004/12/06 08:19:20  ralf
//	Fixes
//	
//	Revision 1.12  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.11  2004/12/05 07:01:56  ralf
//	Fix for izu:image, made align attribute optional
//	
//	Revision 1.10  2004/12/04 22:22:02  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.9  2004/12/04 10:08:15  ralf
//	Table support. Fixes
//	
//	Revision 1.8  2004/11/28 22:38:00  ralf
//	Version 0.9.1: RSS support with ETag/If-Modified.
//	
//	Revision 1.7  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.6  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
//	
//	Revision 1.5  2004/11/21 18:17:12  ralf
//	Blog support added. Experimental.
//	
//	Revision 1.4  2004/09/26 19:32:27  ralf
//	Support for block comments in izu files.
//	Fixed quotes prefixed with backslash in named izu links (hack).
//	
//	Revision 1.3  2004/05/09 19:06:10  ralf
//	Use url rewrite. Allow for not having index.php in the URL.
//	Added izu:image, #links, fixed some regexps. Log http-language-accept.
//	Added site license generated on every page.
//	Added izumi favicon. Fixed dba_open n vs c, using wait lock.
//	Support HTTP 304 Not Modified, ETag, Last-Modified headers and If-counterparts.
//	
//	Revision 1.2  2004/01/06 09:08:43  ralf
//	Don't create izu links on page that do not exists.
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//-------------------------------------------------------------
?>
