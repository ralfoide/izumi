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

*/
//-----------------------------------------------------------------------

define("SEP_URL", "/");
if (PHP_OS == 'WINNT')
{
	define("SEP", "\\");
	define("SEP2", "");			// Windows: accept either / or \ in paths
}
else // Un*x
{
	define("SEP", "/");
	define("SEP2", "\\");		// Unix: converts \ to / in paths
}


define("CURRENT_ALBUM_ARROW",	"&nbsp;=&gt;&nbsp;");
define("SOFT_NAME",				"Izumi");
define("ALBUM_ICON",			"album_icon.jpg");
define("ALBUM_OPTIONS_TXT",		"options.txt");
define("ALBUM_OPTIONS_XML",		"options.xml");

define("DESCRIPTION_TXT",		"descript.ion");		// RM 20030713
define("FILEINFODIZ_TXT",		"file_info.diz");

// start timing...
$time_start = izu_getmicrotime();

//var_dump(izu_require_once("prefs.php", $dir_globset));
// read site-prefs and then override with local prefs, if any
require_once(izu_require_once("prefs.php", $dir_globset));

if (izu_is_file ($dir_locset . "prefs.php"))
	require_once($dir_locset . "prefs.php");

// setup...
require_once(izu_require_once("version.php",    $dir_src));

izu_read_prefs_paths();
izu_handle_cookies();


// DEBUG default prefs/curr for lang and theme
// echo "<p> izu_version = "   ; var_dump($izu_version);
// echo "<p> pref_default_lang = "   ; var_dump($pref_default_lang);
// echo "<p> current_language = "; var_dump($current_language);
// echo "<p> pref_default_theme = "; var_dump($pref_default_theme);
// echo "<p> current_theme = "   ; var_dump($current_theme);


// include language strings
//-------------------------
// RM 20020714 fix: always load the str_en first
require_once(izu_require_once("str_en.php", $dir_src));

// and override with other language if not english

// DEBUG
// izu_check_src_file($dir_install . $dir_src . "str_$current_language.php");


// Fix (Paul S. 20021013): if requested lang doesn't exist, revert to english
if (!isset($current_language) || !izu_is_file($dir_install . $dir_src . "str_$current_language.php"))
	$current_language = $pref_default_lang;

if (is_string($current_language) && $current_language != 'en')
{
	require_once(izu_require_once("str_$current_language.php", $dir_src, $abs_upload_src_path));
}

// include theme strings
//----------------------

// DEBUG
// izu_check_src_file($dir_install . $dir_src . "theme_$current_theme.php");

if (!isset($current_theme) || !izu_is_file($dir_install . $dir_src . "theme_$current_theme.php"))
	$current_theme = $pref_default_theme;
require_once(izu_require_once("theme_$current_theme.php", $dir_src, $abs_upload_src_path));


// load common source code -- note these do not use the src_upload override
require_once(izu_require_once("common_display.php", $dir_src));


izu_setup();
izu_create_option_dir("");



//-----------------------------------------------------------------------


//**************************************
function izu_html_error($title_str,
					   $error_str,
					   $file_str = NULL,
					   $php_str  = NULL)
//**************************************
{
	global $color_table_bg;
	global $color_error1_bg;
	global $color_error2_bg;

	// Trick to close the title (if we were writing the title and the header)
	// and to close enough tables to be wide-screen. This assumes that most browser
	// will silently ignore what is blatlantly offensive!
	echo "</title></head></table></table></table></table>";
	echo "<body>\n";

	if (!$title_str)
		$title_str = "A Runtime Error Occured";

	echo "<center><table border=1 bgcolor=\"$color_error1_bg\" width=\"100%\" cellpadding=\"5\">\n";

	// title
	echo "<tr><td bgcolor=\"$color_error1_bg\"><center><font size=\"+2\">$title_str</font></center></td></tr>\n";

	// description
	echo "<tr><td bgcolor=\"$color_error2_bg\">\n $error_str\n </td></tr>\n";

	// file argument
	if ($file_str != NULL)
	{
		if (is_array($file_str))
		{
			echo "<tr><td bgcolor=\"$color_error2_bg\">\n<b>File:</b><pre>";
			var_dump($file_str);
			echo "<pre></td></tr>\n";
		}
		else
		{
			echo "<tr><td bgcolor=\"$color_error2_bg\">\n<b>File:</b> $file_str\n </td></tr>\n";
		}
	}

	// php error msg
	if ($php_str != NULL)
	{
		if (is_array($php_str))
		{
			echo "<tr><td bgcolor=\"$color_error2_bg\">\n<b>PHP Error:</b><pre>";
			var_dump($php_str);
			echo "</pre>\n</td></tr>\n";
		}
		else
		{
			echo "<tr><td bgcolor=\"$color_error2_bg\">\n<b>PHP Error:</b> $php_str\n </td></tr>\n";
		}
	}

	echo "</table></center><p>\n";

	// Also assumes that browsers will continue displaying the HTML after a
	// bad </body>.
	echo "</body>\n";

	return FALSE;
}

//-----------------------------------------------------------------------


