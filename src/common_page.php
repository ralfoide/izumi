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

Globals:
	$current_path	-- directories + page name
	$current_page	-- page name (without the directories)
	$current_dir	-- directories (without the page name) 

*/

//-----------------------------------------------------------------------


define("DIR_HTML_CACHE_NAME", "__izu_dir.html");

//-----------------------------------------------------------------------


//******************************************
function izu_prepare_page($path, $title = "")
//******************************************
{
	global $abs_album_path;
	global $current_path;
	global $current_dir;
	global $current_page;
	global $display_title;
	global $display_page_title;
	global $html_album, $html_none;


	// get clean path	
	$current_path = izu_decode_argument($path);
	$current_dir = "";
	$current_page = "";

	// explode the path
	$dirs = explode(SEP, $current_path);

	$n = count($dirs);

	if ($n > 0)
	{
		// is the path a directory or a file?
		// if not a valid directory, it will be assumed to be a file
		$abs_path = izu_post_sep($abs_album_path) . izu_post_sep($current_path);
		if (izu_is_dir($abs_path))
		{
			// directory exists... explore
			$current_dir = izu_post_sep($current_path);
		}
		else
		{	
			// assume it to be a file, get the filename
			$current_page = $dirs[$n-1];

			// recompose the directory without the last component
			if ($n > 1)
			{
				unset($dirs[$n-1]);
				$current_dir = izu_post_sep(implode(SEP, $dirs));
			}
		}
	}

	if (!$title)
		$title = $html_album;

	if ($current_page != "")
	{
		$pretty = izu_pretty_name($current_page);
		$display_title = "$title - " . $pretty;
		$display_page_title = "$html_album - " . $pretty;
	}
	else
	{
		$display_title = "$title - $html_none";
		$display_page_title = "$html_album - $html_none";
	}
	
	return TRUE;
}


//-----------------------------------------------------------------------


//*****************************
function izu_process_directory()
//*****************************
// Returns FALSE if there is no page to include
// Returns a string if there is a page to include
{
	global $dir_src;
	global $current_dir;
	global $abs_album_path;
	global $abs_preview_path;

	// fail if there is no abs path
	if (!is_string($abs_album_path) || $abs_album_path == '')
		return FALSE;

	// get the abs directory & abs html cache

	$abs_dir  = izu_post_sep($abs_album_path) . izu_post_sep($current_dir);
	$abs_html = izu_post_sep($abs_preview_path) . izu_post_sep($current_dir) . DIR_HTML_CACHE_NAME;

	// make sure appropriate subdirs exist

	izu_create_preview_dir($current_dir);
	izu_create_option_dir ($current_dir);


	echo "<p>directory '$current_dir' ==&gt; '$abs_dir' <br>";
	echo "Cache: '$abs_html'<br>";


	// does it need to be rebuild?
	// it does if cached html doesn't exist
	
	$rebuild = !izu_is_file($abs_html);

	// compare cached file date with directory's filedate
	if (!$rebuild)
	{
		$tm_txt  = filemtime($abs_dir);
		$tm_html = filemtime($abs_html);
		$tm_vers = filemtime(izu_require_once("version.php", $dir_src));

		$rebuild = ($tm_html < $tm_txt) || ($tm_html < $tm_html);
	}

	echo "Needs rebuild: " . ($rebuild ? "Yes" : "No") . "<br>";

	if ($rebuild)
	{
		// read directory content
	
		izu_read_directory();

		// render directory

		return izu_require_once("template_dir.php", $dir_src);
	}
	else // no need to rebuild
	{
		return $abs_html;
	}

	return FALSE;
}


//**************************
function izu_read_directory()
//**************************
{
	global $current_folder_list;
	global $current_page_list;

	$current_folder_list = array();
	$current_page_list   = array();

	$handle = @opendir($abs_dir);
	if ($handle)
	{
		while (($file = readdir($handle)) !== FALSE)
		{
			if ($file != '.' && $file != '..' && izu_is_visible(-1, $current_dir, $file))
			{
				$abs_file = $abs_dir . $file;
				if (izu_is_dir($abs_file))
				{
					$current_folder_list[] = $current_dir . $file;
				}
				else if (izu_valid_ext($file))
				{
					// remove extension if any
					$n = strlen($file);
					if ($n > 4 && substr($file, -4) == ".txt")
						$file = substr($file, 0, -4);

					$current_page_list[] = $current_dir . $file;
			    }
			}
		}
		closedir($handle);
	}

	return TRUE;
}




