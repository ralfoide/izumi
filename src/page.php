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


require_once($dir_install . $dir_src . "common.php");

//-----------------------------------------------------------------------

require_once(izu_require_once("RPagePath.php", $dir_src));
require_once(izu_require_once("RPage.php",     $dir_src));
require_once(izu_require_once("RBlog.php",     $dir_src));
require_once(izu_require_once("RPageStat.php", $dir_src));
require_once(izu_require_once("RPageLog.php",  $dir_src));

//-----------------------------------------------------------------------

/*
if ($_SERVER["REMOTE_ADDR"] == "192.168.2.42")
	$_debug_=1;
if ($_debug_)
	phpinfo(INFO_ALL);
*/

//-----------------------------------------------------------------------

$izu_arg = izu_get($_SERVER,'PATH_INFO');
if (!is_string($izu_arg) || $izu_arg == "")
	$izu_arg = izu_get($_GET,'page');

// if path ends with a slash, add the auto index if allowed
if (is_string($pref_auto_index) && $pref_auto_index != '' && substr($izu_arg, -1) == '/')
	$izu_arg .= $pref_auto_index;

$izu_path = new RPagePath(izu_decode_argument($izu_arg));


// If the page does not exist and the prefs allows for the index to be
// automatically displayed, go for it
global $pref_auto_index;

if (   is_string($pref_auto_index) && $pref_auto_index != ''
	&& ($izu_path->IsEmpty() || ($izu_path->IsPage() && !$izu_path->PageExists())))
{
	$index_path = new RPagePath(null, $izu_path->mDir, $pref_auto_index);
	
	if (! $index_path->PageExists())
	{
		// try to fallback on the root index
		$index_path = new RPagePath(null, '', $pref_auto_index);
	}
	
	// if it worked, use that index path
	if ($index_path->PageExists())
		$izu_path = $index_path;
}

// Create page object

if ($izu_path->IsBlog())
	$izu_page = new RBlog($izu_path);
else
	$izu_page = new RPage($izu_path);

// Prepare page
// Returns TRUE if page can be served (even if it has not been modified)
// Returns FALSE if page must NOT be served at all (it is refused or invalid)

$prepare = $izu_page->Prepare();

// In the case of a blog, this may change the path so let's update it

$izu_path = $izu_page->GetPath();

// Create log object

$izu_log  = new RPageLog($izu_path);


// Create stat object

global $pref_page_stats;
$izu_stat = NULL;
if (is_bool($pref_page_stats) && $pref_page_stats)
	$izu_stat = new RPageStat($izu_path);


// Use Prepare's result and render if not modified
// If prepare returned false, the page must not be served.

if (!$prepare)
{
	// Do not count invalid accesses in access stats

	// Reply and log a 404 Not Found cf. http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4
	 header(izu_get($_SERVER, 'SERVER_PROTOCOL') . " 404 Not Found");
	 header("Status: 404 Not Found");

	$izu_log->SetHttpStatus(404);
	$izu_log->SetSize(0);

	// This is not supposed to print.
	// Send an empty page.
?><html><head><meta name="robots" content="noindex,nofollow,nosnippet,noarchive"></head><body></body></html>
<?php
}
else if ($izu_page->NeedsRedirect())
{
	// Perform the requested redirection here.
	// Do not count redirect in access stats

	// Reply with 302 Found under a different URI cf http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3
	// and provide Location with the new URI.
	// This is better than 301 Moved Permanently as it means the user agent
	// will keep the "old" link rather than the redirected to one.

	header(izu_get($_SERVER, 'SERVER_PROTOCOL') . " 302 Found");
	header("Status: 302 Found");
	header("Location: " . $izu_page->RedirectUrl());

	$izu_log->SetHttpStatus(302);
	$izu_log->SetSize(0);
}
else if ($izu_page->IsNotModified())
{
	// Page has not been modified. Header 304 has already been returned
	// by RPage::handleNotModified.

	// Log a 304 Not Modified cf http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3
	$izu_log->SetHttpStatus(304);
	$izu_log->SetSize(0);

	// Count accesses in access stats
	if ($izu_stat != NULL)
		$izu_stat->AddRemoteIp();
}
else /* Page modified or rebuilt */
{
	// Count accesses in access stats
	if ($izu_stat != NULL)
		$izu_stat->AddRemoteIp();

	// Render page
	$izu_page->RenderPage();
}

//-----------------------------------------------------------------------
// Perform logging of page access

$izu_log->AddLogEntry();
$izu_log->AddLogHttpAcceptLanguage();

// Cleanup

if ($izu_stat != NULL)
	$izu_stat->Release();

//-------------------------------------------------------------
//	$Log$
//	Revision 1.2  2005-04-05 18:54:01  ralfoide
//	Started work on version 1.1
//	Changed blog entries keys from MD5 to encoded date/title clear text.
//	Added internal anchor references to blog entries.
//
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.12  2004/12/20 07:01:37  ralf
//	New minor features. Version 0.9.4
//	
//	Revision 1.11  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.10  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.9  2004/12/04 22:22:03  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.8  2004/11/28 22:38:00  ralf
//	Version 0.9.1: RSS support with ETag/If-Modified.
//	
//	Revision 1.7  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.6  2004/11/22 19:03:35  ralf
//	Added response code 302 for redirections
//	
//	Revision 1.5  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
//	
//	Revision 1.4  2004/11/21 18:17:12  ralf
//	Blog support added. Experimental.
//	
//	Revision 1.3  2004/09/26 19:31:34  ralf
//	Prevent displaying options for search bot user agents
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