//*********************************************************************
function izu_require_once($filename, $main_dir, $abs_override_dir = "")
//*********************************************************************
// RM 20030308
//
// Includes a PHP source file, looking in $dir_install + $main_dir
// or $abs_override_dir. The override dir is checked FIRST and is ABSOLUTE!
// it's purpose is to override the main file with the overriding one.
//
// It is ok for the override dir not to exist or not contain the file.
// It is mandatory that the main dir exists and contains the file.
//
// IMPORTANT: require_once uses the caller's scope which means the
// file can't be included/required here or it wouldn't have a global scope
// thus this function actually returns a string with the file to be
// required and it's up to the caller to actually perform the require_once().
{
	global $dir_install, $abs_upload_src_path;

	// DEBUG
	// echo "<p>izu_require_once: filename='$filename', main_dir='$main_dir', abs_override_dir='$abs_override_dir' \n";

	$main = izu_post_sep($dir_install) . izu_post_sep($main_dir);
	$over = izu_post_sep($abs_override_dir);

	// check params

	if (!$filename)
	{
		return izu_html_error("Invalid parameter!",
			                  "Empty 'filename' argument in function izu_require_once!",
	            		      $main_dir);
	}

	if (!$main_dir)
	{
		return izu_html_error("Invalid parameter!",
			                  "Empty 'main_dir' argument in function izu_require_once!",
	            		      $filename);
	}

	// check main file exists -- it must, even if we're going to use the override

	if (!izu_check_src_file($main . $filename))
		return FALSE;

	// check override file and use it exists
	if ($abs_override_dir && izu_is_file($over . $filename))
	{
		return $over . $filename;
	}

	// otherwise default to the main one
	return $main . $filename;
}


//-----------------------------------------------------------------------

//**********************************************
function izu_get($array, $name, $default = NULL)
//**********************************************
{
	if (isset($array) && isset($name) && isset($array[$name]))
		return $array[$name];
	
	return $default;
}

//-----------------------------------------------------------------------

//*************************
function izu_is_file($name)
//*************************
{
    return file_exists($name) && is_file($name);
}

//************************
function izu_is_dir($name)
//************************
{
    return file_exists($name) && is_dir($name);
}

//*************************
function izu_getmicrotime()
//*************************
// extracted from PHP doc for microtime()
{
    list($usec, $sec) = explode(" ", microtime()); 
    return ((float)$usec + (float)$sec); 
} 


//*************************
function izu_time_elapsed()
//*************************
{
	global $time_start;
	return sprintf("%2.2f", izu_getmicrotime() - $time_start);
}


//*************************
function izu_prep_sep($str)
//*************************
{
	if ($str && $str[0] != SEP)
		return SEP . $str;
	else
		return $str;
}


//*************************
function izu_post_sep($str)
//*************************
{
	if ($str && $str[strlen($str)-1] != SEP)
		return $str . SEP;
	else
		return $str;
}


//*************************
function izu_post_url($str)
//*************************
// RM 20030629 v0.6.3.4
{
	if ($str && $str[strlen($str)-1] != SEP_URL)
		return $str . SEP_URL;
	else
		return $str;
}


//********************************
function izu_decode_argument($arg)
//********************************
// Removes shell-magic characters ( . / \ & ../ ) from album or image arguments
// Decode arguments received from the URL line
{
	if ($arg)
	{
		// remove double-seps
		$arg = str_replace(SEP . SEP, SEP, $arg);
		$arg = str_replace("\\'", "'", $arg);

		// convert SEP2 into SEP (dos->unix path)
		if (SEP2 != "")
			$arg = str_replace(SEP2, SEP, $arg);

		// remove any "../" present in the filename
		$arg = str_replace("../", "", $arg);

		// remove these stange characters if present at the beginning of the string
		$n = strspn($arg, "./\\&^%!\$");
		if ($n)
			return substr($arg, $n);
	}

	return $arg;
}


//********************************
function izu_encode_argument($arg)
//********************************
// Encode arguments that are used in the URL line
{
	// remove double-seps
	$arg = str_replace("/" . "/", "/", $arg);
	$arg = str_replace("\\'", "'", $arg);

	// convert SEP2 into SEP (dos->unix path)
	if (SEP2 != "")
		$arg = str_replace(SEP2, SEP, $arg);

	// remove these strange characters if present at the beginning of the string
	$n = strspn($arg, "./\\&^%!\$");
	if ($n)
		$arg = substr($arg, $n);

	// Now protect characters that have a meaning in HTTP URLs.
	// cf Section 3.2 of RFC 2068 HTTP 1.1
	// reserved = ";/?:@&=+";
	// extra    = "!*'(),";
	// unsafe   = " \"#%<>";
	// safe     = "$-_.";

	$match = ";/?:@&=+!*'(), \"#%<>";

	$n = strlen($arg);
	$res = "";
	for($i=0; $i<$n; $i++)
	{
		$c = substr($arg, $i, 1);
		if (strrchr($match, $c))
			$res .= sprintf("%%%02x", ord($c));
		else
			$res .= $c;
	}
	return $res;
}


//********************************
function izu_encode_url_link($arg)
//********************************
// Encode IMG SRC and HREF links
{
	// Now protect characters that have a meaning in HTTP URLs.
	// cf Section 3.2 of RFC 2068 HTTP 1.1
	// reserved = ";/?:@&=+";
	// extra    = "!*'(),";
	// unsafe   = " \"#%<>";
	// safe     = "$-_.";

	// RM 20020713 note: tried to encode c>=127 into &#dd;
	// but that breaks URLs and many other things.
	

	$match = ";?:@&=+!*'(), \"#%<>";

	$n = strlen($arg);
	$res = "";
	for($i=0; $i<$n; $i++)
	{
		$c = substr($arg, $i, 1);
		if (strrchr($match, $c))
			$res .= sprintf("%%%02x", ord($c));
		else
			$res .= $c;
	}
	return $res;
}



