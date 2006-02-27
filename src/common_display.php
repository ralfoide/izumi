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


//**********************************************
function izu_display_header($title, $extra = '')
//**********************************************
{
	global $html_encoding;
	global $html_language_code;
	global $theme_css_head;
	global $pref_html_meta;
	global $pref_html_meta_for_query_string;

	// Online reference:

	// For the DocType, consult the W3C HTML 4.0:
	// http://www.w3.org/TR/REC-html40/struct/global.html#h-7.2

	// For the Meta Content-Type, consult the W3C HTML 4.0
	// http://www.w3.org/TR/REC-html40/charset.html#h-5.2.2
	//
	// This list of charset is available here:
	// http://www.iana.org/assignments/character-sets

	// For the Robots Meta Tag, consult robotstxt.org:
	// http://www.robotstxt.org/wc/exclusion.html#meta

	// The HTML language code is described by the W3C HTML 4.0 here:
	// http://www.w3.org/TR/REC-html40/struct/dirlang.html#h-8.1
	// http://www.w3.org/TR/REC-html40/struct/dirlang.html#langcodes

	// Setup the language information for the HTML tag -- RM 20021023
	if ($html_language_code)
		$lang = "lang=\"$html_language_code\"";
	else
		$lang = "";

	// Provide the web server with an HTTP Header describing the right charset -- RM 20021023
	// This is the one step that will make the browser switch to the right encoding...
	// Explanation: Apache will send a default content-type if the CGI does not provide one.
	//   This default content-type is configured in the http.conf and generally defaults to
	//   ISO-8859-1 or similar. If you want to generate UTF-8, you need to provide the
	//   header content-type yourself. Using an <html><head><meta> is not enough.
	if ($html_encoding)
		header("Content-Type: text/html; charset=$html_encoding");

	// prepare the meta tags line
	$meta = "";


	$admin = izu_get($_GET,'admin');

	if (!$admin)
	{
		/*  RM 20040217
		 *	The idea is to provide a html-meta that allows indexing for normal Izumi
		 *	pages (when using PATH_INFO to get $page) that have no query arguments;
		 * 	and then to use a non-indexing meta for pages that that query arguments
		 *	(so that search engines do not cache "alternate" views of the main page).
		 */

		if (   is_string($pref_html_meta_for_query_string)
			&&           $pref_html_meta_for_query_string != ''
			&& izu_get($_SERVER, 'QUERY_STRING', '') != '')
		{
			$meta = $pref_html_meta_for_query_string;
		}
		else if ($pref_html_meta)
		{
			$meta = $pref_html_meta;
		}
	}
			



	// RM 20040104 -- location of browser_detect.js
	// If the URI is using a PATH_INFO, the location must be made specifically to the
	// local site (i.e. if the script is in /dir1/dir2/index.php, then we should explicitly
	// ask for the .js in /dir1/dir2). Rationale: failure to do so means the server will
	// look for the .js in the absolute path matching the path info, which does not exists
	// (i.e. /dir1/dir2/index.php/somedir/file.js instead of /dir1/dir2/file.js)
	//
	// RM 2004022 -- Hack: if SCRIPT_NAME does not end with "/index.php", then it is assumed
	// to be the root path we need.

	$root_path = '';
	$script_name = izu_get($_SERVER, 'SCRIPT_NAME');
	if (preg_match("@(.*/)index\\.php$@", $script_name, $matches) == 1)
		$root_path = $matches[1];
	else
		$root_path = izu_post_sep($script_name);

// The indentation below is made on purpose, to make sure there's nothing before doctype
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html <?= $lang ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $html_encoding ?>">
	<?= $meta ?> 
	<link rel="shortcut icon" href="<?= $root_path ?>izumi.ico">
	<?= $extra ?>
	<script language="JavaScript" type="text/javascript" src="<?= $root_path ?>browser_detect.js"></script>
 	<title><?= $title ?></title>
	<?= $theme_css_head ?>
</head>
<?php

}

//************************
function izu_display_body()
//************************
{
	global $color_body_bg;
	global $color_body_text;
	global $color_body_link;
	global $color_body_alink;
	global $color_body_vlink;

	?>
		<body bgcolor="<?= $color_body_bg    ?>"
			  text   ="<?= $color_body_text  ?>"
			  link   ="<?= $color_body_link	 ?>"
			  alink  ="<?= $color_body_alink ?>"
			  vlink  ="<?= $color_body_vlink ?>"
		>
	<?php
}



//-----------------------------------------------------------------------


//*******************************************
function izu_display_section($html_content,
							$color_bg   = "",
							$color_text = "")
//*******************************************
{
	global $color_section_bg;
	global $color_section_text;

	if ($color_bg == "")
		$color_bg = $color_section_bg;

	if ($color_text == "")
		$color_text = $color_section_text;
		

	?>
		<table width="100%" bgcolor="<?= $color_bg ?>"><tr><td>
			<font color="<?= $color_text ?>"><center>
				<?= $html_content ?>
			</center></font>
		</td></tr></table>
	<?php
}



