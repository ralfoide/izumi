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


//---------------------------------------------------------

define("EXT_SOURCE", ".izu");
define("EXT_CACHE",  ".html");
define("DIR_CACHE",  "izu_dir");
define("FILE_CACHE", "izu_file_");

define("EXT_BLOG",   ".blog");
define("DIR_BLOG",   "blog_dir_");
define("FILE_BLOG",	 "_main");
define("FILE_RSS",	 "_rss.txt");

//---------------------------------------------------------



//*************
class RPagePath
//*************
{
	var $mPath;
	var $mDir;
	var $mPage;
	var $mIsBlog;
	var $mIsRss;
	var $mAbsSource;


	//**********************************************
	function RPagePath($path, $dir = -1, $page = -1)
	//**********************************************
	// Initializes the class
	{
		global $abs_album_path;

		$this->mPath   = '';
		$this->mDir    = '';
		$this->mPage   = '';
		$this->mIsBlog = FALSE;
		$this->mIsRss  = FALSE;

		$this->mAbsSource = izu_post_sep($abs_album_path);
		
		if (is_string($dir) || is_string($page))
		{
			// check if path points to a blog
			
			$this->mIsBlog = (substr($page, -5) == EXT_BLOG);	// 5 == strlen(".blog")
			if ($this->mIsBlog)
				$page = substr($page, 0, strlen($page)-5);

			// use given dir and page arguments
			if (is_string($dir))
				$this->mDir = izu_post_sep($dir);

			if (is_string($page))
			{
				// remove source extension if any
				$this->mPage = preg_replace("/" . EXT_SOURCE . "$/", '', $page);
			}
		}
		else if (is_string($path))
		{
			// check if path points to a blog
			
			$this->mIsBlog = (substr($path, -5) == EXT_BLOG);	// 5 == strlen(".blog")
			if ($this->mIsBlog)
				$path = substr($path, 0, strlen($path)-5);

			// use the given path and extract the dir and page

			// explode the path
			$dirs = explode(SEP, $path);
		
			$n = count($dirs);
		
			if ($n > 0)
			{
				$this->mDir = izu_post_sep($path);

				// is the path a directory or a file?
				// if not a valid directory, it will be assumed to be a file

				if (!izu_is_dir($this->mDir))
				{	
					// assume it to be a file, get the filename
					$this->mPage = $dirs[$n-1];

					if (is_string($this->mPage))
					{
						// remove source extension if any
						$this->mPage = preg_replace("/" . EXT_SOURCE . "$/", '', $this->mPage);
					}
		
					// recompose the directory without the last component
					if ($n > 1)
					{
						unset($dirs[$n-1]);
						$this->mDir = izu_post_sep(implode(SEP, $dirs));
					}
					else
					{
						$this->mDir = '';
					}
				}
			} // if n > 0
		} // if path

		// reconstruct the full path as needed
		$this->mPath = $this->mDir . $this->mPage;
	}


	
	//************************************************
	function SetToPagePath(&$path, $abs_source = NULL)
	//************************************************
	{
		$this->mPath = $path->mPath;
		$this->mDir  = $path->mDir;
		$this->mPage = $path->mPage;
		
		if ($abs_source != NULL && is_string($abs_source) && $abs_source != '')
		{
			$this->mAbsSource = izu_post_sep($abs_source);
		}
	}



	//*******************
	function DebugPrint()
	//*******************
	{
		echo "<P>Class Path: ";
		var_dump($this->mPath);

		echo " | dir: ";
		var_dump($this->mDir);

		echo " | page: ";
		var_dump($this->mPage);
		echo "<br>\n";
	}


	//****************
	function IsEmpty()
	//****************
	{
		return ($this->mPath == '' && $this->mDir == '' && $this->mPage == '');
	}


	//**************
	function IsDir()
	//**************
	{
		return ($this->mPage == '');
	}


	//***************
	function IsPage()
	//***************
	{
		return ($this->mPage != '');
	}



	//***************
	function IsBlog()
	//***************
	{
		return ($this->mIsBlog == TRUE);
	}


	//**************
	function IsRss()
	//**************
	{
		return ($this->mIsRss == TRUE);
	}


	//******************
	function DirExists()
	//******************
	{
		$n = $this->GetSourcePath();
		return file_exists($n) && is_dir($n);
	}


	//*******************
	function PageExists()
	//*******************
	{
		$n = $this->GetSourcePath();
		return file_exists($n) && is_file($n);
	}


	//********************
	function GetPageSize()
	//********************
	{
		if ($this->IsPage() && $this->PageExists())
			return filesize($this->GetSourcePath());

		return 0;
	}


	//**********************
	function GetPrettyName()
	//**********************
	{
		if ($this->IsDir())
			return izu_pretty_name($this->mDir);
		else
			return izu_pretty_name($this->mPage);
	}


	//*******************
	function GetSelfUrl()
	//*******************
	// RM 20041120 if the page is a blog, add the blog extension
	{
		$str = $this->mPath;

		if ($this->mIsBlog)
			$str .= EXT_BLOG;

		return izu_self_url($str);
	}


	//**********************
	function GetSourcePath()
	//**********************
	{
		if ($this->IsDir())
			return $this->mAbsSource . $this->mPath;
		else
			return $this->mAbsSource . $this->mPath . EXT_SOURCE;
	}


	//*******************************************
	function GetCachePath($use_lang  = TRUE,
						  $use_theme = TRUE,
						  $use_user  = TRUE,
						  $ext       = EXT_CACHE)
	//*******************************************
	{
		global $abs_preview_path;
		global $izu_lang;
		global $izu_theme;
		global $izu_user;

		if ($this->IsDir())
			$s = izu_post_sep($abs_preview_path) . $this->mDir . DIR_CACHE;
		else
			$s = izu_post_sep($abs_preview_path) . $this->mDir . FILE_CACHE . $this->mPage;

		// Note that the cache file *MAY* depend on the follwing variables:
		// - current loggued user name (different users have different visibilities)
		// - color theme name
		// - language name

		if ($use_lang === TRUE)
			$s .= '_' . izu_simplify_filename($izu_lang);

		if ($use_theme === TRUE)
			$s .= '_' . izu_simplify_filename($izu_theme);

		if ($use_user === TRUE)
			$s .= '_' . izu_simplify_filename($izu_user);

		if (is_string($ext))
			$s .= $ext;

		return $s;
	}


	//****************************************
	function GetBlogDirPath($relative = FALSE)
	//****************************************
	{
		global $abs_preview_path;

		$s = $this->mDir . SEP . DIR_BLOG . $this->mPage;

		if (!$relative)
			$s = izu_post_sep($abs_preview_path) . $s;

		return $s;
	}


	//**************************************************************
	function GetBlogPath($index = '', $key = '', $page_only = FALSE)
	//**************************************************************
	{
		$s = '';

		if (!$page_only)
		{
			$s = $this->GetBlogDirPath() . SEP;
		}

		// make sure the arguments are clean (no "." and no SEP)
		$index     = str_replace('.' , '', $index);
		$index     = str_replace(SEP , '', $index);
		$index     = str_replace(SEP2, '', $index);
		$key = str_replace('.' , '', $key);
		$key = str_replace(SEP , '', $key);
		$key = str_replace(SEP2, '', $key);

		$this->mIsRss = ($key == 'rss' || $index == 'rss');

		if ($this->mIsRss)
			// A request to the most-recent-entries RSS is requested
			$s .= FILE_RSS;
		else if (strlen($key) == 4)
			// A 4-digit index stored in the permalink key: this is a main page index
			$s .= FILE_BLOG . $key;
		else if (strlen($key) == 32 && strlen($index) == 4)
			// A 32-character md5 for a permalink key with a permalink index
			$s .= $index . "." . strtolower($key);
		else if (strlen($index) == 4)
			// A 4-digit main page index
			$s .= FILE_BLOG . $index;
		else if (strlen($key) == 32)
			// A 32-character md5 for a permalink key
			$s .= strtolower($key);
		if ($key == '' && $index == '')
			$s .= FILE_BLOG . '0000';

		$s .= EXT_SOURCE;

		return $s;
	}


	//*********************
	function CreateTgDirs()
	//*********************
	{
		izu_create_preview_dir($this->mDir);
		izu_create_option_dir ($this->mDir);
		
		if ($this->mIsBlog)
			izu_create_preview_dir($this->GetBlogDirPath(TRUE));
	}


} // RPagePath


//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.6  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.5  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.4  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.3  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
//	
//	Revision 1.2  2004/11/21 18:17:12  ralf
//	Blog support added. Experimental.
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//-------------------------------------------------------------
?>