//*******************************
function izu_shell_filename($str)
//*******************************
// Encode a filename before using it in a shell argument call
// The thumbnail app will un-backslash the full argument filename before using it
{
	// RM 102201 -- escapeshellarg is "almost" a good candidate for linux
	// but for windows we need escapeshellcmd because a path may contain backslashes too

	return "\"" . escapeshellcmd($str) . "\"";
}


//********************************
function izu_shell_filename2($str)
//********************************
// Encode a filename before using it in a shell argument call
// This one is more dedicated for directly unix calls.
// Escapeshellcmd will transform ' into \' which is not always appropriate.
{
	$s = "\"" . escapeshellcmd($str) . "\"";
	$s = str_replace("\\'", "'", $s);
	return $s;
}


//***********************************
function izu_simplify_filename($name)
//***********************************
{
	$name = trim($name);
	// replace weird characters by underscores
	$name = strtr($name, " \'\"\\/&" , "______");
	return $name;
}


//*****************************
function izu_pretty_date($date)
//*****************************
{
	// Simple reformating of date

	// YYYY-MM-DD:HH:MM:SS or YYYY/MM/DD:HH:MM:SS or YYYMMDDHHMMSS
	if (preg_match("@([0-9]{4})[-/]?([0-9]{2})[-/]?([0-9]{2})[- /:]?([0-9]{2})[-:/h]?([0-9]{2})[-:/m]?([0-9]{2})@", $date, $matches) == 1)
		$date = sprintf("%s/%s/%s %sh%sm%ss", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]);
	// YYYY-MM-DD:HH:MM or YYYY/MM/DD:HH:MM or YYYMMDDHHMM
	else if (preg_match("@([0-9]{4})[-/]?([0-9]{2})[-/]?([0-9]{2})[- /:]?([0-9]{2})[-:/h]?([0-9]{2})@", $date, $matches) == 1)
		$date = sprintf("%s/%s/%s %sh%sm", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5]);
	// YYYY-MM-DD or YYYY/MM/DD or YYYMMDD
	else if (preg_match("@([0-9]{4})[-/]?([0-9]{2})[-/]?([0-9]{2})@", $date, $matches) == 1)
		$date = sprintf("%s/%s/%s", $matches[1], $matches[2], $matches[3]);

	return $date;
}


//*****************************
function izu_pretty_name($name)
//*****************************
{
	// TBDL -- RM 20030808

	// replace underscores by spaces
	$name = str_replace('_', ' ', $name);

	// remove extension if any
	$n = strlen($name);
	if ($n > 4 && substr($name, -4) == ".txt")
		$name = substr($name, 0, -4);

	// If there's a directory separator, keep only the terminating name for display
	if (preg_match("@/([A-Za-z]+)$@", $name, $matches) == 1)
		$name = $matches[1];

	// If name is a pure Wiki name, insert space before caps
	if (preg_match("/([A-Z][a-z]+)([A-Z][a-z]+)([A-Za-z]*)/", $name, $matches) == 1)
		$name = $matches[1] . " " . $matches[2] . preg_replace("/([A-Z])/", " \\1", $matches[3]);

	return $name;
}


//*************************************
function izu_mkdir($base, $path, $mode)
//*************************************
// RM 20030124
// This function creates a full path, recursively if needed.
//
// This function adds a bit of checking on the base directory.
// One has to exist since RIG never changes base directories.
{
	// first, explode the path
	$dirs = explode(SEP, $path);

	$n = count($dirs);

	// if there are no directories to create, do nothing
	if ($n < 1 || ($n == 1 && $dirs[0] == ""))
		return TRUE;

	// the very first part must always exists and be a directory
	// RIG does not create root directories, by security
	$full = $base;
	if (!izu_is_dir($base))
    {
		return izu_html_error( "Create Directory",
		                       "Non-existant base directory<br>\n" .
    		                   "RIG does not create base directories, by security.\n",
            		           $base,
                		       $php_errormsg);
    }

	// check for or create all the intermediary paths
	foreach($dirs as $dir)
	{
		// reject ".." directories, ignore "." and empty directories
		if ($dir == "..")
		{
			return izu_html_error( "Create Directory",
			                       "Invalid \"..\" directory name in path<br>\n",
	            		           $path,
	                		       $php_errormsg);
	    }

		if ($dir != "." && $dir != "")
		{
			// get the full path up to the current component
			$full = $full . izu_prep_sep($dir);
		
			// create if it does not exists
			if (!izu_is_dir($full))
				if (!mkdir($full, $mode))
				{
					return izu_html_error( "Directory Creation Failed",
					                       "Directory mode is $mode\n",
			            		           $full,
			                		       $php_errormsg);
				}
		}
	}

	return TRUE;
} 

//*************************************
function izu_create_preview_dir($album)
//*************************************
{
	global $pref_mkdir_mask;
	global $abs_preview_path;

	if (!izu_mkdir($abs_preview_path, $album, $pref_mkdir_mask))
	{
		return izu_html_error("Create Preview Directory",
					   "Failed to create directory",
					   $album,
					   $php_errormsg);
	}

	return TRUE;
}


