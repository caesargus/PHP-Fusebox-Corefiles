<?php
/*
Fusebox Software License
Version 1.0

Copyright (c) 2003, 2004, 2005, 2006 The Fusebox Corporation. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted 
provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions 
   and the following disclaimer.

2. Redistributions in binary form or otherwise encrypted form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in the documentation and/or other 
   materials provided with the distribution.

3. The end-user documentation included with the redistribution, if any, must include the following 
   acknowledgment:

   "This product includes software developed by the Fusebox Corporation (http://www.fusebox.org/)."

   Alternately, this acknowledgment may appear in the software itself, if and wherever such 
   third-party acknowledgments normally appear.

4. The names "Fusebox" and "Fusebox Corporation" must not be used to endorse or promote products 
   derived from this software without prior written (non-electronic) permission. For written 
   permission, please contact fusebox@fusebox.org.

5. Products derived from this software may not be called "Fusebox", nor may "Fusebox" appear in 
   their name, without prior written (non-electronic) permission of the Fusebox Corporation. For 
   written permission, please contact fusebox@fusebox.org.

If one or more of the above conditions are violated, then this license is immediately revoked and 
can be re-instated only upon prior written authorization of the Fusebox Corporation.

THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
DISCLAIMED. IN NO EVENT SHALL THE FUSEBOX CORPORATION OR ITS CONTRIBUTORS BE LIABLE FOR ANY 
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT 
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE 
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

-------------------------------------------------------------------------------

This software consists of voluntary contributions made by many individuals on behalf of the 
Fusebox Corporation. For more information on Fusebox, please see <http://www.fusebox.org/>.

*/
function __cfthrow($_cfcatch){
	global $cfcatch;
	$_cfcatch["detail"] = htmlspecialchars($_cfcatch["detail"]);
	$cfcatch = $_cfcatch;
	if ( isset($GLOBALS['_fba']->debug) && $GLOBALS['_fba']->debug && isset($GLOBALS['myFusebox']) ) {
		$GLOBALS['myFusebox']->trace("Fusebox","Caught Fusebox exception '{$cfcatch['type']}'");
	}
	die( (!@include($GLOBALS["FUSEBOX_APPLICATION_PATH"]."errortemplates/".$_cfcatch["type"].".php") ) ? $_cfcatch["detail"] : null );
}

function Location($URL, $addToken = 1) {
	$questionORamp = (strstr($URL, "?"))?"&":"?";
	$location = ( $addToken && substr($URL, 0, 7) != "http://" && defined('SID') ) ? $URL.$questionORamp.SID : $URL; //append the sessionID ($SID) by default
	//ob_end_clean(); //clear buffer, end collection of content
	if(headers_sent()) {
		print('<script type="text/javascript">( document.location.replace ) ? document.location.replace("'.$location.'") : document.location.href = "'.$location.'";</script>'."\n".'<noscript><meta http-equiv="Refresh" content="0;URL='.$location.'" /></noscript>');
	} else {
		header('Location: '.$location); //forward to another page
		exit; //end the PHP processing
	}
}
function isPHP5() {
	$thisversion = phpversion();
	return ( $thisversion{0} >= 5 );
}
// FB5: allow "" default - FB41 required this variable:
if ( !isset($FUSEBOX_APPLICATION_PATH) ) $FUSEBOX_APPLICATION_PATH = "";
if ( strlen($FUSEBOX_APPLICATION_PATH) > 0 && substr($FUSEBOX_APPLICATION_PATH, -1) != "/" ) {
	$FUSEBOX_APPLICATION_PATH .= '/';
}
// FB5: application key - FB41 always uses 'fusebox':
if ( !isset($FUSEBOX_APPLICATION_KEY) ) $FUSEBOX_APPLICATION_KEY = "fusebox";

// FB5: application name - same as FB41 - FB4 always uses 'cacheddata':
if ( !isset($FUSEBOX_APPLICATION_NAME) ) $FUSEBOX_APPLICATION_NAME = "cacheddata";

