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

// --- system-dependent prefs ---

/***********************************************************
 *
 *	Section:	system-dependent prefs
 *
 *	For new installations: most likely, you will not need to
 *	change anything in this section.
 *
 ***********************************************************/


/***********************************************************
 *
 *	Setting: 		$pref_mkdir_mask
 *	Type:			Octal mask
 *	Default:		0777
 *	
 *	The mask used to create the various cache and option directories
 *	for rig. Consult "man mkdir" on a Unix box or Cygwin for more info.
 *	The default is 0777: "0" to make it an octal number. Then each 7
 *	indicates full access rights for user/group/others.
 *
 ***********************************************************/

$pref_mkdir_mask		= 0777;


/***********************************************************
 *
 *	Setting: 		$pref_umask
 *	Type:			Octal mask
 *	Default:		0022
 *	
 *	The global mask for creating files (previews, thumbnails, options, etc.)
 *	for rig. Consult "man umask" on a Unix box or Cygwin for more info which
 *	mainly says "umask sets the umask to mask & 0777".
 *	The default is 0022: "0" to make it an octal number. Then 022
 *	to make it accessible only by the current user, not by group/others.
 *
 ***********************************************************/

$pref_umask				= 0022;



// --- customization of cookies ---


/***********************************************************
 *
 *	Setting: 		$pref_cookie_host
 *	Type:			File-system relative path with \\ separators
 *	Default:		Empty string ''
 *	
 *	The host used for cookies.
 *	It is best to leave empty, in which case the host will be
 *	figured out automatically.
 *
 ***********************************************************/

$pref_cookie_host       = '';



/***********************************************************
 *
 *	Section:	Blah.
 *
 *
 ***********************************************************/





// --- default language & theme ---

$pref_default_lang		= 'en';				// choices are en, fr, sp, jp
$pref_default_theme		= 'gray';			// choices are blue, gray, khaki, egg, sand, black


// --- dates at beginning of album names ---

$pref_date_YM						= 'M/Y';	// format for short dates. M & Y must appear.
/* American */ $pref_date_YMD		= 'M/D/Y';	// format for long dates. D & M & Y must appear.
/* Japanese */ // $pref_date_YMD	= 'Y/M/D';	// format for long dates. D & M & Y must appear.
/* French   */ // $pref_date_YMD	= 'D/N/Y';	// format for long dates. D & M & Y must appear.
$pref_date_sep						= ' - ';	// separator between date and description



// -- IZU specific behavior --

$pref_auto_index		= 'Index';		// Page to open by default (or empty for none)

$pref_page_stats		= TRUE;				// Allow keeping IP access stats per page

$pref_page_stats_exclude = "/192\.168\.[1-3]\./"; // Exclude IP or hostnames matching this regexp

$pref_log_file			= '/var/log/izumi/combined.log'; // Location of log file
$pref_log_hostname		= TRUE;				// TRUE to perform host lookup, FALSE to log IP

/***********************************************************
 *
 *	Setting: 		$pref_copyright_name
 *	Type:			String
 *	Default:		Empty string ''
 *	
 *	The copyright name that appears under albums or images.
 *	
 *	Format is HTML. Use HTML-compliant characters (like &eacute; or &#129;)
 *	Important: if you want to insert Japanese here, add a line in data_jpu8.bin
 *	or use UTF-8 bytes directly in hexa.
 *
 *	This should ideally be overriden by album-specific prefs.php files
 *
 ***********************************************************/

$pref_copyright_name = '';



/***********************************************************
 *
 *	Setting: 		$pref_html_meta
 *	Type:			String
 *	Default:		"<meta name=\"robots\" content=\"index, follow\">"
 *	
 *	The <meta> tag that appears on top of every html page.
 *	
 *	Each album's pref can override this. The default is here.
 *
 ***********************************************************/

$pref_html_meta = "<meta name=\"robots\" content=\"index,follow\">";


/***********************************************************
 *
 *	Setting: 		$pref_html_meta_for_query_string
 *	Type:			String
 *	Default:		"<meta name=\"robots\" content=\"noindex, nofollow\">"
 *	
 *	The <meta> tag that appears on top of every html page WHEN THE
 *	URL CONTAINS A NON-EMPTY QUERY STRING!.
 *	This meta replaces $pref_html_meta if used.
 *
 *	The idea is to provide a html-meta that allows indexing for normal Izumi
 *	pages (when using PATH_INFO to get $page) that have no query arguments;
 * 	and then to use a non-indexing meta for pages that that query arguments
 *	(so that search engines do not cache "alternate" views of the main page).
 *	
 *	Each album's pref can override this. The default is here.
 *
 *  New in Izumi 0.3.3 -- RM 20040218
 *
 ***********************************************************/

$pref_html_meta_for_query_string = "<meta name=\"robots\" content=\"noindex,nofollow\">";

// --- Global display preferences ---



// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.7  2004/12/09 19:45:14  ralf
//	dos2unix
//	
//	Revision 1.6  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.5  2004/12/04 22:22:44  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.4  2004/11/21 18:16:02  ralf
//	Use black theme by default
//	
//	Revision 1.3  2004/09/26 19:30:30  ralf
//	Updated ip exclusion regexp to add 192.168.1.0 and 3.0 segments
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
