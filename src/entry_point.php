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

// This script is called directly by index.php
// At this point, nothing has been loaded yet.
// The only rig function defined is izu_check_src_file.

// ------------------------------------------------------------


//*************************
function rig_select($param)
//*************************
{
	global $_GET;
	global $dir_install;
	global $dir_src;
	
	$dir = $dir_install . $dir_src;
	$filename = $param . ".php";

	if (isset($_GET[$param]) && is_string($_GET[$param]))
		return "require_once(izu_check_src_file(\"$dir$filename\")); exit();";

	return ";";
}


eval(rig_select('php_credits'));
eval(rig_select('edit'));
require_once(izu_check_src_file($dir_install . $dir_src . "page.php"));


// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.2  2006-02-27 03:45:47  ralfoide
//	Fixes
//
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.4  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.3  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.2  2004/12/04 22:22:03  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//-------------------------------------------------------------
?>