if( !isset($attributes) || !is_array($attributes) ) {
	$attributes = array();
	$attributes = array_merge($_GET,$_POST); 
}

if ( isset( $_SERVER['QUERY_STRING']) and strlen($_SERVER['QUERY_STRING']) > 0 ) {
  // loop through query string to "fix" url variable names with dots in them
  $qs_array = split("[\&;]",$_SERVER['QUERY_STRING']);
  for ( $i = 0 ; $i < count( $qs_array ) ; $i++ ) {
    @list($thisname,$thisvalue) = explode("=",$qs_array[$i]);
    if ( !isset($attributes[$thisname]) ) $attributes[$thisname] = $thisvalue;
  }
}

require_once("myFusebox.php");
if ( !isPHP5() ) { eval('$myFusebox =& new MyFusebox($FUSEBOX_APPLICATION_KEY,$attributes);'); } else { $myFusebox = new MyFusebox($FUSEBOX_APPLICATION_KEY,$attributes); }

if ( isset($FUSEBOX_APPLICATION_STARTUPTIME) ) $myFusebox->trace('Fusebox','Deserialized app_ datafile',$FUSEBOX_APPLICATION_STARTUPTIME);
// FB5: indicates whether application was started on this request
$myFusebox->applicationStart = false;
// FB5: uses request.__fusebox for internal tracking of compiler / runtime operations:
$_REQUEST['__fusebox'] = array();
/*
	complex condition allows FB5 to drop into a running FB41 site and force re-init because
	FB41 application.fusebox will not have compileRequest() method - this should make upgrades
	to FB5 easier!
*/
if ( !isset($application[$FUSEBOX_APPLICATION_KEY]) || $myFusebox->parameters['load'] ) {
	$fa = @fopen($FUSEBOX_APPLICATION_PATH."parsed/_app_".$FUSEBOX_APPLICATION_NAME.".php","r") or
		$fa = fopen($FUSEBOX_APPLICATION_PATH."parsed/_app_".$FUSEBOX_APPLICATION_NAME.".php","w");
	if(!flock($fa,LOCK_EX)){
		$myFusebox->trace("FuseboxError","Could not get exclusive lock to application data file");
	}
	
	if ( !isset($application[$FUSEBOX_APPLICATION_KEY]) || $myFusebox->parameters['load'] ) {
		// if it doesn't exisit or the user explicitly requested a load it
		if ( !isset($application[$FUSEBOX_APPLICATION_KEY]) || $myFusebox->parameters['userProvidedLoadParameter'] ) {
			// can't be conditional: we don't know the state of the debug flag yet
			$myFusebox->trace("Fusebox","Creating Fusebox application object");
			require_once("fuseboxApplication.php");
			if ( !isPHP5() ) { eval('$_fba =& new FuseboxApplication();'); } else { $_fba = new FuseboxApplication(); }
			$application[$FUSEBOX_APPLICATION_KEY] = $_fba->init($FUSEBOX_APPLICATION_KEY,$FUSEBOX_APPLICATION_PATH,$FUSEBOX_APPLICATION_NAME,$myFusebox);
		//echo "fusebox5 still running...";
		} else {
			// can't be conditional: we don't know the state of the debug flag yet
			$myFusebox->trace("Fusebox","Reloading Fusebox application object");
			@include($FUSEBOX_APPLICATION_PATH."parsed/_app_".$FUSEBOX_APPLICATION_NAME.".php");
			$_fba =& $application[$FUSEBOX_APPLICATION_KEY];
			// it exists and the load is implicit, not explicit (via user) so just reload XML
			$_fba->reload($FUSEBOX_APPLICATION_KEY,$FUSEBOX_APPLICATION_PATH,$myFusebox);
		}
		// fix attributes precedence
		if ( $_fba->precedenceFormOrURL == "URL" ) {
			$attributes = array_merge($_POST,$_GET);
		}
		// set the default fuseaction if necessary
		if ( !array_key_exists($_fba->fuseactionVariable,$attributes) || $attributes[$_fba->fuseactionVariable] == "" ) {
			$attributes[$_fba->fuseactionVariable] = $_fba->defaultFuseaction;
		}
		// set this up for fusebox.appinit.php
		$attributes['fuseaction'] = $attributes[$_fba->fuseactionVariable];
		// flag this as the first request for the application
		$myFusebox->applicationStart = true;
		// force parse after reload for consistency
		if ( $_fba->mode != "production" || $myFusebox->parameters['userProvidedLoadParameter'] ) {
			$myFusebox->parameters['parse'] = true;
		}
		
		// need all of the above set before we attempt any compiles!
		if ( $myFusebox->parameters['parseall'] ) {
			$_fba->compileAll($myFusebox);
		}
		// FB5: new appinit include file
		if ( $_fba->debug ) {
			$myFusebox->trace("Fusebox","Including fusebox.appinit.php");
		}
		if ( file_exists($_fba->getWebRootPathToappRoot()."fusebox.appinit.php") ) {
			include($_fba->getWebRootPathToappRoot()."fusebox.appinit.php");
		}
	} else {
		$_fba =& $application[$FUSEBOX_APPLICATION_KEY];
		// fix attributes precedence
		if ( $_fba->precedenceFormOrURL == "URL" ) {
			$attributes = array_merge($_POST,$_GET);
		}
		// set the default fuseaction if necessary
		if ( !array_key_exists($_fba->fuseactionVariable,$attributes) || $attributes[$_fba->fuseactionVariable] == "" ) {
			$attributes[$_fba->fuseactionVariable] = $_fba->defaultFuseaction;
		}
		$attributes['fuseaction'] = $attributes[$_fba->fuseactionVariable];
	}
	@flock($fa,LOCK_UN);
	fclose($fa);
} else {
	$_fba =& $application[$FUSEBOX_APPLICATION_KEY];
	// fix attributes precedence
	if ( $_fba->precedenceFormOrURL == "URL" ) {
		$attributes = array_merge($_POST,$_GET);
	}
	// set the default fuseaction if necessary
	if ( !array_key_exists($_fba->fuseactionVariable,$attributes) || $attributes[$_fba->fuseactionVariable] == "" ) {
		$attributes[$_fba->fuseactionVariable] = $_fba->defaultFuseaction;
	}
	$attributes['fuseaction'] = $attributes[$_fba->fuseactionVariable];
}