//************************************
function izu_create_option_dir($album)
//************************************
{
	global $pref_mkdir_mask;
	global $abs_option_path;

	if (!izu_mkdir($abs_option_path, $album, $pref_mkdir_mask))
    {
        global $dir_abs_content, $dir_option;
		return izu_html_error( "Create Options Directory",
		                       "Failed to create directory<br>\n" .
    		                   "<b>Dir Abs Album:</b> $dir_abs_content<br>\n" . 
        		               "<b>Dir Option:</b> $dir_option\n",
            		           $album,
                		       $php_errormsg);
    }

	return TRUE;
}


// defines for in_page in izu_self_url -- RM 20030308
define("IZU_SELF_URL_NORMAL",		0);	// album+image user view
define("IZU_SELF_URL_ADMIN",		1);	// album+image admin view
define("IZU_SELF_URL_UPLOAD",		2);	// upload *admin* view
define("IZU_SELF_URL_TRANSLATE",	3);	// translate *admin* view

//***********************************
function izu_self_url($in_path = -1,
					  $in_album = -1,
					  $in_page = -1,
					  $in_extra = "")
//***********************************
// encode album/image name as url links
// in_path: -1 (use current if any) or text for image=...
// in_album: -1 (use current if any) or text for album=...
// in_page : -1 (use current if any) or RIG_SELF_URL_xxx (see above)
// in_extra: extra parameters (in the form name=val&name=val etc)
//
// Use URL-Rewriting when defined in prefs [RM 20030107]
{
	global $current_id;
	global $current_album;
	global $current_path;
	global $pref_url_rewrite;	// RM 20030107

	global $_debug_;

	$admin		= izu_get($_GET,'admin'		);
	$translate	= izu_get($_GET,'translate'	);
	$upload		= izu_get($_GET,'upload'		);

	// RM 20040108
	$izu_page = izu_get($_SERVER,'PATH_INFO');
	if (!is_string($izu_page) || $izu_page == "")
		$izu_page = izu_get($_GET,'page');

	$credits	= izu_get($_GET,'credits'	);
	$phpinfo	= izu_get($_GET,'phpinfo'	);
	$_debug_	= izu_get($_GET,'_debug_'	);


	// DEBUG
	// echo "<p>izu_self_url: in_page=$in_page\n";


	$use_rewrite = (is_array($pref_url_rewrite) && count($pref_url_rewrite) >= 2);

	if ($use_rewrite)
		$url = $pref_url_rewrite['index'];
	else
		$url = izu_get($_SERVER, 'SCRIPT_NAME');



	$params = "";
	$param_concat_char = "?";

//var_dump($in_path);
//var_dump($izu_page);

	// set default, if requested
	if (is_int($in_path) && $in_path == -1)
		$in_path = izu_encode_url_link($izu_page);
	else if ($izu_page) // RM 20031123
		$in_path = izu_encode_url_link($in_path);

//var_dump($in_path);

	// remove leading / if any in the path
	if (is_string($in_path) && $in_path[0] == '/')
		$in_path = substr($in_path, 1);
	

	// check in_page param
	if (is_int($in_page) && $in_page == -1)
	{
		// translate and upload imply admin so they must be tested first
		if ($translate)		$in_page = IZU_SELF_URL_TRANSLATE;
		else if ($upload)	$in_page = IZU_SELF_URL_UPLOAD;
		else if ($admin)	$in_page = IZU_SELF_URL_ADMIN;
		else				$in_page = IZU_SELF_URL_NORMAL;
	}

	// switch on in_page values
	switch($in_page)
	{
		case IZU_SELF_URL_ADMIN:
			izu_url_add_param($params, 'admin', 'on');
			break;

		case IZU_SELF_URL_TRANSLATE:
			izu_url_add_param($params, 'admin', 'on');
			izu_url_add_param($params, 'translate', 'on');
			break;

		case IZU_SELF_URL_UPLOAD:
			izu_url_add_param($params, 'admin', 'on');
			izu_url_add_param($params, 'upload', 'on');
			break;
	}


	if ($in_path)
	{
		if ($use_rewrite)
		{
			$url = $pref_url_rewrite['page'];
			$param_concat_char = "?";
		}
		else
		{
			// RM 20040104 by default pass the page name as PATH_INFO rather
			// than as a QUERY_STRING
			// old:
			// izu_url_add_param($params, 'page', $in_path);
			// new:
			
			$url .= "/" . $in_path;
		}
	}

	if ($_debug_)
		izu_url_add_param($params, '_debug_', '1');

	if ($credits == 'on')
		izu_url_add_param($params, 'credits', $credits);

	if ($phpinfo == 'on')
		izu_url_add_param($params, 'phpinfo', $phpinfo);


	// the extra must always be the last one
	if ($in_extra)
	{
		// don't add the & if the extra is <a name=> jump label (#label)
		if ($params && $in_extra[0] != '#')
			$params .= "&";

		$params .= "$in_extra";
	}


	// [RM 20030107]
	if ($use_rewrite)
	{
		// Replace %P by path
		$url = str_replace('%P', $in_path, $url);
	}

	if ($params)
		if ($params[0] == '#')
			return $url . $params;
		else
			return $url . $param_concat_char . $params;
	else
		return $url;
}


//***********************************************************
function izu_url_add_param(&$inout_url, $in_param, $in_value)
//***********************************************************
// RM 20030308 utility function to add one parameter in izu_self_url()
{
	// param can't be empty
	if (!is_string($in_param) || $in_param == '')
		return;

	// param must end with a '='
	if ($in_param[strlen($in_param)-1] != '=')
		$in_param .= '=';
	
	// check param is not already in the url
	if (!strstr($inout_url, $in_param))
	{
		// append to url
		if ($inout_url)
			$inout_url .= '&';

		// add param=value to the url
		$inout_url .= $in_param . $in_value;
	}
}


