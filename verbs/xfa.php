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
if ( $fb_['verbInfo']['executionMode'] == "start" ) {
	$fb_['cir'] =& $fb_['verbInfo']['action']->getCircuit();
	$fb_['app'] =& $fb_['cir']->getApplication();
	// validate attributes
	// evaluate - boolean default false
	if ( array_key_exists("evaluate",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['evaluate'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'evaluate' must either be \"true\" or \"false\", for a 'xfa' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['evaluate'] = "false";
	}
	// name - string - required
	if ( !array_key_exists("name",$fb_['verbInfo']['attributes']) || trim($fb_['verbInfo']['attributes']['name']) == "" ) {
		$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
					"Required attribute is missing",
					"The attribute 'name' is required when 'overwrite' is present, for a 'xfa' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
	}
	// overwrite - boolean - default true
	if ( array_key_exists("overwrite",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['overwrite'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'overwrite' must either be \"true\" or \"false\", for a 'xfa' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['overwrite'] = "true";
	}
	// value - string - required
	if ( !array_key_exists("value",$fb_['verbInfo']['attributes']) ) {
		$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
					"Required attribute is missing",
					"The attribute 'value' is required, for a 'xfa' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
	}
	// strict mode - check attribute count:
	if ( $fb_['app']->strictMode ) {
		if ( Count($fb_['verbInfo']['attributes']) != 4 ) {
			$this->fb_throw("fusebox.badGrammar.unexpectedAttributes",
						"Unexpected attributes",
						"Unexpected attributes were found in a 'set' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}

	// compile <xfa>
	$name = $fb_['verbInfo']['attributes']['name'];
	if ( $fb_['verbInfo']['attributes']['name']{0} == '$' ) {
		$name = '{' . $name . '}';
	}
	$name = 'XFA["' . $name . '"]';
	$value = $fb_['verbInfo']['attributes']['value'];
	// adjust xfa value if it is local to this circuit:
	// <xfa name="foo" value="bar" /> becomes
	// <xfa name="foo" value="thiscircuit.bar" />
	$fb_['arValue'] = explode('.',$value);
	if ( count($fb_['arValue']) < 2 && $fb_['verbInfo']['attributes']['evaluate'] != "true" ) {
		$value = $fb_['verbInfo']['circuit'] . "." . $value;
	}
	$value = '"' . $value . '"';
	
	if ( $fb_['verbInfo']['attributes']['evaluate'] == "true" ) {
		$value = "eval(" . $value . ")";
	}
	if ( $fb_['verbInfo']['attributes']['overwrite'] == "true" ) {
		$this->fb_appendLine("\$$name = $value;");
	} else {
		$this->fb_appendLine("if ( !isset(\$$name) ) { \$$name = $value; }");
	}
}
?>