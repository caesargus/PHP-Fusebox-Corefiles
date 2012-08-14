<?php
/*
Fusebox Software License
Version 1.0

Copyright (c) 2003, 2004, 2005, 2006 The Fusebox Corporation. All rights reserved.

Redistribution && use in source && binary forms, with or without modification, are permitted 
provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions 
   && the following disclaimer.

2. Redistributions in binary form or otherwise encrypted form must reproduce the above copyright 
   notice, this list of conditions && the following disclaimer in the documentation and/or other 
   materials provided with the distribution.

3. The end-user documentation included with the redistribution, if any, must include the following 
   acknowledgment:

   "This product includes software developed by the Fusebox Corporation (http://www.fusebox.org/)."

   Alternately, this acknowledgment may appear in the software itself, if && wherever such 
   third-party acknowledgments normally appear.

4. The names "Fusebox" && "Fusebox Corporation" must not be used to endorse or promote products 
   derived from this software without prior written (non-electronic) permission. For written 
   permission, please contact fusebox@fusebox.org.

5. Products derived from this software may not be called "Fusebox", nor may "Fusebox" appear in 
   their name, without prior written (non-electronic) permission of the Fusebox Corporation. For 
   written permission, please contact fusebox@fusebox.org.

If one or more of the above conditions are violated, then this license is immediately revoked && 
can be re-instated only upon prior written authorization of the Fusebox Corporation.

THIS SOFTWARE IS PROVIDED "AS IS" && ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY && FITNESS FOR A PARTICULAR PURPOSE ARE 
DISCLAIMED. IN NO EVENT SHALL THE FUSEBOX CORPORATION OR ITS CONTRIBUTORS BE LIABLE FOR ANY 
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT 
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
BUSINESS INTERRUPTION) HOWEVER CAUSED && ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE 
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

-------------------------------------------------------------------------------

This software consists of voluntary contributions made by many individuals on behalf of the 
Fusebox Corporation. For more information on Fusebox, please see <http://www.fusebox.org/>.

*/
class MyFusebox { /*I provide the per-request myFusebox data structure && some convenience methods.*/
	var $version;
	var $thisCircuit;
	var $thisFuseaction;
	var $thisPlugin;
	var $thisPhase;
	var $plugins;
	var $parameters;
	var $stack;
	