//-----------------------------------------------------------------------


//*****************************
function izu_read_prefs_paths()
//*****************************
{
	global $dir_abs_content;

	// append a separator to the abs album dir if not already done
	$dir_abs_content = izu_post_sep($dir_abs_content);

	// make some paths absolute
	// RM 20021021 check these absolute paths

	// --- album directory ---

	global $dir_album, $abs_album_path;
	$abs_album_path   = realpath($dir_abs_content . $dir_album);

	if (!is_string($abs_album_path))
	{
		izu_html_error("Missing Album Directory",
					   "Can't get absolute path for the album directory. <p>" .
					   "<b>Base directory:</b> $dir_abs_content<br>" .
					   "<b>Target directory:</b> $dir_album<br>" ,
					   $dir_abs_content . $dir_album);
	}

	// --- previews directory ---

	global $dir_preview, $abs_preview_path;
	$abs_preview_path = realpath($dir_abs_content . $dir_preview);

	if (!is_string($abs_preview_path))
	{
		izu_html_error("Missing Previews Directory",
					   "Can't get absolute path for the previews directory. <p>" .
					   "<b>Base directory:</b> $dir_abs_content<br>" .
					   "<b>Target directory:</b> $dir_preview<br>" ,
					   $dir_abs_content . $dir_preview);
	}

	// --- options directory ---

	global $dir_option, $abs_option_path;
	$abs_option_path = realpath($dir_abs_content . $dir_option);

	if (!is_string($abs_option_path))
	{
		izu_html_error("Missing Options Directory",
					   "Can't get absolute path for the options directory. <p>" .
					   "<b>Base directory:</b> $dir_abs_content<br>" .
					   "<b>Target directory:</b> $dir_option<br>" ,
					   $dir_abs_content . $dir_option);
	}

	// --- upload_src directory ---

	global $dir_upload_src, $abs_upload_src_path;
	$abs_upload_src_path = realpath($dir_abs_content . $dir_upload_src);

	if (!is_string($abs_upload_src_path))
	{
		izu_html_error("Missing Upload Sources Directory",
					   "Can't get absolute path for the upload_src directory. <p>" .
					   "<b>Base directory:</b> $dir_abs_content<br>" .
					   "<b>Target directory:</b> $dir_upload_src<br>" ,
					   $dir_abs_content . $dir_upload_src);
	}

	// --- upload_album directory ---

	global $dir_upload_album, $abs_upload_album_path;
	$abs_upload_album_path = realpath($dir_abs_content . $dir_upload_album);

	if (!is_string($abs_upload_album_path))
	{
		izu_html_error("Missing Upload Albums Directory",
					   "Can't get absolute path for the upload_album directory. <p>" .
					   "<b>Base directory:</b> $dir_abs_content<br>" .
					   "<b>Target directory:</b> $dir_upload_album<br>" ,
					   $dir_abs_content . $dir_upload_album);
	}

}


//********************************
function izu_clear_album_options()
//********************************
// Currently clears:
//	list_hide				- array of filename
//	list_album_icon			- array of icon info { a:album(relative) , f:file, s:size }
//	list_description		- array of [filename] => description (text and/or html) -- RM 20030713
{
	global $list_hide;
	global $list_album_icon;
	global $list_description;

	unset($list_hide);
	unset($list_album_icon);
	unset($list_description);
}


//*************************************
function izu_read_album_options($album)
//*************************************
{
	// first clear current options
	izu_clear_album_options();

	// then grab new ones
	global $abs_preview_path;	// old location for options was with previews
	global $abs_option_path;	// new options have their own base directory (may be shared with previews anyway)

	// make sure the directory exists
	// don't output an error message, the create function does it for us
	if (!izu_create_option_dir($album))
		return FALSE;

	// RM 20030121 moving options to option's dir -- amazing design isn't it?
	// first try to get options at the new location
	$abs_options = $abs_option_path . izu_prep_sep($album) . izu_prep_sep(ALBUM_OPTIONS_TXT);

	if (!izu_is_file($abs_options))
	{
		// if that fails, try the old location
		$abs_options = $abs_preview_path . izu_prep_sep($album) . izu_prep_sep(ALBUM_OPTIONS_TXT);
	
		// silently abort if the file does not exist
		if (!izu_is_file($abs_options))
			return FALSE;
	}

	// DEBUG
	// global $_debug_;
	// if ($_debug_)  echo "<p>Reading abs_options '$abs_options'<br>";

	$file = @fopen($abs_options, "rt");

	if (!$file)
		return izu_html_error("Read Album Options", "Failed to read from file", $abs_options, $php_errormsg);

	$var_name = "";
	$local = array();

	while(!feof($file))
	{
		$line = fgets($file, 1023);

		if (!is_string($line) || $line == FALSE || $line[0] == '#')
			continue;
		
		if (substr($line, -1) == "\n")
			$line = substr($line, 0, -1);
		if (substr($line, -1) == "\r")
			$line = substr($line, 0, -1);

		if ($line[0] == ':')
		{
			if ($var_name)
			{
				global $$var_name;
				$$var_name = array_merge($$var_name, $local);
				$local = array();
			}

			$var_name = substr($line, 1);
			global $$var_name;
			$$var_name = array();
		}
		else if ($line)
		{
			$key = -1;
			$c = substr($line, 0, 1);
			// DEBUG
			// if ($_debug_) echo "<br>Read line; '$line'";
			if ($c == '[')
			{
				// DEBUG
				// if ($_debug_) echo "<br>----- format is [key]value";

				// format is "[key]value"
				if (ereg("^\[(.*)\](.*)", $line, $reg) && is_string($reg[1]))
				{
					$key   = $reg[1];
					// the reg-exp will return false if nothing can be matched for the second part
					if ($reg[2] === FALSE)
						$value = "";
					else
						$value = $reg[2];
				}
			}
			else if ($c == '_')
			{
				// DEBUG
				// if ($_debug_) echo "<br>----- format is _value";
		
				// format is "_value"
				$line = substr($line, 1);		// RM 20030215 bug fix (..., 1, -1) => (..., 1);
			}

			// DEBUG
			// if ($_debug_) echo "<br>----- key = '$key'";
			// if ($_debug_) echo "<br>----- value = '$value'";
			// if ($_debug_) echo "<br>----- line = '$line'";

			if ($key == -1)
				$local[] = $line;
			else
				$local[$key] = $value;

			// DEBUG
			// if ($_debug_) { echo "<p>local: "; var_dump($local); }
		}
	}

	// DEBUG
	// if ($_debug_) global $list_hide;
	// if ($_debug_) global $list_album_icon;
	// if ($_debug_) { echo "<p>Reading list_hide: "; var_dump($list_hide);}

	fclose($file);		// RM 20020713 fix
	return TRUE;
}