//*************************
function izu_dir_has_pages()
//*************************
// Returns TRUE if there are pages to display
{
	global $current_page_list;

	return is_array($current_page_list) && count($current_page_list) > 0;
}



//***************************
function izu_dir_has_folders()
//***************************
// Returns TRUE if there are folders to display
{
	global $current_folder_list;

	return is_array($current_folder_list) && count($current_folder_list) > 0;
}



//***********************************
function izu_display_directory_pages()
//***********************************
// Returns TRUE if could display the directory, FALSE otherwise
{
	global $current_page_list;

	// display pages list

	foreach($current_page_list as $name)
	{
		$pretty = izu_pretty_name($name);
		$link   = izu_self_url($name);

		echo "[&nbsp;] <a href=\"$link\">$pretty</a> <br>\n";
	}

	return TRUE;
}


//*************************************
function izu_display_directory_folders()
//*************************************
// Returns TRUE if could display the directory, FALSE otherwise
{
	global $current_folder_list;

	// display directories list


	izu_display_section("Folders");

	foreach($current_folder_list as $name)
	{
		$pretty = izu_pretty_name($name);
		$link   = izu_self_url($name);

		echo "[+] <a href=\"$link\">$pretty</a> <br>\n";
	}


	return TRUE;
}



//-----------------------------------------------------------------------



//************************
function izu_process_page()
//************************
// Returns FALSE if there is no page to include
// Returns a string if there is a page to include
{
	global $current_path;
	global $current_page;
	global $current_dir;
	global $current_page_source;
	global $abs_album_path;
	global $abs_preview_path;
	global $dir_install;
	global $dir_src;

	// fail if there is no abs path
	if (!is_string($abs_album_path) || $abs_album_path == '')
		return FALSE;

	// fail if there is no current page
	if (!is_string($current_page) || $current_page == '')
		return FALSE;

//	echo "<p>page '$current_page'<br>";


	// make sure appropriate subdir exist

	izu_create_preview_dir($current_dir);
	izu_create_option_dir ($current_dir);

	// get the abs source filename and the cached html filenmae

	$abs_txt  = izu_post_sep($abs_album_path)   . $current_path . ".txt";
	$abs_html = izu_post_sep($abs_preview_path) . $current_path . ".html";

//	echo "Source: '$abs_txt'<br>";
//	echo "Cache: '$abs_html'<br>";

	// give up if source does not exists
	if (!izu_is_file($abs_txt))
		return FALSE;

	// does it need to be rebuild?
	// it does if cached html doesn't exist
	
	$rebuild = !izu_is_file($abs_html);

	// compare cached file date with source's filedate
	if (!$rebuild)
	{
		$tm_txt  = filemtime($abs_txt);
		$tm_html = filemtime($abs_html);
		$tm_vers = filemtime(izu_require_once("version.php", $dir_src));

		$rebuild = ($tm_html < $tm_txt) || ($tm_html < $tm_html);
	}

//	echo "Needs rebuild: " . ($rebuild ? "Yes" : "No") . "<br>";

	if ($rebuild)
	{
		$current_page_source = $abs_txt;
		return izu_require_once("template_page.php", $dir_src);
	}
	else
	{
		return $abs_html;
	}


	return FALSE;
}


//************************
function izu_display_page()
//************************
{
	global $current_page_source;

	izu_render_text($current_page_source);
}