/*
	Fusebox 4.1 did not set attributes.fuseaction or default the fuseaction variable until
	*after* fusebox.init.php had run. This made it hard for fusebox.init.php to do URL
	rewriting. For Fusebox 5, we default the fuseaction variable and set attributes.fuseaction
	before fusebox.init.php so it can rely on attributes.fuseaction and rewrite that. However,
	in order to maintain backward compatibility, we need to allow fusebox.init.php to set
	attributes[$_fba->fuseactionVariable] and still have that reflected in attributes.fuseaction
	and for that to actually be the request that gets processed.
*/
if ( $_fba->debug ) {
	$myFusebox->trace("Fusebox","Including fusebox.init.php");
}
if ( file_exists($_fba->getWebRootPathToappRoot()."fusebox.init.php") ) {
	$_fba_attr_fav = $attributes[$_fba->fuseactionVariable];
	$_fba_attr_fa = $attributes['fuseaction'];
	include($_fba->getWebRootPathToappRoot()."fusebox.init.php");
	if ( $attributes['fuseaction'] != $_fba_attr_fa ) {
		if ( $attributes['fuseaction'] != $attributes[$_fba->fuseactionVariable] ) {
			if ( $attributes[$_fba->fuseactionVariable] != $_fba_attr_fav ) {
				// inconsistent modification of both variables?!?
				__cfthrow(array( 'type'=>"fusebox.inconsistentFuseaction",
					'message'=>"Inconsistent fuseaction variables",
					'detail'=>"Both attributes.fuseaction and attributes[{fusebox}.fuseactionVariable] changed in fusebox.init.php so Fusebox doesn't know what to do with the values!"
				));
			} else {
				// ok, only attributes.fuseaction changed
				$attributes[$_fba->fuseactionVariable] = $attributes['fuseaction'];
			}
		} else {
			// ok, they were both changed and they match
		}
	} else {
		// attributes.fuseaction did not change
		if ( $attributes[$_fba->fuseactionVariable] != $_fba_attr_fav ) {
			// make attributes.fuseaction match the other changed variable
			$attributes['fuseaction'] = $attributes[$_fba->fuseactionVariable];
		} else {
			// ok, neither variable changed
		}
	}
}
/*
	by default, the _fba file included by index.php is the "slim" version of the object, to
	keep the unserializing overhead to a minimum. If there is a parse request however, we 
	definitely need the full object, so we need to include it now, if it exists. It's not
	necessary to include if there is a load request.
*/
if ( !$myFusebox->parameters['load'] && ( $myFusebox->parameters['parse'] || !file_exists($_fba->approotdirectory."parsed/".strtolower($attributes['fuseaction']).".php" ) ) ) {
	if ( $_fba->debug ) $myFusebox->trace("Fusebox","Replacing slim AFB object with full copy");
	include($FUSEBOX_APPLICATION_PATH."parsed/_app_".$FUSEBOX_APPLICATION_NAME.".php");
	unset($_fba);
	$_fba =& $application[$FUSEBOX_APPLICATION_KEY];
}