//*********************************
function izu_get_album_date($album)
//*********************************
// RM 20030719 v0.3.6.5 using strftime
{
	global $abs_album_path;
	global $html_content_date;	// RM 20030719

	$abs_dir = $abs_album_path . izu_prep_sep($album);

	// read the timestamp on the file "." in the directory (aka the directory itself)
	$tm = filemtime(izu_post_sep($abs_dir) . ".");
	return strftime($html_content_date, $tm);	
}


//******************************************
function izu_read_album_descriptions($album)
//******************************************
// Reloads the content of $list_description
//	list_description		- array of [filename] => description (text and/or html) -- RM 20030713
{
	global $abs_album_path;		// descriptions
	global $abs_option_path;	// new options have their own base directory (may be shared with previews anyway)


	// first clear current options
	global $list_description;
	unset($list_description);

	// then grab new ones
	//
	// descriptions are stored either with the album itself or in the options directory
	// the options dir's version superseedes the one from the album, if any


	// first read the main dir files

	$abs_dir = $abs_album_path . izu_prep_sep($album);


	if (!izu_parse_description_file($abs_dir . izu_prep_sep(DESCRIPTION_TXT)))
		 izu_parse_description_file($abs_dir . izu_prep_sep(FILEINFODIZ_TXT));


	// then override with the options directory


	// make sure the directory exists
	// don't output an error message, the create function does it for us
	if (!izu_create_option_dir($album))
		return FALSE;


	$abs_options = $abs_option_path . izu_prep_sep($album);

	if (!izu_parse_description_file($abs_options . izu_prep_sep(DESCRIPTION_TXT)))
		 izu_parse_description_file($abs_options . izu_prep_sep(FILEINFODIZ_TXT));

	return TRUE;
}



//********************************************
function izu_parse_description_file($abs_path)
//********************************************
// This reads in the description file for an album list or image list.
// This function merges the file into the existing array list_description with format:
//	list_description		- array of [filename] => description (text and/or html) -- RM 20030713
// Format:
/*
	# Description file for rig
	# Accepted names are "descript.ion" or "file_info.diz"
	# Lines starting with # are ignored. So are empty lines.
	# Line format is:
	#   <img or album name>[ \t]+<description>\n
	#   [ \t]+<continuation of previous description>\n
*/
{
	global $list_description;

	$file = @fopen($abs_path, "rt");

	if (!$file)
		return FALSE;

	$continuing = FALSE;
	$name = "";

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
				if ($line == "")
					$is_comment = ($temp[0] == '#');
	
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
		}

		// need a valid line
		if (!$line || $line == "" || $line == FALSE || $is_comment)
			continue;

		// if starts by a whitespace, it's the continuation of the previous line
		$nb_ws = strspn($line, " \t");
		if ($nb_ws > 0 && $name != "")
		{
			// skip whitespace
			$line = substr($line, $nb_ws);

			// note that if the previous line nor the new one end or start with a
			// whitespace, one must be added.
			$t = $list_description[$name];
			if ($t != "")
			{
				$t = $t[strlen($t)-1];
				if ($t != " " && $t != "\t")
					$line = " " . $line;
			}

			$list_description[$name] .= $line;
		}
		else
		{
			// this is a new entry, get the name and the text
			// format is "(name)[ \t]+(text)"
			if (ereg("^([^ \t]+)[ \t]+(.*)", $line, $reg) && is_string($reg[1]))
			{
				$name = $reg[1];
				$list_description[$name] = $reg[2];
			}
		}

	} // end while feof

	fclose($file);

	// DEBUG
	// var_dump($album);
	// var_dump($list_description);
	

	return TRUE;
}


