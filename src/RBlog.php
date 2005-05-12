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

/*

Blog Handling in Izumi
----------------------

Blogging is done using a minimal extension to a classic Izumi page:
- The page must start with the keyword [izu:blog]
- The page can contain a keyword [izu:blog-refs: list of Izu pages ]
- Entries are separated by an [s] tag (section:
	[s:date]
or
	[s:date:title]

Each entry terminate when the next one begins.
If a separator tag ( ---- ) is located just before a [s] tag, it is skipped.

Each entry in a blog must have a unique [s] tag. If two entries have the same
tag, they will be merged together.


Initial Workflow
----------------

When RPage detects an [izu:blog] tag, it requests a redirectory to a
URL "page.blog". When RPagePath sees a ".blog" extension in a page request,
it instantiate a RBlog rather than RPage.

RBlog receives a path with has the ".blog" removed, that is the path
is the one on the master .izu file.

RBlog must then:
- Check if the izu file has been modified.
- If yes, the blog cache needs to be rebuild.
- If not, the request can be served.

One obvious difference with a normal izu file is that different kinds of pages
can be served:
- The master .izu page could be served as-is. This is currently not implemented.
- An overview page is served by default which contains the last N blog entries.
- Individual entries can be served as pages with a permalink.

Since RBlog derives from RPage, it tries to reuse as much of the foundation
as possible. One easy way to serve the multiple pages is to actually create
temporary izu files, one per section to be served, and let RPage handle the
page has if it had been a normal izu page.

For a given master blog izu page called "Dir/Test.izu":
- RBlog will receive a RPagePath onto "Dir" and "Test".
- A cache directory "Dir/Test.blogdir" will be created.
- The directory will be filled with a "main.izu" which is the "default" file
  containing the last N blog entries.
- The directory will be filled will a serie of "NNNN.Xxxxxxx.izu", one per section,
  where XXX is the lower-case 32-characters MD5 of a given section
  and NNNN is a 4-digit indicating the section order in the master file.

To check if a blog needs to be updated, the modification date of the master
izu file will be checked against the main.izu file. If the master is more
recent, the cache will be purged and reconstructed. As usual in Rig and Izumi
no special care is taken to prevent two web server threads from reconstructing
the cache of the same page at the same time.

When several sections have the same date/title, they are merged together,
in which case the 4-digit index will be the one of the first section found.
The purpose of the index is not to differiate sections of identical name
but to be able to easily provide an ordered list of links or previous/next links.

An obvious optimization would be to avoid purging the cache and only
updating those sections that have really changed. No attempt is done in a
first version to do that. It is expected this is going to be a low volume
processing and simply recreate all pages will be fast enough to start with.
Eventually the option can be done later if deemed necessary.

Note also that since rendering of the izu will be delegated to an
RPage object, generation and handling of the HTML cache will be done by RPage.


Access
------

Accessing
	"Dir/Test"
will redirect to 
	"Dir/Test.blog" 
which will show the content of 
	"Dir/Test.blogdir/_main.izu".

Permalinks will be in the form
	"Dir/Test.blog?s=xxxxxxxxxxxxxx"
where "xxxxxxxxxxx" is the 32-characters key, in which case if it exists
the cached entry will be served:
	"Dir/Test.blogdir/NNNN.MD5xxxxxxxxxxxxxx.izu".


*/
//-----------------------------------------------------------------------

define("BLOG_NB_MAIN", 10);
define("BLOG_NB_PREV",  5);

//-----------------------------------------------------------------------


