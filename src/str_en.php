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

// English Language Strings for Izumi



// HTML encoding
//--------------

// Encoding for HTML web pages. Cannot be empty.

$html_encoding		= 'ISO-8859-1';		// cf http://www.w3.org/TR/REC-html40/charset.html#h-5.2.2
$html_language_code	= 'en-US';			// cf http://www.w3.org/TR/REC-html40/struct/dirlang.html#h-8.1.1


// Current Locale
//---------------

// Lib-C locale, mainly used to generate dates and time with the correct language.
// On Debian, run 'dpkg-reconfigure locales' as root and make sure the locale is installed.
//
// Neither 'en' nor 'en_EN' work for me in English. Using 'C' instead as fallback.
// This is expected to be be ISO-8859 not UTF-8

$lang_locale        = array('en_US', 'en_EN', 'en', 'C');


// Languages availables
//---------------------

$html_language		= 'Language:';
$html_desc_lang		= array('en' => 'English',
							'fr' => 'Fran&ccedil;ais',
							'sp' => 'Espa&ntilde;ol',
							'jp' => '&#26085;&#26412;&#35486;'
							);

// Themes available
//-----------------

$html_theme			= 'Color Theme:';
$html_desc_theme	= array('gray'  => 'Gray',
							'blue'  => 'Blue',
							'black'	=> 'Black',
							'sand'  => 'Sand',
							'khaki' => 'Khaki',
							'egg'	=> 'Egg',
							'none'	=> 'None');


// HTML content
//-------------

$html_options		= 'Options';

$html_generated		= 'Generated in [time] seconds the <i>[date]</i> by <i>[rig-version]</i>';

$html_admin_intrfce	= 'Administration Interface';

$html_rig_admin		= 'Izumi Administration Interface';
$html_comment_stats	= 'Stats for album and sub-albums:';
$html_album_stat	= '[bytes] bytes used by [files] files in [folders] folders';

$html_credits		= 'Credits';
$html_show_credits	= 'Display Izumi & PHP Credits';
$html_hide_credits	= 'Hide Credits';
$html_text_credits	= 'R\'alf Izumi (<a href="http://izumi.alfray.com">Izumi</a>) &copy; 2003-2004 by R\'alf<br>';
$html_text_credits .= 'Izumi is diffused under the terms of the <a href="http://izumi.alfray.com/license.html">Izumi license</a> (<a href="http://www.opensource.org/licenses/">OSL</a>).<br>';
$html_text_credits .= 'Based on <a href="http://www.php.net">PHP</a>.<br>';

$html_phpinfo		= 'PHP Server Information';
$html_show_phpinfo	= 'Display PHP Server Information';
$html_hide_phpinfo	= 'Hide PHP Server Information';


// Script Content
//---------------

// Date formatiing
// Date formating for $html_footer_date, $html_img_date and $html_album_date uses
// the PHP's date() notation, cf http://www.php.net/manual/en/function.date.php
// Now using notation from http://www.php.net/manual/en/function.strftime.php
$html_footer_date	= '%m/%d/%Y, %I:%M %p';

// Album Title
$html_content		= 'Izumi';
$html_admin			= 'Admin';
$html_none			= 'Home';

// Current Album
$html_root			= 'Start';

// Images
$html_image			= 'Image';
$html_prev			= 'Previous';
$html_next			= 'Next';
$html_image2		= 'image';
$html_pixels		= 'pixels';
$html_ok			= 'Change';
$html_img_size		= 'Image Size';
$html_original		= 'Original';


// Number formating
$html_num_dec_sep	= '.';		// separator for decimals (ex 25.00 in English)
$html_num_th_sep	= ',';		// separator for thousand (ex 1,000 in English)


// Image date displayed
// Now using notation from http://www.php.net/manual/en/function.strftime.php
$html_img_date		= '%A %B %d %Y, %I:%M %p'; //l\, F d\, Y\, g:m A';

// Album date displayed
// cf http://www.php.net/manual/en/function.strftime.php
$html_content_date	= '%B %Y';

// end

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
//	Revision 1.4  2004/12/04 22:22:03  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.3  2004/11/21 18:16:50  ralf
//	New black theme
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
