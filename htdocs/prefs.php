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
//
//
// LOCAL PREFS for _this_ specific album.
// Defaults are in rig/settings/prefs.php and can
// be overrided here.

// ---- Access/Misc Prefs ----


// ---- Copyright Name for albums & images ----

// Format is HTML. Use HTML-compliant characters (like &eacute; or &#129;)
// Important: if you want to insert Japanese here, add a line in data_jpu8.bin
// or use UTF-8 bytes directly in hexa.

$pref_title_name     = 'Nickname';
$pref_copyright_name = 'Your Name Here';



$pref_html_site_license = '\n' . 'This work is licensed by ' . $pref_copyright_name . '\n';

// --- meta tags for album/image pages ---

// Uncomment the next line if you want robots index and follow autorized for this album
// $pref_html_meta = "";


// ---- URL-Rewrite support ---

// If non empty, URLs will be rewritten using this rule.
// %A is the URL-encoded album name, %I is the URL-encoded image name.
// There are 3 kind of urls: main index, album URL and image URL.

// Example:
// If you define something like this in your Apache's httpd.conf file
//
// LoadModule rewrite_module /usr/lib/apache/1.3/mod_rewrite.so
// <VirtualHost 192.168.0.0>
//     ServerName www.example.com
//     DocumentRoot /home/user/rig/
//     RewriteEngine On
//     RewriteRule ^/i=([^/]+)/(.*)$   http://www.example.com/index.php?image=$1&album=$2
//     RewriteRule ^/a=(.*)$           http://www.example.com/index.php?album=$1
//     RewriteRule ^/$                 http://www.example.com/index.php
// </VirtualHost>
//
// Then you can use an URL-rewrite like this:
// 
// $pref_url_rewrite = array('index' => "http://www.example.com/",
// 							 'album' => "http://www.example.com/a=%A",
// 							 'image' => "http://www.example.com/i=%I/%A");


// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:10:45  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.5  2004/12/09 19:43:07  ralf
//	dos2unix
//	
//	Revision 1.4  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.3  2004/12/04 22:22:44  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
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
