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

define("DB_REFERAL",	"_referal.db");
define("DB_KEY_TOTAL",	"_total_");




//-----------------------------------------------------------------------


//************
class RPageLog
//************
{
	var $mPath;
	var	$mSize;
	var	$mHttpStatus;


	//***********************
	function RPageLog(&$path)
	//***********************
	{
		// get initial path -- it is up to caller to use izu_decode_argument()
		// and provide a clean RPagePath instance
		$this->mPath = $path;
		
		// set the initial http status to 0 (unset -- will be "guessed" in AddLogEntry)
		$this->mHttpStatus = 0;
		
		// Initially the size is not known
		$this->mSize = NULL;
	}


	//*****************************
	function SetHttpStatus($status)
	//*****************************
	// Override the internal Http Status with a given code, such as
	// 404 Not Found or 304 Not Modified.
	// If not set, AddLogStatus will auto decide on either 200 OK or 404 Not Found.
	{
		$this->mHttpStatus = $status;
	}


	//*********************
	function SetSize($size)
	//*********************
	// Override the byte size (if not called, the path->GetPageSize() will be used)
	{
		$this->mSize = $size;
	}


	//********************
	function AddLogEntry()
	//********************
	{
		global $pref_log_file;
		global $pref_log_hostname;
		global $display_user;
		
		if ($this->mPath == NULL || !is_string($pref_log_file) || $pref_log_file == '')
			return;

		// To keep file access to a minimum, prepare the log string before opening the file.
		// Use Apache's "Combined Log" format, cf http://httpd.apache.org/docs/logs.html#combined
		
		$ip			= izu_get($_SERVER, 'REMOTE_ADDR', '');
		if ($pref_log_hostname)
			$ip = @gethostbyaddr($ip);

		$indent		= '-';
		$user		= is_string($display_user) ? $display_user : izu_get($_SERVER, 'REMOTE_USER', '');
		$reqtime	= date("d/M/Y:H:i:s O");			// Formats with the correct timezone, strftime does not
		$request	= izu_get($_SERVER, 'REQUEST_METHOD', '') . ' ' . izu_get($_SERVER, 'REQUEST_URI', '') . ' ' . izu_get($_SERVER, 'SERVER_PROTOCOL', '');

		if ($this->mHttpStatus > 0)
			$status = $this->mHttpStatus;
		else
			$status	= ($this->mPath->PageExists() || $this->mPath->DirExists()) ? '200' : '404';

		$bytes		= is_int($this->mSize) ? $this->mSize : $this->mPath->GetPageSize();
		$referer	= izu_get($_SERVER, 'HTTP_REFERER', '');
		$useragent	= izu_get($_SERVER, 'HTTP_USER_AGENT', '');
		
		$msg = "$ip $indent $user [$reqtime] \"$request\" $status $bytes \"$referer\" \"$useragent\"";

		// in case of...
		$msg = str_replace("\n", "", $msg);
		$msg = str_replace("\r", "", $msg);

		// HACK tweak the log... -- RM 20040104 better force using url-rewrite once fixed for cookies
		$msg = str_replace("/index.php", "", $msg);

		$msg .= "\n";

		// Open log file... this may fail for a variety of reasons (insufficient rights,
		// directory not existing, etc.). Report errors if any.
		
		$file = @fopen($pref_log_file, "a+t");

		if (!$file)
			return izu_html_error("Add Log Entry",
								  "Failed to open log file",
								  $pref_log_file,
								  $php_errormsg);

		// use flock... ignore failures (really? yeah, it's just a log, dude!!)
		if (flock($file, LOCK_EX))
		{
			fwrite($file, $msg);
			flock ($file, LOCK_UN);
		}
 
		fclose($file);
	}


	//*********************************
	function AddLogHttpAcceptLanguage()
	//*********************************
	// RM 20040212
	{
		global $pref_log_file;
		global $pref_log_hostname;
		global $display_user;
		
		if ($this->mPath == NULL || !is_string($pref_log_file) || $pref_log_file == '')
			return;

		// HACK: log into "logfile.lang"
		$lang_log_file = $pref_log_file . ".lang";

		// To keep file access to a minimum, prepare the log string before opening the file.
		
		$ip = izu_get($_SERVER, 'REMOTE_ADDR');
		if ($pref_log_hostname)
			$ip = @gethostbyaddr($ip);


		$useragent = izu_get($_SERVER, 'HTTP_USER_AGENT', '');
		$lang = izu_get($_SERVER, 'HTTP_ACCEPT_LANGUAGE', '');
		
		$msg = "$ip\t\"$lang\"\t\"$useragent\"\n";

		// Open log file... this may fail for a variety of reasons (insufficient rights,
		// directory not existing, etc.). Report errors if any.
		
		$file = @fopen($lang_log_file, "a+t");

		if (!$file)
			return izu_html_error("Add Log Entry",
								  "Failed to open accept-lang log file",
								  $lang_log_file,
								  $php_errormsg);

		// use flock... ignore failures (really? yeah, it's just a log, dude!!)
		if (flock($file, LOCK_EX))
		{
			fwrite($file, $msg);
			flock ($file, LOCK_UN);
		}
 
		fclose($file);
	}




	//-----------------------------------------------------------------------
	// private methods




} // end RPageLog

//-----------------------------------------------------------------------




//-----------------------------------------------------------------------
// end

//-------------------------------------------------------------
//	$Log$
//	Revision 1.1  2005-02-16 02:04:51  ralfoide
//	Stable version 0.9.4 updated to SourceForge
//
//	Revision 1.5  2004/12/09 19:44:06  ralf
//	dos2unix
//	
//	Revision 1.4  2004/12/06 07:54:08  ralf
//	Using GPL headers
//	
//	Revision 1.3  2004/11/27 23:23:38  ralf
//	RSS support.
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
