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

// --- album & system-dependent locations ---

// ---- global settings ---
  
// base installation directory (absolute path)
// Needs to use / or \ (Win) and need to ends up with a dir separator
$dir_install			= '/home/ralf/files/dev/php/sourceforge/izumi/';	// linux
# $dir_install			= 'C:\RalfDev\php\sourceforge\izumi\\';				// windows

// php sources (relative to $dir_install)
// Needs to use / or \ (Win) and need to ends up with a dir separator
$dir_src				= 'src/';		// linux	
# $dir_src				= 'src\\';		// windows

// global settings (relative to $dir_install)
// Needs to use / or \ (Win) and need to ends up with a dir separator
$dir_globset			= 'settings/';	// linux
# $dir_globset			= 'settings\\';	// windows

// ---- local settings ---

// the entry-point directory (i.e. _this_ directory in absolute)
$dir_abs_content = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME']);
if (file_exists($dir_abs_content))
{
	if (is_file($dir_abs_content))
	{
		$dir_abs_content = pathinfo($dir_abs_content);
		$dir_abs_content = $dir_abs_content['dirname'];
	}
}
else
{
	izu_check_src_file($p1);	// invalid configuration or exploit?
}

// local settings
$dir_locset				= './';

// album location (relative to dir_abs_album)
$dir_album				= 'my-content';
$dir_preview			= 'tg-cache';
$dir_option				= 'tg-options';


// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.2  2005-04-05 18:51:36  ralfoide
//	Updated for Windows vs Linux config
//
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.4  2004/12/09 19:43:07  ralf
//	dos2unix
//	
//	Revision 1.3  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.2  2004/01/06 09:07:38  ralf
//	Removed obsolete tg-image
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//	Revision 1.3  2003/03/12 07:11:45  ralfoide
//	New upload dirs, new entry_point, new meta override
//	
//	Revision 1.2  2003/02/16 20:09:41  ralfoide
//	Update. Version 0.6.3.1
//	
//	Revision 1.1  2002/08/04 00:58:08  ralfoide
//	Uploading 0.6.2 on sourceforge.rig-thumbnail
//	
//	Revision 1.1  2001/11/26 04:35:05  ralf
//	version 0.6 with location.php
//	
//-------------------------------------------------------------
?>
