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
		// <false> has no attributes
		// <false> must be nested inside an <if>
		if ( !array_key_exists("parent",$fb_['verbInfo']) || $fb_['verbInfo']['parent']['lexiconVerb'] != "if" ) {
			$this->fb_throw("fusebox.badGrammar.falseNeedsIf",
						"Verb 'false' has no parent 'if' verb",
						"Found 'false' verb with no parent 'if' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
		// strict mode - check attribute count:
		$fb_['theCircuit'] =& $fb_['verbInfo']['action']->getCircuit();
		$fb_['theApp'] =& $fb_['theCircuit']->getApplication();
		if ( $fb_['theApp']->strictMode ) {
			if ( count($fb_['verbInfo']['attributes']) != 0 ) {
				$this->fb_throw("fusebox.badGrammar.unexpectedAttributes",
							"Unexpected attributes",
							"Unexpected attributes were found in a 'false' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
			}
		}
			
		// compile <false>
		$this->fb_appendLine("} else {");
	}
?>