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

// RIG Theme: blue


// --- css header ---

$theme_css_head			= '';


// --- page colors ---

$color_body_bg			= '#99CCFF';
$color_body_text		= '#000000';
$color_body_link		= '#000099';
$color_body_alink		= '#000099';
$color_body_vlink		= '#990099';

$color_title_bg			= '#3399FF';
$color_title_text		= '#000000';

$color_section_bg		= $color_title_bg;
$color_section_text		= $color_title_text;

$color_header_bg		= '#3399FF';
$color_header_text		= '#FFFFCC';

$color_table_border		= '#000000';
$color_table_bg			= '#FFFFFF';
$color_table_infos		= '#BBBBBB';

$color_image_border		= $color_table_bg;
$color_caption_bg		= $color_table_bg;
$color_caption_text		= $color_body_text;

$color_index_text		= '#800000';
$color_warning_bg		= '#00CC66';

$color_error1_bg		= '#FF9966';	// '#FFFF33';
$color_error2_bg		= '#FFFF99';	// '#FFFF33';

// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.3  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.2  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//	Revision 1.3  2003/02/16 20:22:58  ralfoide
//	New in 0.6.3:
//	- Display copyright in image page, display number of images/albums in tables
//	- Hidden fix_option in admin page to convert option.txt from 0.6.2 to 0.6.3 (experimental)
//	- Using rig_options directory
//	- Renamed src function with rig_ prefix everywhere
//	- Only display phpinfo if _debug_ enabled or admin mode
//	
//	Revision 1.2  2003/01/20 12:39:51  ralfoide
//	Started version 0.6.3. Display: show number of albums or images in table view.
//	Display: display copyright in images or album mode with pref name and language strings.
//	
//	Revision 1.1  2002/10/21 01:52:48  ralfoide
//	Multiple language and theme support
//	
//	Revision 1.1  2002/10/14 07:05:17  ralf
//	Update 0.6.3 build 1
//	
//-------------------------------------------------------------
?>
