<?php
/*
Copyright 2006 TeraTech, Inc. http://teratech.com/


Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/
if ( $fb_['verbInfo']['executionMode'] == "start" ) {
	// validate attributes
	$fb_['nAttrs'] = 0;
	$fb_['cir'] =& $fb_['verbInfo']['action']->getCircuit();
	$fb_['app'] =& $fb_['cir']->getApplication();
	// arguments - string default ""
	if ( !array_key_exists("arguments",$fb_['verbInfo']['attributes']) ) {
		// prepare to gather up <argument> tags, if any:
		$fb_['verbInfo']['data']['arguments'] = "";
		$fb_['verbInfo']['data']['separator'] = "";
	} else {
		$fb_['nAttrs']++;	// for arguments - since we do not default it
		if ( $fb_['verbInfo']['hasChildren'] ) {
			$this->fb_throw("fusebox.badGrammar.unexpectedChildren",
						"Unexpected child verbs",
						"The 'instantiate' verb cannot have children when using the 'arguments' attribute, in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}
	// class - string default ""
	// webservice - string default ""
	// one of class or webservice must be present
	if ( !array_key_exists("class",$fb_['verbInfo']['attributes']) ) {
		if ( !array_key_exists("webservice",$fb_['verbInfo']['attributes']) ) {
			// error: class or webservice must be present
			$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
						"Required attribute is missing",
						"Either the attribute 'class' or 'webservice' is required, for a 'instantiate' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		} else {
			// webservice
		}
	} else {
		if ( !array_key_exists("webservice",$fb_['verbInfo']['attributes']) ) {
			// class
		} else {
			// error: only one of class or webservice may be present
			$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
						"Required attribute is missing",
						"Either the attribute 'class' or 'webservice' is required, for a 'instantiate' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}
	$fb_['nAttrs']++;	// for either one of class or webservice
	// object - string - required
	if ( !array_key_exists("object",$fb_['verbInfo']['attributes']) ) {
		$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
					"Required attribute is missing",
					"The attribute 'object' is required, for a 'instantiate' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
	}
	$fb_['nAttrs']++;	// for object
	// overwrite - boolean default true
	if ( array_key_exists("overwrite",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['overwrite'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'overwrite' must either be \"true\" or \"false\", for a 'instantiate' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['overwrite'] = "true";
	}
	$fb_['nAttrs']++;	// for overwrite - since we default it
	// strict mode - check attribute count:
	if ( $fb_['app']->strictMode ) {
		if ( count($fb_['verbInfo']['attributes']) != $fb_['nAttrs'] ) {
			$this->fb_throw("fusebox.badGrammar.unexpectedAttributes",
						"Unexpected attributes",
						"Unexpected attributes were found in a 'instantiate' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}

} else {
	
	// update arguments if we had any child <argument> tags:
	if ( array_key_exists("arguments",$fb_['verbInfo']['attributes']) ) {
		$fb_['args'] = $fb_['verbInfo']['attributes']['arguments'];
	} else {
		$fb_['args'] = $fb_['verbInfo']['data']['arguments'];
	}

	// compile <instantiate>
	$fb_['obj'] = $fb_['verbInfo']['attributes']['object'];
	$fb_['constructor'] = "";
	/*
	if ( find("##",fb_.obj) gt 0 ) {
		$this->fb_.obj = '"' & fb_.obj & '"';
	}
	*/
	if ( array_key_exists("class",$fb_['verbInfo']['attributes']) ) {
		// look up the class definition:
		$fb_['classDef'] =& $fb_['app']->getClassDefinition($fb_['verbInfo']['attributes']['class']);
		$fb_['creation'] = 'require_once($application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$fb_['classDef']->classpath.'");';
		$fb_['constructor'] = $fb_['classDef']->constructor;
	} else {
		$fb_['creation'] = '/*createObject("webservice","'.$fb_['verbInfo']['attributes']['webservice'].'")*/';
	}
	// I'd rather the constructor was called immediately on construction but it can't be guaranteed that the constructor returns this
	$fb_['parseObj'] = '$';
	if ( $fb_['obj']{0} == '$' ) $fb_['parseObj'] .= '{';
	$fb_['parseObj'] .= $fb_['obj'];
	if ( $fb_['obj']{0} == '$' ) $fb_['parseObj'] .= '}';
	if ( $fb_['verbInfo']['attributes']['overwrite'] != "true" ) {
		$this->fb_appendLine('if ( !isset('.$fb_['parseObj'].') ) {');
	}
	$this->fb_appendLine($fb_['creation']);
	$this->fb_appendSegment($fb_['parseObj'].' = new '.$fb_['verbInfo']['attributes']['class']);
	if ( $fb_['args'] != "" ) {
		$this->fb_appendSegment('('.$fb_['args'].');');
	} else {
		$this->fb_appendSegment('();');
	}
	if ( $fb_['verbInfo']['attributes']['overwrite'] != "true" ) {
		$this->fb_appendLine('}');
	}
}
?>