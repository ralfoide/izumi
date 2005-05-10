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

$izu_version = "1.1.1";

// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.4  2005-05-10 18:06:27  ralfoide
//	Fixed a minor bug in the RSS export: accents where improperly encoded as HTML entities.
//
//	Revision 1.3  2005/04/05 18:54:01  ralfoide
//	Started work on version 1.1
//	Changed blog entries keys from MD5 to encoded date/title clear text.
//	Added internal anchor references to blog entries.
//	
//	Revision 1.2  2005/02/16 02:33:02  ralfoide
//	Version 1.0 on SourceForge with DEB package
//	
//	Revision 1.1  2005/02/16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//	
//	Revision 1.9  2004/12/20 07:01:37  ralf
//	New minor features. Version 0.9.4
//	
//	Revision 1.8  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.7  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.6  2004/12/04 22:22:03  ralf
//	Cleaned some obsolete RIG code.
//	Removed login and admin/users management.
//	Moved to version 0.9.3.
//	
//	Revision 1.5  2004/11/28 22:38:00  ralf
//	Version 0.9.1: RSS support with ETag/If-Modified.
//	
//	Revision 1.4  2004/11/27 23:23:38  ralf
//	RSS support.
//	
//	Revision 1.3  2004/11/22 04:01:38  ralf
//	Added blog archives.
//	Added Google site search.
//	Moved to version 0.9 (testing before going 1.0)
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