/*
	must special case development-circuit-load mode since it causes circuits to reload during
	the compile (post-load) phase and therefore must be exclusive
*/
if ( $_fba->debug ) {
	$myFusebox->trace("Fusebox","Compiling requested fuseaction '{$attributes['fuseaction']}'");
}
if ( $_fba->mode == "development-circuit-load" ) {
	$fa = fopen($FUSEBOX_APPLICATION_PATH."parsed/_app_".$FUSEBOX_APPLICATION_NAME.".php","r");
	if(!flock($fa,LOCK_EX)){
		$myFusebox->trace("FuseboxError","Could not get exclusive lock to application data file");
	}
	$_parsedFileData = $_fba->compileRequest($attributes['fuseaction'],$myFusebox);
	@flock($fa,LOCK_UN);
	fclose($fa);
} else {
	$fa = fopen($FUSEBOX_APPLICATION_PATH."parsed/_app_".$FUSEBOX_APPLICATION_NAME.".php","r");
	if ( !flock($fa,LOCK_SH) ) {
		$myFusebox->trace("FuseboxError","Could not get exclusive lock to application data file");
	}
	$_parsedFileData = $_fba->compileRequest($attributes['fuseaction'],$myFusebox);
	@flock($fa,LOCK_UN);
	fclose($fa);

}
/*
	readonly lock protects against including the parsed file while
	another threading is writing it...
*/
if ( $myFusebox->parameters['execute'] ) {
	if ( $_fba->debug ) {
		$myFusebox->trace("Fusebox","Including parsed file for '{$attributes['fuseaction']}'");
	}
	if ( file_exists($_parsedFileData['lockName']) ) {
		$fp = fopen($_parsedFileData['lockName'],'r');
		if ( !flock($fp,LOCK_SH) ) {
			$myFusebox->trace("FuseboxError","Could not get exclusive lock to parsed fuseaction file");
		}
		include($_parsedFileData['parsedFile']);
		@flock($fp,LOCK_UN);
		fclose($fp);
	} else {
		__cfthrow(array( 'type'=>"fusebox.missingParsedFile", 
			'message'=>"Parsed File or Directory not found.",
			'detail'=>"fusebox5.php line 269: Attempting to execute the parsed file '{$_parsedFileData['parsedName']}' threw an error. This can occur if the parsed file does not exist in the parsed directory or if the parsed directory itself is missing."
		));
	}
}


if ( $myFusebox->parameters['load'] ) {
	if ( $_fba->debug ) $myFusebox->trace("Fusebox","Saving app data file...");
	$_fba->writeFullObjectToDisk();
	$_fba->writeSlimObjectToDisk();
}

$myFusebox->trace("Fusebox","Request completed");
if ( isset($_fba->debug) && $_fba->debug == "true" && isset($myFusebox) ) {
	echo $myFusebox->renderTrace();
}
?>