//*********************************
function izu_display_site_license()
//*********************************
{
	global $pref_html_site_license;
	
	if (is_string($pref_html_site_license) && $pref_html_site_license != '')
	{
		izu_display_section("<b>Site License</b>");	// RM 20040215 TBT

		echo "<p>";
		echo $pref_html_site_license;
		echo "<p>\n";
	}
}


//***************************
function izu_display_search()
//***************************
{
	global $html_encoding;
	global $color_body_bg;
	global $color_section_bg;

	$server = izu_get($_SERVER, 'SERVER_NAME');
	$agent  = izu_get($_SERVER, 'HTTP_USER_AGENT');

	// Field size: 30 by default, 17 for PPC screen (240x320)
	$small_screen = (strpos($agent, "240x") > 0);

	$field = $small_screen ? 17 : 30;

	// Select which Google logo depending on background color
	if ($color_body_bg == '#000000')
		$logo = "http://www.google.com/logos/Logo_25blk.gif";
	else if ($color_body_bg == '#FFFFFF' || $color_body_bg == '')
		$logo = "http://www.google.com/logos/Logo_25wht.gif";
	else
		$logo = "http://www.google.com/logos/Logo_25gry.gif";

	$site = $small_screen ? "This Site" : $server;

	echo "\n<p><table border=\"0\"><tr><td>\n";

	// Google free web search: http://www.google.com/stickers.html
	// Google logos: http://www.google.com/stickers.html

	?>
		<!-- SiteSearch Google -->
		<form method=get action="http://www.google.com/search" style="padding: 0px; margin: 0px; border: none">
			<input type=hidden name=ie value=<?= $html_encoding ?> >
			<input type=hidden name=oe value=<?= $html_encoding ?> >
		
		<table border="0">
		<tr>
			<td colspan="2">
				<input type=text name=q size=<?= $field ?> maxlength=255 value="">
				<input type=submit name=btnG value="Search">
			</td>
		</tr>
		<tr>
			<td align="left">
				<font size=-1>
				<input type=hidden name=domains value="<?= $server ?>">
				<input type=radio name=sitesearch value=""> Web 
				<input type=radio name=sitesearch value="<?= $server ?>" checked> <?= $site ?> 
				</font>
			</td>
			<td align="right">
				<a href="http://www.google.com/"><img src="<?= $logo ?>" 
					border="0" alt="Powered by Google" name="Powered by Google" align="absmiddle"></a>
			</td>
		</tr>
		</table>
		</form>
		<!-- SiteSearch Google -->

	<?php

	echo "</td></tr>\n";
	echo "<tr><td height=\"2\" bgcolor=\"$color_section_bg\"></td></tr>";
	echo "</table><p>\n";
}


//******************************************
function izu_display_options($use_hr = TRUE)
//******************************************
{
	global $color_section_bg;
	global $html_options;

	izu_display_section("<b>$html_options</b>");

	echo "<table>";

	// -- RM 20031123 not for izumi: --	izu_display_language();
	izu_display_theme();

	if ($use_hr)
		echo "<tr><td colspan=\"2\"  height=\"2\" bgcolor=\"$color_section_bg\"></td></tr>";
	// or use a <hr>:
	//	echo "<tr><td colspan=\"2\"><hr></td></tr>";

	echo "</table>";

	if (!$use_hr)
		echo "<p>";
}


//*****************************
function izu_display_language()
//*****************************
{
	global $html_desc_lang;
	global $html_language;
	global $current_language;
	global $pref_disable_web_translate_interface;

	$sep = FALSE;

	echo "<tr><td align=\"right\"><a name=\"lang\">$html_language</td><td> \n";

	foreach($html_desc_lang as $key => $value)
	{
		if ($sep)
			echo "&nbsp;|&nbsp;";

		if ($current_language == $key)
		{
			if (isset($pref_disable_web_translate_interface) && $pref_disable_web_translate_interface)
			{
				echo $value;
			}
			else
			{
				// if in admin mode and not already in translate mode, display the edit language link
				// RM 20030308 TBT -- Translate "Edit"

				$admin     = isset($_GET['admin'    ]) ? $_GET['admin'    ] : null;
				$translate = isset($_GET['translate']) ? $_GET['translate'] : null;

				if ($admin && !$translate)
				{
					echo " [<a href=\"" . izu_self_url(-1, -1, TG_SELF_URL_TRANSLATE, "lang=$key#lang") . "\">Edit $value</a>] \n";
				}
				else if ($admin && $translate)
				{
					echo " [<a href=\"" . izu_self_url(-1, -1, TG_SELF_URL_TRANSLATE, "lang=$key#lang") . "\">Reload $value</a>] \n";
					echo " [<a href=\"" . izu_self_url(-1, -1, TG_SELF_URL_ADMIN, "lang=$key#lang") . "\">Exit Edit $value</a>] \n";
				}
				else
				{
					echo $value;
				}
			}
		}
		else
		{
			echo "<a href=\"" . izu_self_url(-1, -1, -1, "lang=$key#lang") . "\">$value</a>\n";
		}

		$sep = TRUE;
	}

	echo "</td></tr>";
}