//*******************************************************
function izu_write_album_options($album, $silent = FALSE)
//*******************************************************
// Currently writes:
//	list_hide				- array of filename
//	list_album_icon			- array of icon info { a:album(relative) , f:file, s:size }
// RM 20030121 moving options to option's dir -- amazin design isn't it?
// RM 20030121 always writing header for the array name even if the array is empty or missing
// RM 20030121 not ready to move to XML yet (DomXml is only in PHP 4.2.1+ experimental yet)
{
	global $list_hide;
	global $list_album_icon;
	global $izu_version;
	global $abs_option_path;

	// DEBUG
	// echo "<p> izu_write_album_options( $album, $silent )\n";
	// echo "<br>list_album_icon = \n"; var_dump($list_album_icon);
	// echo "<p> abs_options = $abs_options\n";

	// make sure the directory exists
	// don't output an error message, the create function does it for us
	if (!izu_create_option_dir($album))
		return FALSE;

	$abs_options = $abs_option_path . izu_prep_sep($album) . izu_prep_sep(ALBUM_OPTIONS_TXT);

	// make sure the directory exists

	$file = fopen($abs_options, "wt");

	if (!$file)
	{
		return izu_html_error("Write Album Options",
							  "Failed to write to file",
							  $abs_options,
							  $php_errormsg);
	}

	if (!$silent)
		echo "<p>Write album <b>'$album'</b> options - file: <b>$file</b>\n";

	// ------

	fputs($file, "# Album options - RIG $izu_version\n");
	fputs($file, "# Format: :var_name/val/val.../: to end\n");
	fputs($file, "# Values: one entry per line, either _String\\n or [Key]String\\n\n");

	// ------

	// DEBUG
	// echo "<p> list_hide = \n"; var_dump($list_hide);

	fputs($file, ":list_hide\n");
	if (is_array($list_hide))
	{
		if (!$silent)
			echo "<br>Write album options - list_hide: " . count($list_hide) . " items\n";

		foreach($list_hide as $str)
			fputs($file, '_' . $str . "\n");
	}

	// ------

	// DEBUG
	//echo "<p> list_album_icon = \n"; var_dump($list_album_icon);

	fputs($file, ":list_album_icon\n");
	if (is_array($list_album_icon))
	{
		if (!$silent)
			echo "<br>Write album options - list_album_icon: " . count($list_album_icon) . " items\n";

		foreach($list_album_icon as $key => $str)
			fputs($file, '[' . $key . ']' . $str . "\n");
	}

	fputs($file, ":\n");
	fclose($file);

	return TRUE;
}


//-----------------------------------------------------------------------

//****************************
function izu_nocache_headers()
//****************************
// used by the admin pages to prevent caching
// RM see HTTP doc to determine if html vs. img can be cached selectively (IMG tag?)
{
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");				// Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");	// always modified
	header("Cache-Control: no-cache, must-revalidate");				// HTTP/1.1
	header("Pragma: no-cache");										// HTTP/1.0
}


//***************************************************
function izu_set_cookie_val($name, $val, $set = TRUE)
//***************************************************
// $set: TRUE to set cookie, FALSE to delete cookie
{
	global $dir_abs_content;
    global $pref_cookie_host;

	$delay = 3600 * 24 * 365;

    $host = $pref_cookie_host;
	if (!$host)
		$host = izu_get($_SERVER, 'HTTP_HOST');

	$path = izu_get($_SERVER, 'SCRIPT_NAME');
    if (!$path)
    	$path = $dir_abs_content;

	$time = ($set ? time() + $delay : time() - $delay);
	// $time = gmstrftime("%A, %d-%b-%Y %H:%M:%S", ($set ? time() + $delay : time() - $delay));

	// RM 20041126 should not removed them
	// $host = "";
	// $path = "";

	// debug
    // if (izu_get($_GET,'admin') && izu_get($_GET,'_debug_'))
	// echo "Set Cookie: name='$name' -- val='$val' -- date='$time' -- path='$path' -- host='$host'<br>\n";

	setcookie($name, $val, $time, $path, $host);

	// Update the global cookie array
	if ($set)
		$_COOKIE[$name] = $val;
	else
		$_COOKIE[$name] = NULL;		
}


//***************************
function izu_handle_cookies()
//***************************
// Some literature:
// http://developer.netscape.com:80/docs/manuals/js/client/jsguide/cookies.htm
{
	global $current_language;
	global $current_theme;

	// Description of variables:
	//
	//	GET/POSTDATA name	COOKIE name
	// lang					izu_lang
	// theme				izu_theme

	global $lang,		$izu_lang;
	global $theme,		$izu_theme;

	$lang			= izu_get($_GET,'lang'			);
	$theme			= izu_get($_GET,'theme'			);
	$keep			= izu_get($_GET,'keep'			);

	$izu_lang		= izu_get($_COOKIE,'izu_lang'	);
	$izu_theme		= izu_get($_COOKIE,'izu_theme'	);


	if ($lang)
	{
		izu_set_cookie_val("izu_lang", $lang);
		$current_language = $lang;
		$izu_lang = $lang;
	}
	else
	{
		$current_language = $izu_lang;
	}

	if ($theme)
	{
		izu_set_cookie_val("izu_theme", $theme);
		$current_theme = $theme;
		$izu_theme = $theme;
	}
	else
	{
		$current_theme = $izu_theme;
	}

}



