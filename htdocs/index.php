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


// Load location settings (must be done before anything else)

require_once("location.php");

// ------------------------------------------------------------
// DEBUG -- Test installation variable and cry if not happy
// Note this is not technically necessary but is of great help for new installations

function izu_check_src_file($name)
{
	global $dir_install, $dir_src, $dir_globset, $dir_locset;
	
	// enabling track_errors is a big help
	ini_set("track_errors", "1");
	
	// check it worked
	$track_errors = (ini_get("track_errors") == 1);
	
	if ($track_errors)
	    $result = @file_exists($name);
	else
	    $result = file_exists($name);
	if (!$result)
	{
	    echo "<h1>Izumi Configuration Error</h1>";
	    echo "<h2>Error</h2>A source file could not be located! Please check <em>location.php</em> file!";
	    if ($track_errors)
	        echo "<h2>Reason</h2>$php_errormsg";
	    else
	        echo "<h2>Important!</h2>Please consider enabling <em>track_errors</em> in your PHP.INI file!";
	    echo "<h2>Details</h2>";
	    echo "<b>file path</b> = '$name'<br>";
	    echo "<b>dir_instal</b> = '$dir_install'<br>";
	    echo "<b>dir_src</b> = '$dir_src'<br>";
	    echo "<b>dir_globset</b> = '$dir_globset'<br>";
	    echo "<b>dir_locset</b> = '$dir_locset'<br>";
	    echo "<hr>";
	}
	
	return $result;
}

// ------------------------------------------------------------
// call the entry point

izu_check_src_file($dir_install . $dir_src . "entry_point.php");
require_once     ($dir_install . $dir_src . "entry_point.php");

// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.3  2004/12/09 19:43:07  ralf
//	dos2unix
//	
//	Revision 1.2  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.1.1.1  2004/01/05 06:11:58  ralf
//	Version 0.3, stable/testing
//	
//	Revision 1.4  2003/03/12 07:11:45  ralfoide
//	New upload dirs, new entry_point, new meta override
//	
//	Revision 1.3  2003/02/16 20:09:41  ralfoide
//	Update. Version 0.6.3.1
//	
//	Revision 1.2  2002/10/20 09:03:19  ralfoide
//	Display error when require_once files cannot be located
//	
//	Revision 1.1  2002/08/04 00:58:08  ralfoide
//	Uploading 0.6.2 on sourceforge.rig-thumbnail
//	
//	Revision 1.1  2001/11/26 04:35:05  ralf
//	version 0.6 with location.php
//	
//-------------------------------------------------------------
?>