//********************************
function izu_render_text($filepath)
//********************************
{
	// DEBUG
	// echo "<p>izu_render_text: '$filepath'<p>";

	
	$file = @fopen($filepath, "rt");

	if (!$file)
		return FALSE;

	$was_ul  = FALSE;
	$was_pre = FALSE;
	$was_bq  = FALSE;

	while(!feof($file))
	{
		// read till we get a full line
		$line = "";

		$same_line = TRUE;
		$is_comment = FALSE;

		while($same_line && !feof($file))
		{
			$temp = fgets($file, 1023);

			if (is_string($temp))
			{
//				if ($line == "")
//					$is_comment = ($temp[0] == '#');
	
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
	
				// store if not a comment line
				if (!$is_comment)
					$line .= $temp;
			}
		} // while same line

		// process line state
//var_dump($line);	echo "<br>\n";

		// need a valid line (line can be empty)
		if (!is_string($line) || $is_comment)
			continue;

		// empty lines are paragraphs
		if ($line == '')
		{
			echo '<p>';
			continue;
		}

		// performs basic replacements
		
		$p = array();
		$r = array();

		// '----' at the beginning is an <hr>
		$p[] = "/----/";
		$r[] = "<hr>";
				
		// '%%%' at the end means a line break is necessary
		// loosen this to be "anywhere"
		$p[] = "/%%%/";
		$r[] = "<br>";

		// set __xx__ as bold
		$p[] = "/__(.*)__/";
		$r[] = "<b>\\1</b>";
		
		// set ''xx'' as italics
		$p[] = "/''(.*)''/";
		$r[] = "<i>\\1</i>";

		// format external links
		
		// named link: [name|http:\\blah blah]
		$p[] = "/\\[([^\\|\\]]+)\\|(http:\\/\\/[^\\]]+)\\]/";
		$r[] = "<a href=\"\\2\">\\1</a>";

		// unnamed link: [http:\\blah blah]
		$p[] = "/\\[(http:\\/\\/.+)\\]/";
		$r[] = "<a href=\"\\1\">\\1</a>";

		// unformated link: http:\\blah (link cannot contain quotes)
		// and must not be surrounded by quotes
		$p[] = "/([^\"])(http:\\/\\/[^ \"]+)([^\"])/";
		$r[] = "\\1<a href=\"\\1\">\\2</a>\\3";

		// format internal links

		// named link: [name|http:\\blah blah]
		$p[] = "/\\[([^\\|\\]]+)\\|([A-Z][a-z]+[A-Za-z]*)\\]/e";
		$r[] = "izu_create_url(\"\\2\",\"\\1\")";

		// unnamed link: [http:\\blah blah]
		$p[] = "/\\[([A-Z][a-z]+[A-Za-z]*)\\]/e";
		$r[] = "izu_create_url(\"\\1\")";

		// unformated link: http:\\blah (link cannot contain quotes)
		// and must not be surrounded by quotes
		$p[] = "/([^=A-Za-z])([A-Z][a-z]+[A-Z][a-z]+[A-Za-z]*)([^\"])/e";
		$r[] = "\"\\1\" . izu_create_url(\"\\2\") . \"\\3\"";

		// perform actions

		$line = preg_replace($p, $r, $line);

		// blockquote & pre management
		if (preg_match("/^\t(.*)$/", $line, $matches) == 1)
		{
			// open pre as needed
			if (!$was_bq)
			{
				echo "<blockquote>";
				$was_bq = TRUE;
			}
			
			// make line a list item
			$line = $matches[1];
		}
		else if ($was_bq)
		{
			echo "</blockquote>";
			$was_bq = FALSE;
		}		

		if (preg_match("/^    (.*)$/", $line, $matches) == 1)
		{
			// open pre as needed
			if (!$was_pre)
			{
				echo "<pre>";
				$was_pre = TRUE;
			}
			
			// make line a list item
			$line = $matches[1];
		}
		else if ($was_pre)
		{
			echo "</pre>";
			$was_pre = FALSE;
		}		
		

		// list management
		if (preg_match("/^\*[ \t]+(.*)$/", $line, $matches) == 1)
		{
			// open list as needed
			if (!$was_ul)
			{
				echo "<ul>";
				$was_ul = TRUE;
			}
			
			// make line a list item
			$line = "<li>" . $matches[1] . "</li>";
		}
		else if ($was_ul)
		{
			// close previous list
			echo "</ul>";
			$was_ul = FALSE;
		}


		// output final line		

		echo $line . "\n";

	} // end while feof

	fclose($file);

//	echo "</pre>";
	return TRUE;	
}


//***************************************
function izu_create_url($page, $name = "")
//***************************************
{
	$link = izu_self_url($page);

	if ($name == "")
		$name = izu_pretty_name($page);


	return "<a href=\"$link\">$name</a>";
}


//-----------------------------------------------------------------------


//*************************
function izu_process_close()
//*************************
{
}


//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.4  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.3  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.2  2004/12/04 22:22:02  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//-------------------------------------------------------------
?>