//***********************
class RBlog extends RPage
//***********************
{
	var	$mIsRss;


	//********************
	function RBlog(&$path)
	//********************
	{
		$this->mIsRss = FALSE;

		parent::RPage($path);
	}
	
	
	//*****************************
	function Prepare($title = NULL)
	//*****************************
	{
		global $display_title;
		global $display_page_title;
		global $html_content, $html_none;
		global $pref_title_name;			// RM 20040314

		// Check if base class accepts this file

		if (!parent::Prepare($title))
			return FALSE;
		
		// A normal side effect of calling RPage::Prepare here is that
		// it will ask for a redirect onto the blog page (which is how we
		// got here in the first page). Except here we do not want to redirect
		// and want to avoid an infinite loop, so let's reset the redirect.
		
		$this->mRedirect = '';

		// Setup titles
		
		if (!$title)
			$title = $html_content;
	
		if (is_string($pref_title_name) && $pref_title_name != '')
			$name = " Blog: $pref_title_name - ";
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

		
		// Check if blog needs to be reconstructed
		
		if ($this->isBlogModified())
			if (!$this->rebuildBlog())
				return FALSE;

		// Now that the blog is correctly built, 
		// set the path onto the izu section file
		
		if (!$this->setInternalIzuPath())
			return FALSE;

		// Handle HTTP Last-Modified/ETag and If-Modified-Since/If-None-Match
		// only continue if modified
		$this->handleLastModified();
		
		return TRUE;
	}


	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------
	// Main HTML or RSS rendering of the page

	//*******************
	function RenderPage()
	//*******************
	{
		if ($this->mIsRss)
		{
			// An RSS feed is displayed as-is
			$p = $this->BeginProcess();
			
			if (is_string($p))
			{
				if (substr($p, -4) == '.php')
 					include($p);
				else
					$this->DisplayRssContent($p);
			}
			
			$this->EndProcess();
		}
		else
		{
			parent::RenderPage();
		}
	}


	//*********************
	function BeginProcess()
	//*********************
	{
		if ($this->mIsRss)
			$p = $this->processRssPage();
		else
			$p = $this->processPage();
		return $p;
	}


	//***********************
	function processRssPage()
	//***********************
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
		if ($this->mIsRss)
			return "template_rss.php";
		else
			return parent::TemplateName($type);
	}


	//*********************
	function ExtraHeaders()
	//*********************
	// Returns extra lines to insert in the HTML's <head>
	{
		if (!$this->mIsRss)
		{
			$feed = izu_self_url(-1, -1, -1, 's=rss');
			return "<link title='RSS' rel='alternate' type='application/rss+xml' href='$feed'>";
		}

		return '';
	}


	//******************************************
	function DisplayRssContent($filepath = NULL)
	//******************************************
	{
		if ($filepath == NULL)
			$filepath = $this->mPath->GetSourcePath();

		global $html_encoding;
		header("Content-Type: text/xml; charset=$html_encoding");

		// -------	
		
		$file = @fopen($filepath, "rt");
	
		if (!$file)
			return FALSE;

		while(!feof($file))
			echo fgets($file, 1023);

		fclose($file);

		// -------	

		return TRUE;
	}


	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------
	
	//***************************************
	function BlogEntryKey($date, $title = "")
	//***************************************
	// This static method returns the key for a blog entry
	// The key is a string, typically an MD5 or a simplified combination
	// of the date & title which is unique and safe to use as a file name.
	// As such it should only contain number, letters and _ and be case
	// insenstive.
	//
	// The old way was to use MD5($date . $title).
	// This changed to a numerical date combined with part of the
	// title and a CRC32 on the title if too long (keep up to 32 chars
	// for the key).
	{
		$d = "";
		$t = "";
		
		$digits = "0123456789";
		$alpha  = "abcdefghijklmnopqrstuvwxyz";
		$extra  = " -_=+[]{};:'\",./<>?`~!@#$%^&*()\\|";
		
		// Only keep numbers in date
		if ($date)
		{
			$n = strlen($date);
			for($i = 0; $i < $n; $i++)
			{
				$c = $date[$i];
				if (strpos($digits, $c) !== FALSE)
					$d .= $c;
			}
		}

		// only keep alpha, digits and _ in title
		if ($title)
		{
			$lowcaps = strtolower($title);
			$n = strlen($title);
			for($i = 0; $i < $n; $i++)
			{
				$c = $lowcaps[$i];
				if (   strpos ($digits, $c) !== FALSE
					|| strpos($alpha , $c) !== FALSE)
					$t .= $c;
				else if (strpos($extra, $c) !== FALSE)
					$t .= '_';
			}
		}

		// we want at least 4 digits for the date
		if (strlen($d) > 4)
		{
			if (strlen($t) > 0)
				$d .= '_' . $t;

			// if too long, add a crc32			
			$n = strlen($d);
			if ($n > 32)
			{
				$d = substr($d, 0, 23);
				
				// get the crc32 of the original date + string combo
				$c = crc32($date . $title);
				
				$d .= sprintf("_%x", $c);
			}
			
			return $d;
		}		
		
		
		// if the date or the title were not satisfactory
		// revert to an MD5 key
		return md5($date . $title);
	}



	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------
	// P R I V A T E   M E T H O D S
	//-----------------------------------------------------------------------
	//-----------------------------------------------------------------------


	//***************************
	function setInternalIzuPath()
	//***************************
	{
		global $abs_preview_path;

		// Is a specific permalink/section requested?

		$section = izu_get($_GET,'s');

		$section = str_replace('.' , '', $section);
		$section = str_replace(SEP , '', $section);
		$section = str_replace(SEP2, '', $section);

		$page = $this->mPath->GetBlogPath('', $section, TRUE);

		$this->mIsRss = $this->mPath->IsRss();

		if (is_string($section) && strlen($section) > 4 && strlen($section) <= 32)
		{
			// Parse list of files in the izu blog dir
			// and select the first one that matches the given section
			// (section key is a 32-char max and should at least
			//  start with 4 digits)

			$abs_dir = $this->mPath->GetBlogDirPath();

			$handle = @opendir($abs_dir);
			if ($handle)
			{
				while (($file = readdir($handle)) !== FALSE)
				{
					if ($file != '.' && $file != '..')
					{
						if (izu_valid_ext($file) && strpos($file, $section) != FALSE)
						{
							// Found one, use it.
							
							$page = $file;
							break;
					    }
					}
				}
				closedir($handle);
			}
		}

		// Return the main entry
		$path = new RPagePath(null, $this->mPath->GetBlogDirPath(TRUE), $page);
		$this->mPath->SetToPagePath($path, $abs_preview_path);

		return TRUE;
	}


	//-----------------------------------------------------------------------


	//***********************
	function isBlogModified()
	//***********************
	// Returns TRUE if the blog has been modified and/or
	// needs to be rebuild.
	// Note that same than for the cache, we rebuild if the source
	// code has been modified.
	{
		global $dir_install;
		global $dir_globset;
		global $dir_locset;
		global $dir_src;

		global $abs_album_path;
	
		// fail if there is no abs path
		if (!is_string($abs_album_path) || $abs_album_path == '')
			return FALSE;

		// get the abs directory & abs main entry

		$abs_src  = $this->mPath->GetSourcePath();
		$abs_main = $this->mPath->GetBlogPath();

		// make sure appropriate subdirs exist

		$this->mPath->CreateTgDirs();

		// does it need to be rebuild?
		// it does if cached html doesn't exist
		
		$is_valid = izu_is_file($abs_main);
	
		// compare cached file date with directory's filedate
		if ($is_valid)
		{
			// To be valid, the cache must exist and must be older than:
			// - the album folder
			// - the local  pref folder modification date (can affect album visibility)
			// - the global pref folder modification date (can affect album visibility)
			// - the RIG source  folder modification date (can affect album content)
			// (in that order, most likely to change tested first)

			$tm_main   = $this->modifDate($abs_main);

			// set the list of files or folders to check
			$check_list = array($abs_src,
								$dir_install    . izu_prep_sep($dir_src),
								$dir_install    . izu_prep_sep($dir_globset),
								$dir_locset);
	
			// cache is valid if not expired
			$is_valid  = !$this->checkExpired($tm_main, $check_list);
		}


		// DEBUG
		// echo "<p>isBlogModified: "; var_dump($is_valid);

		return $is_valid == FALSE;
	}
	
	
	//-----------------------------------------------------------------------
	

	//********************
	function rebuildBlog()
	//********************
	{
		return $this->cleanBlogDir() && $this->parseMaster();
	}


	//*********************
	function cleanBlogDir()
	//*********************
	{	
		$abs_dir = $this->mPath->GetBlogDirPath();
		
		$handle = @opendir($abs_dir);
		if ($handle)
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				if ($file != '.' && $file != '..')
				{

					if (izu_valid_ext($file))
					{
						// DEBUG
						// echo "Removing " . izu_post_sep($abs_dir) . $file . "<p>";

						// Use @ to avoid reporting errors if the file to be unlinked
						// does not exists (can happen if 2 sessions try to rebuild the
						// cache at the same time)
						@unlink(izu_post_sep($abs_dir) . $file);						
				    }
				}
			}
			closedir($handle);
		}
	
		return TRUE;
	}
	
	
	
	//-----------------------------------------------------------------------
	
	
	
	//********************
	function parseMaster()
	//********************
	{
		// DEBUG
		// echo "<p>parseMaster<p>";

		$abs_src  = $this->mPath->GetSourcePath();
		$abs_dir  = $this->mPath->GetBlogDirPath();

		$abs_dir = izu_post_sep($abs_dir);


		// -------
		// Parse master file
		
		$src_file = @fopen($abs_src, "rt");
	

		if (!$src_file)
			return FALSE;

		// -------	
		// Create main.izu and section files files

		$main_file_list   = array();
		$main_index       = sprintf("%04d", count($main_file_list));
		$main_file_list[] = fopen($this->mPath->GetBlogPath($main_index), "w");

		if ($main_file_list[0] == NULL)
			return FALSE;

		$current_main_file = $main_file_list[0];


		// Get RSS path
		$rss_file = fopen($this->mPath->GetBlogPath('rss'), "w");

		if ($rss_file == NULL)
			return FALSE;

		$this->writeRssHeader($rss_file);
		$rss_content = '';

		// Initialize some state

		$key_list			= array();
		$nb_entries			= -1;
		$section_key		= '';
		$first_date			= '';
		$last_date			= '';
		$blog_header		= NULL;
		$add_header_to_main	= TRUE;

		// Section files (current and list of previous)

		$current_section_file   = NULL;		// currently output section file
		$prev_section_file_list = array();	// all *previous* section files

		// Scan input file

		while(!feof($src_file))
		{
			$line = fgets($src_file, 1023);

			// Process izu codes

			$is_izu_tag = FALSE;
			
			if (preg_match('/\[izu:blog-refs:([^\]]+)\]/', $line, $matches) == 1)
			{
				// Izu blog references (header only)
				$is_izu_tag = TRUE;
				$blog_header .= $line;
			}
			else if (strpos($line, "[izu:header:--") !== FALSE)
			{
				// Izu blog header
				$is_izu_tag = TRUE;
				while(!feof($src_file))
				{
					$line = fgets($src_file, 1023);
					
					if (strpos($line, "--]") !== FALSE)
						break;
					else
						$blog_header .= $line;
				}
			}
			else if (preg_match('/\[s:([^:]+)(?::([^\]]+))?\]/', $line, $matches) == 1)
			{
				// New section started

				$is_izu_tag = TRUE;

				// Close previous section file, if any
				if ($current_section_file != NULL)
				{
					$this->writeFooter($current_section_file, $current_main_file, $section_key);
					$this->writeOldArticlesHeader($current_section_file);
					$this->writeRssItemFooter($rss_file, $rss_content);
					$rss_content = '';


					// RM 20041219:
					// Instead of closing the section file, move it to the list
					// of previous section files
					$prev_section_file_list[] = $current_section_file;
					$current_section_file = NULL;
					
					// RM 20041219: in order to avoid exhausting open file descriptors
					// only keep a number of previous sections open
					for($n = count($prev_section_file_list); $n > BLOG_NB_PREV; $n--)
					{
						$f = array_shift($prev_section_file_list);
						if ($f != NULL)
							fclose($f);
					}
				}
				
				// This is a new section, increment counter
				$nb_entries++;

				// If there are more than BLOG_NB_MAIN entries,
				// open a new main file (must be done after nb_entries is incremented above)
				
				if ($nb_entries > 0 && $nb_entries % BLOG_NB_MAIN == 0)
				{
					$this->writeOldArticlesHeader($current_main_file);
					$this->writeOldArticlesLink($main_file_list, $main_index, $first_date, $last_date);

					$n = count($main_file_list);
					$main_index  = sprintf("%04d", $n);

					$current_main_file = fopen($this->mPath->GetBlogPath($main_index), "w");
					$main_file_list[]  = $current_main_file;

					$add_header_to_main = TRUE;
					$first_date			= '';
					$last_date			= '';

					// Close RSS file when we reached the end of the page
					if ($rss_file != NULL)
					{
						$this->writeRssFooter($rss_file);
						fclose($rss_file);
						$rss_file = NULL;
					}
				}

				
				// Create new section file if possible, keep filename around
				// reuse filename if a previous session had the same key

				$date = $matches[1];
				$title = $matches[2];

				// update first-last dates
				if ($first_date == '')
					$first_date = $date;
				$last_date = $date;

				// compute new section key
				//
				// Note RM 20050403: Originally the MD5 was computed using the
				// section index, the date and title. The purpose was to allow
				// two entries to have the same date and title. Yet this is
				// inherently flawed as it means the MD5 changes when new entries
				// are added or reordered. Thus it breaks the _perma_ link.
				// Consequently I'm now using what at wanted in the very first
				// beginning which is only hte date and the title. This breaks
				// the existing blog keys (not too bad). It also means I'm going
				// back to the old behavior of appending to previous sections
				// with the same key.
				
				$section = sprintf(".%s.%s", $date, $title);
				$section_key = $this->BlogEntryKey($date, $title);

				// DEBUG
				// echo "Section : "; var_dump($section); var_dump($section_key); echo "<p>";

				$filename = $key_list[$section_key];					

				if ($filename == NULL)
				{
					// New section's filename
					$filename = sprintf("%s%04d.%s%s",
											$abs_dir,
											$nb_entries,
											$section_key,
											EXT_SOURCE);

					$key_list[$section_key] = $filename;
				}

				// Open new section file or reopen and append to older one
				$file_already_exists  = izu_is_file($filename);
				$current_section_file = fopen($filename, 'a');

				// Write header to file only if file is new
				// Note: we can't use ftell() because it's set to 0 for an append-stream
				if ($current_section_file != NULL && !$file_already_exists)
				{
					$this->writeHeader($current_section_file, $blog_header);
				}

				// Write header to main file only once
				if ($add_header_to_main)
				{
					$this->writeHeader($current_main_file, $blog_header);
					$add_header_to_main = FALSE;
				}
				
				// Write header to RSS file
				$this->writeRssItemHeader($rss_file, $date, $title, $section_key);
				
				// Append a permalink to this section to previous section files
				foreach($prev_section_file_list as $f)
					$this->writePreviousArticleLink($f, $date, $title, $section_key);

			}
			else if (preg_match('/^----/', $line, $matches) == 1)
			{
				// Remove all line separators in blog sections
				$line = '';
			}


			// Now add the *current* line to the blog as needed.
			// Note that any tag currently being parsed is added on purpose
			// unless explicitly altered earlier.

			if ($current_section_file != NULL)
			{
				// Any source line that is processed when a section file is open
				// is logged in that file.
				
				fwrite($current_section_file, $line);
				
				// If the main file is open, append to the main file too

				if ($current_main_file != NULL)
					fwrite($current_main_file, $line);
			}

			// Add content to the current RSS entry if needed

			if ($rss_file != NULL && !$is_izu_tag)
			{
				// RM 20050510 need to perform proper XML encoding of HTML entities
				// A quick hack is to use htmlspecialchars which will convert & < > " and '
				// into they HTML equivalent. This works as these are encoded the same
				// way in HTML and XML.
				//$s = htmlspecialchars($line);
				//if ($s != $line)
				//	echo "<b>'$line'</b> => '$s'<p>";
				$rss_content .= $line;
			}
		
		} // while !feof file

		// Terminate...

		// Write header to main file only once
		if ($add_header_to_main)
		{
			$this->writeHeader($current_main_file, $blog_header);
			$add_header_to_main = FALSE;
		}

		// Close open files

		if ($current_section_file != NULL)
		{
			$this->writeFooter($current_section_file, $current_main_file, $section_key);
			$this->writeOldArticlesHeader($current_section_file);
			fclose($current_section_file);
		}

		// Set archives to current and close mains
		$this->writeOldArticlesHeader($current_main_file);
		$this->writeOldArticlesLink($main_file_list, $main_index, $first_date, $last_date);

		// Close all main files
		foreach($main_file_list as $f)
			if ($f != NULL)
				fclose($f);

		// Close all section files
		foreach($prev_section_file_list as $f)
			if ($f != NULL)
				fclose($f);

		if ($rss_file != NULL)
		{
			$this->writeRssItemFooter($rss_file, $rss_content);
			$this->writeRssFooter($rss_file);
			fclose($rss_file);
		}

		fclose($src_file);

		// -------	

		return TRUE;
	}


	//**********************************
	function writeHeader($file, $header)
	//**********************************
	{
		// Write header to file

		if (is_string($header))
		{
			if ($file != NULL)
				fwrite($file, $header);
		}
	}
	

	//************************************
	function writeOldArticlesHeader($file)
	//************************************
	{
		// Write header to file

		if ($file != NULL)
		{
			fwrite($file, "\n\nBlog Archives: /\n");
			$this->writePermalink($file, "Most recent posts", '', '');
			fwrite($file, "&nbsp;|&nbsp;");
			$this->writePermalink($file, "[[RSS]", 'rss', 'br');
		}
	}
	
	
	//***********************************************************
	function writePreviousArticleLink($file, $date, $title, $key)
	//***********************************************************
	{
		// Write header to file

		if ($file != NULL)
		{
			$date = izu_pretty_date($date);
			if ($title != '')
				$sep = '&nbsp;&laquo;&raquo;&nbsp;';
			else
				$sep = '';
			$this->writePermalink($file, "$date$sep$title", $key, 'br');
		}
	}
	
	
	//*************************************************************************
	function writeOldArticlesLink($files, $main_index, $first_date, $last_date)
	//*************************************************************************
	{
		$s = izu_pretty_date($first_date) . '&nbsp;-&nbsp;' . izu_pretty_date($last_date);

		for($n = count($files)-2; $n >= 0; $n--)
		{
			$this->writePermalink($files[$n], $s, $main_index, 'br');
		}
	}

	

	//************************************************
	function writePermalink($file, $title, $key, $sep)
	//************************************************
	{
		if ($file != NULL && is_string($key) && is_string($title))
		{
			$s = "[izu:permalink:" . $sep . ":" . $title .":" . $key . "]\n";
			fwrite($file, $s);
		}
	}
	
	

	//****************************************
	function writeFooter($file1, $file2, $key)
	//****************************************
	{
		$this->writePermalink($file1, "[[permalink]", $key, 'hr');
		$this->writePermalink($file2, "[[permalink]", $key, 'hr');
	}



	
	//****************************
	function writeRssHeader($file)
	//****************************
	{
		// DEBUG
		// echo "writeRssHeader $file<p>";

		if ($file != NULL)
		{
			global $html_encoding;
			global $display_title;
			global $display_softname;
			global $pref_copyright_name;
			global $izu_version;
			global $html_language_code;

			$feed		= izu_self_url(-1, -1, -1, 's=rss');
			$title		= htmlentities($display_title);
			$name		= htmlentities($pref_copyright_name);
			$copy		= htmlentities("Copyright " . date('Y') . " ," . $pref_copyright_name);
			$generator	= htmlentities($display_softname . " " . $izu_version);

			// References:
			// Dublin Core: http://dublincore.org/documents/dcmi-terms/
			// http://www.w3.org/TR/NOTE-datetime (date format for dc:date)
			// http://feedvalidator.org/docs/rss2.html
			
			$s  = "<?xml version=\"1.0\" encoding=\"$html_encoding\"?>\n";
			$s .= "<rss version=\"2.0\" xmlns:dc='http://purl.org/dc/elements/1.1/'>\n";
			$s .= "<channel>\n";

			// mandatory channel tags
			$s .= "<title>$display_title</title>\n";
			$s .= "<link>$feed</link>\n";
			$s .= "<description>$title</description>\n";

			// optional channel tags
			$s .= "<copyright>$copy</copyright>\n";
			$s .= "<generator>$generator</generator>\n";
			$s .= "<dc:creator>$name</dc:creator>\n";

			if ($html_language_code)
				$s .= "<language>$html_language_code</language>\n";

			// caching options

			// TBDL RM 20041128: govern these values from $pref defaults, override with [izu:rss:...] tags

			$s .= "<ttl>120</ttl>\n";

			$hours = array(1, 2, 3, 4, 5, 6, 7, 9, 11, 13, 15, 17, 19, 22);
			$days  = array( /* 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' */);

			if ($hours != null && is_array($hours) && count($hours) > 0)
			{
				$s .= "<skipHours>";
				foreach($hours as $i)
					$s .= "<hour>$i</hour>\n";
				$s .= "</skipHours>\n";
			}

			if ($days != null && is_array($days) && count($days) > 0)
			{
				$s .= "<skipDays>";
				foreach($days as $i)
					$s .= "<day>$i</day>\n";
				$s .= "</skipDays>\n";
			}


			fwrite($file, $s);
   		}
	}

	
	//****************************
	function writeRssFooter($file)
	//****************************
	{
		// DEBUG
		// echo "writeRssFooter $file<p>";

		if ($file != NULL)
		{
			$s = "</channel>\n</rss>\n";
			fwrite($file, $s);
   		}
	}

	//*****************************************************
	function writeRssItemHeader($file, $date, $title, $key)
	//*****************************************************
	{
		// DEBUG
		// echo "writeRssItemHeader $file<p>";

		if ($file != NULL)
		{
			if (!is_string($title) || strlen($title) == 0)
				$title = '(no title)';

			$title = htmlentities($title);

			if (is_string($key) && strlen($key) > 0)
				$key = "s=" . $key;
			else
				$key = '';
		
			$permalink = izu_self_url(-1, -1, -1, $key);

			// References for date formating:
			// Dublin Core: http://dublincore.org/documents/dcmi-terms/
			// http://www.w3.org/TR/NOTE-datetime (date format for dc:date)
			// Date must be provided in the form
			// 	YYYY-MM-DDThh:mm:ssTZD
			// 	YYYY-MM-DDThh:mmTZD
			//	YYYY-MM-DD

			if (preg_match("@([0-9]{4})[-/]?([0-9]{2})[-/]?([0-9]{2})[- /:]?([0-9]{2})[-:/h]?([0-9]{2})[-:/m]?([0-9]{2})@", $date, $matches) == 1)
			{
				$tzd = date('O'); // or $tzd = 'Z'; (for UTC)
				$date = sprintf("%s-%s-%sT%s:%s:%s%s", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6], $tzd);
			}
			else if (preg_match("@([0-9]{4})[-/]?([0-9]{2})[-/]?([0-9]{2})[- /:]?([0-9]{2})[-:/h]?([0-9]{2})@", $date, $matches) == 1)
			{
				$tzd = date('O'); // or $tzd = 'Z'; (for UTC)
				$date = sprintf("%s-%s-%sT%s:%s%s", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $tzd);
			}
			else if (preg_match("@([0-9]{4})[-/]?([0-9]{2})[-/]?([0-9]{2})@", $date, $matches) == 1)
			{
				$date = sprintf("%s-%s-%s", $matches[1], $matches[2], $matches[3]);
			}
	
			
			$s  = "<item>\n";
			$s .= "<title>$title</title>\n";
			$s .= "<link>$permalink</link>\n";
			$s .= "<dc:date>$date</dc:date>\n";
			$s .= "<description>\n";
      
			fwrite($file, $s);
   		}
	}

	//******************************************
	function writeRssItemFooter($file, $content)
	//******************************************
	{
		// DEBUG
		// echo "writeRssItemFooter $file<p>";

		if ($file != NULL)
		{	
			// Render izu content as HTML
			$content = $this->renderString($content);
			
			// Remove unnecessary <p> at the beginning of an rss item content
			// (this happens when there's an empty line after a [s:..] tag).
			// In fact remove also any white spacing that can be found there.
			while(preg_match("@^(<p>|\n+|\r+|\t+| +)@", $content, $matches) == 1)
			{
				$content = substr($content, strlen($matches[1]));
			}
			
			// Transform HTML entities as appropriate for XML content
			// RM 20050510 htmlentities => htmlspecialchars
			// This works as htmlspecialchars only transforms & < > " and '
			// which have the same encoding in XML and HTML.
			// Using htmlentities was wrong as it also encode other HTML
			// entities in entities which are NOT part of XML (such as accents)
			$content = htmlspecialchars($content);
			fwrite($file, $content);

			$s = "</description>\n</item>\n";
      
			fwrite($file, $s);
   		}
	}


} // end RPage

//-----------------------------------------------------------------------




//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.4  2005-05-12 15:50:26  ralfoide
//	Fix: Empty lines that consist of solely white-space characters in RPage
//	Fix: Remove unnecessary <p> at beginning of RSS post content
//
//	Revision 1.3  2005/05/10 18:06:26  ralfoide
//	Fixed a minor bug in the RSS export: accents where improperly encoded as HTML entities.
//	
//	Revision 1.2  2005/04/05 18:53:44  ralfoide
//	Started work on version 1.1
//	Changed blog entries keys from MD5 to encoded date/title clear text.
//	Added internal anchor references to blog entries.
//	
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.7  2004/12/20 07:01:37  ralf
//	New minor features. Version 0.9.4
//	
//	Revision 1.6  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.5  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.4  2004/11/28 22:38:00  ralf
//	Version 0.9.1: RSS support with ETag/If-Modified.
//	
//	Revision 1.3  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.2  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
//	
//	Revision 1.1  2004/11/21 18:17:12  ralf
//	Blog support added. Experimental.
//	
//-------------------------------------------------------------
?>