	var $created;
	var $log;
	var $occurence;
	var $frame;
	
	
	function MyFusebox ( $appKey, $attributes ) { 
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		$this->thisCircuit = "";
		$this->thisFuseaction =  "";
		$this->thisPlugin = "";
		$this->thisPhase = "";
		
		$this->version = array();
		$this->version['runtime']     = "unknown";
		$this->version['loader']      = "unknown";
		$this->version['transformer'] = "unknown";
		$this->version['parser']      = "unknown";
		
		$this->version['runtime']     = "5.0.0.GR.0";
		
		// the basic default is development-full-load mode:
		$this->parameters = array();
		$this->parameters['load'] = true;
		$this->parameters['parse'] = true;
		$this->parameters['execute'] = true;
		// FB5: new execution parameters:
		$this->parameters['clean'] = false;	 	// don't delete parsed files by default
		$this->parameters['parseall'] = false;	// don't compile all fuseactions by default
		  
		$this->parameters['userProvidedLoadParameter'] = false;
		$this->parameters['userProvidedCleanParameter'] = false;
		$this->parameters['userProvidedParseParameter'] = false;
		$this->parameters['userProvidedParseAllParameter'] = false;
		$this->parameters['userProvidedExecuteParameter'] = false;
		
		$this->plugins = array();
		
		// stack frame for do/include parameters:
		$this->stack = array();
	
		
		$this->appKey = $appKey;
		$theFusebox = array();
		
		list($usec, $sec) = explode(" ", microtime());
		$this->created = ((float)$usec + (float)$sec);
		//$this->created = substr($created,-strpos($created,' '));
		$this->log = array();
		$this->occurrence = array();

		// we can't guarantee the fusebox exists in application scope yet...
		if ( isset($GLOBALS['application']) && is_array($GLOBALS['application']) && array_key_exists($this->appKey,$GLOBALS['application']) ) {
			$theFusebox =& $GLOBALS['application'][$this->appKey];
		}
		// default myFusebox.parameters depending on "mode" of the application set in fusebox.xml
		if ( is_object($theFusebox) && array_key_exists("mode",get_object_vars($theFusebox)) ) {
			switch ( $theFusebox->mode ) {
			// FB41 backward compatibility - now deprecated
			case "development" :
				if ( array_key_exists('strictMode',get_object_vars($theFusebox)) && $theFusebox->strictMode ) {
					// since we don't load fusebox.xml if we throw an exception, we must fixup the value for the next run
					$theFusebox->mode = "development-full-load";
					__cfthrow(array( 'type'=>"fusebox.badGrammar.deprecated",
							'message'=>"Deprecated feature",
							'detail'=>"'development' is a deprecated execution mode - use 'development-full-load' instead."));
				}
				$this->parameters['load'] = true;
				$this->parameters['parse'] = true;
				$this->parameters['execute'] = true;
				break;
			// FB5: replacement for old development mode
			case "development-full-load" :
				$this->parameters['load'] = true;
				$this->parameters['parse'] = true;
				$this->parameters['execute'] = true;
				break;
			// FB5: new option - does not load fusebox.xml && therefore does not (re-)load fuseboxApplication object
			case "development-circuit-load" :
				$this->parameters['load'] = false;
				$this->parameters['parse'] = true;
				$this->parameters['execute'] = true;
				break;
			case "production" :
				$this->parameters['load'] = false;
				$this->parameters['parse'] = false;
				$this->parameters['execute'] = true;
				break;
			default:
				// since we don't load fusebox.xml if we throw an exception, we must fixup the value for the next run
				$theFusebox->mode = "development-full-load";
				__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidParameterValue", 
						'message'=>"Parameter has invalid value", 
						'detail'=>"The parameter 'mode' must be one of 'development-full-load', 'development-circuit-load' or 'production' in the fusebox.xml file."));
				break;
			}
		}
		// did the user pass in any special "fuseboxDOT" parameters for this request?
		// If so, process them
		// note: only if attributes.fusebox.password matches the application password
		if ( !isset($attributes["fusebox.password"]) ) { $attributes["fusebox.password"] = ""; }
		if ( is_object($theFusebox) && array_key_exists('password',get_object_vars($theFusebox)) && $theFusebox->password == $attributes['fusebox.password'] ) {
			// FB5: does a load and wipes the parsed files out
			if ( array_key_exists('fusebox.loadclean',$attributes) && in_array($attributes['fusebox.loadclean'],array('true','false')) ) {
				$this->parameters['load'] = ( $attributes['fusebox.loadclean'] == 'true' );
				$this->parameters['clean'] = ( $attributes['fusebox.loadclean'] == 'true' );
				$this->parameters['userProvidedLoadParameter'] = true;
				$this->parameters['userProvidedCleanParameter'] = true;
			}
			if ( array_key_exists('fusebox.load',$attributes) && in_array($attributes['fusebox.load'],array('true','false')) ) {
				$this->parameters['load'] = ( $attributes['fusebox.load'] == 'true' );
				$this->parameters['userProvidedLoadParameter'] = true;
			}
			if ( array_key_exists('fusebox.parseall',$attributes) && in_array($attributes['fusebox.parseall'],array('true','false')) ) {
				$this->parameters['parse'] = ( $attributes['fusebox.parseall'] == 'true' );
				$this->parameters['parseall'] = ( $attributes['fusebox.parseall'] == 'true' );
				if ( $this->parameters['parseall'] ) {
					$this->parameters['load'] = true;
				}
				$this->parameters['userProvidedLoadParameter'] = true;
				$this->parameters['userProvidedParseParameter'] = true;
				$this->parameters['userProvidedParseAllParameter'] = true;
			}
			if ( array_key_exists('fusebox.parse',$attributes) && in_array($attributes['fusebox.parse'],array('true','false')) ) {
				$this->parameters['parse'] = ( $attributes['fusebox.parse'] == 'true' );
				$this->parameters['userProvidedParseParameter'] = true;
			}
			if ( array_key_exists('fusebox.execute',$attributes) && in_array($attributes['fusebox.execute'],array('true','false')) ) {
				$this->parameters['execute'] = ( $attributes['fusebox.execute'] == 'true' );
				$this->parameters['userProvidedExecuteParameter'] = true;
			}
		}
		
		/*
			force a load if the runtime and core versions differ: this allows a new
			version to be dropped in and the framework will automatically reload!
			note: that we must *force* a load, by pretending this is user-provided!
		*/
		if ( is_object($theFusebox) && ( in_array('getversion',get_class_methods($theFusebox)) || in_array('getVersion',get_class_methods($theFusebox)) ) ) {
			if ( $this->version['runtime'] != $theFusebox->getVersion() ) {
				$this->parameters['userProvidedLoadParameter'] = true;
				$this->parameters['load'] = true;
			}
		} else {
			// hmm, doesn't look like the core is present (or it's not FB5 Alpha 2 or higher)
			$this->parameters['userProvidedLoadParameter'] = true;
			$this->parameters['load'] = true;
		}

		// if the fusebox doesn't already exist we definitely want to reload
		if ( is_object($theFusebox) && array_key_exists("isFullyLoaded",get_object_vars($theFusebox)) && $theFusebox->isFullyLoaded ) {
			// if fully loaded, leave the load parameter alone
		} else {
			$this->parameters['load'] = true;
		}
		
		$this->theFusebox =& $theFusebox;
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
	}
	
	function &getApplication () { //I am a convenience method to return the fuseboxApplication object without needing to know reference application scope or the FUSEBOX_APPLICATION_KEY variable.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		/*
			this is a bit of a hack since we're accessing application scope directly 
			but it's probably cleaner than exposing a method to allow fuseboxApplication
			to inject itself back into myFusebox during compileRequest()...
		*/
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $GLOBALS['application'][$this->appKey];
	
	}
	
	function &getCurrentCircuit() { //I am a convenience method to return the current Fusebox circuit object.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $GLOBALS['application'][$this->appKey]->circuits[$this->thisCircuit];
	
	}
	
	function &getCurrentFuseaction() { //I am a convenience method to return the current fuseboxAction (fuseaction) object.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $GLOBALS['application'][$this->appKey]->circuits[$this->thisCircuit]->fuseactions[$this->thisFuseaction];
	
	}
	
	function enterStackFrame() { //I create a new stack frame (for scoped parameters to do/include).
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		$frame = array();
		
		$frame['__fuseboxStack'] = $this->stack;
		$this->stack = $frame;
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function leaveStackFrame() { //I pop the last stack frame (for scoped parameters to do/include).
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		$this->stack = $this->stack['__fuseboxStack'];
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function trace ( $type, $message, $theTime = 0 ) { //I add a line to the execution trace log.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		list($usec, $sec) = explode(" ", microtime());
		$theTime = ( $theTime > 0 ) ? $theTime : ((float)$usec + (float)$sec);
		$this->addTrace(($theTime - $this->created) * 1000,$type,$message);
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}

	function addTrace( //I add a detailed line to the execution trace log.
			$time, //I am the time taken to get to this point in the request.
			$type, //I am the type of trace.
			$message, //I am the trace message.
			$occurrence = 0 //I am a placeholder for part of the struct that is added to the log.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		global $log;
		if ( array_key_exists($message,$this->occurrence) ) {
			$this->occurrence[$message]++;
		} else {
			$this->occurrence[$message] = 1;
		}
		$occurrence = $this->occurrence[$message];
		$log[] = array('time'=>$time,'type'=>$type,'message'=>$message,'occurrence'=>$occurrence);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function renderTrace() { //I render the trace log as HTML.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		global $log;
		$result = "";
		$i = 0;
		
		$result = '
			<br />
			<div style="clear:both;padding-top:10px;border-bottom:1px Solid #CCC;font-family:verdana;font-size:16px;font-weight:bold">Fusebox debugging:</div>
			<br />
			<table cellpadding="2" cellspacing="0" width="100%" style="border:1px Solid #CCC;font-family:verdana;font-size:11pt;">
				<tr style="background:#EAEAEA">
					<td style="border-bottom:1px Solid #CCC;font-family:verdana;font-size:11pt;"><strong>Time</strong></td>
					<td style="border-bottom:1px Solid #CCC;font-family:verdana;font-size:11pt;"><strong>Category</strong></td>
					<td style="border-bottom:1px Solid #CCC;font-family:verdana;font-size:11pt;"><strong>Message</strong></td>
					<td style="border-bottom:1px Solid #CCC;font-family:verdana;font-size:11pt;"><strong>Count</strong></td>
				</tr>
				'; for ( $i = 0 ; $i < count($log) ; $i++ ) {
					$result .= ( $i % 2 > 0 ) ? '<tr style="background:#F9F9F9">' : '<tr style="background:#FFFFFF">'; $result .= '
						<td valign="top" style="font-size:10pt;border-bottom:1px Solid #CCC;font-family:verdana;">'.round($log[$i]['time']).'ms</td>
						<td valign="top" style="font-size:10pt;border-bottom:1px Solid #CCC;font-family:verdana;">'.$log[$i]['type'].'</td>
						<td valign="top" style="font-size:10pt;border-bottom:1px Solid #CCC;font-family:verdana;">'.$log[$i]['message'].'</td>
						<td valign="top" align="center" style="font-size:10pt;border-bottom:1px Solid #CCC;font-family:verdana;">'.$log[$i]['occurrence'].'</td>
					</tr>';
				} $result .= '
			</table>';
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $result;
		
	}

}
?>