//**************************
function izu_display_theme()
//**************************
{
	global $html_desc_theme;
	global $html_theme;
	global $current_theme;

	$sep = FALSE;

	echo "<tr><td align=\"right\"><a name=\"theme\">$html_theme</td><td> \n";

	foreach($html_desc_theme as $key => $value)
	{
		if ($sep)
			echo "&nbsp;|&nbsp;";

		if ($current_theme == $key)
			echo $value;
		else
			echo "<a href=\"" . izu_self_url(-1, -1, -1, "theme=$key#theme") . "\">$value</a>\n";

		$sep = TRUE;
	}

	echo "</td></tr>";
}


//-----------------------------------------------------------------------


//****************************************************************
function izu_display_credits($has_credits = -1, $has_phpinfo = -1)
//****************************************************************
// has_credits can be -1 (default, use query "credits") or "on" or "off" or "no-link"
// has_phpinfo can be -1 (default, use query "phpinfo") or "on" or "off" or "no-link"
{
	global $html_text_credits;
	global $html_hide_credits;
	global $html_show_credits;
	global $html_credits;

	global $html_show_phpinfo;
	global $html_hide_phpinfo;
	global $html_phpinfo;

	global $color_section_bg;

	$admin   = izu_get($_GET,'admin'  );
	$_debug_ = izu_get($_GET,'_debug_');

	if (!is_string($has_credits) && $has_credits == -1)
		$has_credits = izu_get($_GET,'credits', '');

	if (!is_string($has_phpinfo) && $has_phpinfo == -1)
		$has_phpinfo = izu_get($_GET,'phpinfo', '');

	if ($has_credits != "no-link")
	{
		// link to show or hide the credits
		$v = ($has_credits == "on" ? "off" : "on");
		$l = ($has_credits == "on" ? $html_hide_credits : $html_show_credits);
		echo "<a name=\"credits\" href=\"" . izu_self_url(-1, -1, -1, "credits=$v#credits") . "\" target=\"_top\">$l</a><br>";
	}

	if ($has_phpinfo != "no-link")
	{
		// link to show or hide the PHP Info
		// RM 20030118 this is only available in _debug_ or admin, no longuer in normal mode
		if ($_debug_ || $admin)
		{
			$v = ($has_phpinfo == "on" ? "off" : "on");
			$l = ($has_phpinfo == "on" ? $html_hide_phpinfo : $html_show_phpinfo);
			echo "<a name=\"phpinfo\" href=\"" . izu_self_url(-1, -1, -1, "phpinfo=$v#phpinfo") . "\" target=\"_top\">$l</a><br>";
		}
	}

	// actually display the credits if activated
	if ($has_credits == "on" || $has_credits == "no-link")
	{
		?>
			<p>
				<?php izu_display_section("<b>$html_credits<b>") ?>
			<p>
				<?php echo "$html_text_credits" ?>
			<p>
		<?php

		if ($has_credits != "no-link")
		{
			?>
			<a href="?php_credits=on">PHP Credits</a>
			<?php
		}
	}

	// actually display the PHP info if activated
	if ($has_phpinfo == "on")
	{
		?>
			<p>
				<?php izu_display_section("<b>$html_phpinfo<b>") ?>
			<p>
		<?php

		phpinfo(INFO_ALL);
	}

	echo "<p>";
}


//***************************
function izu_display_footer()
//***************************
{
    global $_debug_;
	global $izu_version;
	global $display_exec_date;
	global $display_softname;
	global $html_generated;
	global $html_seconds;
	global $html_the;
	global $html_by;
	global $color_section_bg;
	global $color_section_text;

	$sgen = str_replace("[time]", izu_time_elapsed(), $html_generated);
	$sgen = str_replace("[date]", $display_exec_date, $sgen);
	$sgen = str_replace("[rig-version]", $display_softname . " " . $izu_version, $sgen);

	?>
		<table width="100%" bgcolor="<?= $color_section_bg ?>"><tr><td>
			<center><font size="-1" color="<?= $color_section_text ?>">
				&lt;
				<?= $sgen ?>
				&gt;
			</font></center>
		</td></tr></table>
	<?php
}

//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.2  2006-02-27 03:45:47  ralfoide
//	Fixes
//
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.10  2004/12/20 07:01:37  ralf
//	New minor features. Version 0.9.4
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
//	Revision 1.6  2004/11/28 22:38:00  ralf
//	Version 0.9.1: RSS support with ETag/If-Modified.
//	
//	Revision 1.5  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.4  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
//	
//	Revision 1.3  2004/09/26 19:30:56  ralf
//	Minor formating tweak
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
