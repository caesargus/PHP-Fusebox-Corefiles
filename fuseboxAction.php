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
class FuseboxAction { //I represent a fuseaction within a circuit.

	var $circuit;
	var $name;
	var $customAttributes;
	var $nChildren;
	var $actions;
	var $access;
	
	function FuseboxAction /*I am the constructor.*/ (
			&$circuit, //I am the circuit to which this fuseaction belongs. I am required but it's faster to specify that I am not required.
			$name, //I am the name of the fuseaction. I am required but it's faster to specify that I am not required.
			$access, //I am the access criteria for the fuseaction. I am required but it's faster to specify that I am not required.
			$children, //I am the verbs for this fuseaction. I am required but it's faster to specify that I am not required.
			$global = false, //I indicate whether or not this is a globalfuseaction in fusebox.xml.
			$customAttribs = array() //I hold the custom (namespace-qualified) attributes in the fuseaction tag.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$i = 0;
		$verb = "";
		$app =& $circuit->getApplication();
		$factory =& $app->getFuseactionFactory();

		$this->circuit =& $circuit;
		$this->name = $name;
		$this->customAttributes = $customAttribs;
		$this->nChildren = count($children);
		$this->actions = array();
		//var_dump($children);
		$this->access = $access;
		for ( $i = 0 ; $i < $this->nChildren ; $i++ ) {
			$this->actions[$i] =& $factory->create($children[$i]['xmlName'],
					$this,$children[$i]['xmlAttributes'],$children[$i]['xmlChildren'],
						$global);
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}
	
	function compile /*I compile this fuseaction.*/ (
			&$writer //I am the writer object to which the compiled code should be written. I am required but it's faster to specify that I am not required.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$i = 0;
		$n = 0;
		$this->context = array();
		for ( $i = 0 ; $i < $this->nChildren ; $i++ ) {
			$this->actions[$i]->compile($writer,$this->context);
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function getName() { //I return the name of the fuseaction.
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->name;
		
	}

	function &getCircuit() { //I return the enclosing circuit object.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->circuit;
	
	}
	
	function getAccess() { //I am a convenience method to return this fuseaction's access attribute value.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->access;
	
	}
	
	function getPermissions /*I return the aggregated permissions for this fuseaction.*/ (
			$inheritFromCircuit = true, //I indicate whether or not the circuit's permissions should be returned if this fuseaction has no permissions specified.";
			$useCircuitTrace = false //I indicate whether or not to inherit the parent circuit's permissions if this fuseaction's circuit has no permissions specified.
		) {
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( $this->permissions == "" && $inheritFromCircuit ) {
			$_c = $this->getCircuit();
			return $_c->getPermissions($useCircuitTrace);
		} else {
			return $this->permissions;
		}
	
	}
	
	function getCustomAttributes /*I return the custom (namespace-qualified) attributes for this fuseaction tag.*/ (
			$ns //I am the namespace prefix whose attributes should be returned.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( array_key_exists($ns,$this->customAttributes) ) {
			// we structCopy() this so folks can't poke values back into the metadata!
			//return structCopy($this->customAttributes[$ns]);
			return $this->customAttributes[$ns];
		} else {
			return $array();
		}
		
	}
	
}
?>