//******************
function izu_setup()
//******************
{
	// List of globals defined for the album page by prepare_album():
	// $current_album		- string
	// $display_exec_date	- string
	// $display_softname	- string, constant

	global $current_language;
	global $display_exec_date;
	global $display_softname;
	global $html_footer_date;
	global $html_desc_lang;
	global $lang_locale;
	global $pref_umask;

	// -- setup umask

	if (isset($pref_umask))
		umask($pref_umask);


	// -- setup locale

	if (isset($lang_locale))
	{
		$l = FALSE;
		if (is_string($lang_locale))
		{
			$l = setlocale(LC_TIME, $lang_locale);
		}
		else if (is_array($lang_locale))
		{
			// setlocale does not accept array before php 4.3... simulate
			foreach($lang_locale as $name)
			{
				$l = setlocale(LC_TIME, $name);
				if (is_string($l) && $l != '')
					break;
			}
		}

		if ($l == FALSE)
		{
			izu_html_error("Invalid Locale!",
			               "The specified locale is not recognized by your system!",
	            		   is_string($lang_locale) ? $lang_locale : implode(', ', $lang_locale) );
		}
	}


	// -- setup date & soft name
	$display_exec_date = strftime($html_footer_date);	// RM 20030719 using strftime
	$display_softname  = SOFT_NAME;


	// -- keep track of php errors with $php_errormsg (cf html_error)
	ini_set("track_errors", "1");

}


//-----------------------------------------------------------------------



//*******************************************************
function izu_is_visible($path = -1, $dir = -1, $page = -1)
//*******************************************************
{
	return TRUE;
}


//**************************
function izu_valid_ext($path)
//**************************
{
	if (is_string($path))
	{
		$n = strlen($path);
		if ($n > 4)
		{
			$ext = substr($path, -4);
			
			return ($ext == EXT_SOURCE);
		}
	}

	return FALSE;
}

//-----------------------------------------------------------------------

//**************************************
function izu_parse_string_data($filename)
//**************************************
// Parses a data file for foreign strings
//
// Format of the file:
// - entries are composed of 2 lines:
//	1- the variable to set (with the $ like in PHP)
//	2- the string value for the variable
// The scanner looks for lines starting with $ or @ and use the first word as the variable name
// If the line was starting with @$, the second word will be the named index in an array
// It then reads the *next* line, whatever it's content being, except the ending linefeed
//
// As a side effect, empty lines or lines starting with // or # will be ignored.
//
// The prefered line separator is the Unix mode, i.e. only LF (/n) as linefeed.
{
	global $dir_install, $dir_src, $abs_upload_src_path;


	// get the installation-relative path of the file
	$file1 = rig_post_sep($dir_install . $dir_src) . $filename;
	$file2 = rig_post_sep($abs_upload_src_path)    . $filename;

	if (rig_is_file($file2))
		$filename = $file2;
	else
		$filename = $file1;

	// open the file
	$file = @fopen($filename, "rt");

	// make sure the file exist or display an error to the user	
	if (!$file)
	{
		rig_html_error("Can't read i18n string file",
					   "Failed to read from file",
					   $filename,
					   $php_errormsg);

		// just for the sake of it, present the error again :-p
		return rig_check_src_file($filename);
	}

	$tok_sep = " \t\n\r";

	// for every line...
	while(!feof($file))
	{
		$line = fgets($file, 1023);

		// if the line is empty, we skip it
		if (!is_string($line) || !$line || $line == FALSE)
			continue;

		// if the line does not start with @$ or $, we skip it
		if ($line[0] != '@' && $line[0] != '$')
			continue;

		$is_array = ($line[0] == '@');
		if ($is_array && $line[1] != '$')
			continue;

		// get the variable name
		$var_name = strtok(substr($line, $is_array ? 2 : 1), $tok_sep);

		if (is_string($var_name))
		{
			// acces the global variable
			global $$var_name;

			// if an array, get the array index name
			if ($is_array)
			{
				$index_name = strtok($tok_sep);
				
				if (!is_string($index_name))
					continue;
			}

			// read the actual data line
			$data = fgets($file, 1023);

			if (is_string($data))
			{
				// strip end-of-line
				if (substr($data, -1) == "\n")
					$data = substr($data, 0, -1);
				if (substr($data, -1) == "\r")
					$data = substr($data, 0, -1);

				// DEBUG
				// echo "<br> [$var_name] + [$index_name] = $data";

				// set the value
				if ($is_array)
					$$var_name[$index_name] = $data;
				else
					$$var_name = $data;
			}
		} // if var_name
	} // while !eof

	fclose($file);		// RM 20020713 fix
	return true;
}


//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.9  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.8  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.7  2004/12/04 22:22:02  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.6  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.5  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
//	
//	Revision 1.4  2004/11/21 18:17:12  ralf
//	Blog support added. Experimental.
//	
//	Revision 1.3  2004/05/09 19:06:10  ralf
//	Use url rewrite. Allow for not having index.php in the URL.
//	Added izu:image, #links, fixed some regexps. Log http-language-accept.
//	Added site license generated on every page.
//	Added izumi favicon. Fixed dba_open n vs c, using wait lock.
//	Support HTTP 304 Not Modified, ETag, Last-Modified headers and If-counterparts.
//	
//	Revision 1.2  2004/01/06 09:08:01  ralf
//	Removed obsolete abs_image_path
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//-------------------------------------------------------------

// IMPORTANT: the "? >" must be the LAST LINE of this file, otherwise
// some HTTP output will be started by PHP4 and setting headers or cookies
// will fail with a PHP error message.
?>
