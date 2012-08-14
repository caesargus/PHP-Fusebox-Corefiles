<?php
function GetChildren($vals, &$i, $parentXPath) {

   $children = array();

   while (++$i < sizeof($vals)) {

       // compair type
       switch ($vals[$i]['type']) {

           case 'cdata':
               $children[] = $vals[$i]['value'];
               break;
           case 'complete':
               $children[] = array(
                   'xmlName' => $vals[$i]['tag'], 
                   'xmlAttributes' => getAttributes($vals, $i), 
                   'xmlValue' => getValue($vals, $i),
				   'xmlChildren' => array(),
				   'xmlPath' => $parentXPath.'/'.$vals[$i]['tag']
               );
               break;
           case 'open':
               $children[] = array(
                   'xmlName' => $vals[$i]['tag'], 
                   'xmlAttributes' => getAttributes($vals, $i), 
                   'xmlValue' => getValue($vals, $i), 
                   'xmlChildren' => GetChildren($vals, $i, $parentXPath.'/'.$vals[$i]['tag']),
				   'xmlPath' => $parentXPath.'/'.$vals[$i]['tag']
               );        
               break;
           case 'close':
               return $children;
       }
   }
}

function getAttributes($vals, &$i){
	$attributes = array();
	if ( array_key_exists("attributes",$vals[$i]) ) {
		$attributes = $vals[$i]["attributes"];
	}
	return $attributes;
}

function getValue($vals, &$i){
	$value = "";
	if ( array_key_exists("value",$vals[$i]) ) {
		$value = $vals[$i]["value"];
	}
	return $value;
}

function xmlParse($data, $enc, $bList="") {

   $bArray = array();
	// if any attributes were passed to the function, add them to the array
	if ( strlen($bList) > 0 ) $bArray = explode(",",$bList);
	
	do {
		
		$okay = false;
		// by: waldo@wh-e.com - trim space around tags not within
		$data = eregi_replace(">"."[[:space:]]+"."<","><",$data);
		
		// XML functions
		if ( false == ( $p = @xml_parser_create(strtoupper($enc)) ) ) break;
		
		// by: anony@mous.com - meets XML 1.0 specification
		if ( false == ( @xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0) ) ) break;
		if (false == ( @xml_parse_into_struct($p, $data, $vals, $index) ) ) break;
		if ( false == ( @xml_parser_free($p) ) ) break;
		$okay = true;
		
   } while ( false );
   if ( $okay ) {

		for ( $x = 0 ; $x < count($vals) ; $x++ ) {
			if ( array_key_exists("attributes",$vals[$x]) ) {
				foreach ( $vals[$x]["attributes"] as $thiskey=>$thisvalue ) {
					
					// if the attribute name exists in the "bList" then re-cast the string to a boolean
					if ( ( is_string($thisvalue) && array_search($thiskey,$bArray) !== false ) && ( strtolower($thisvalue) == "true" || strtolower($thisvalue) == "false" ) ) {
						$vals[$x]["attributes"][$thiskey] = (strtolower($thisvalue)=="true");
					}
				}
			}
		}
	   
	   $i = 0;
	   
	   $tree["xmlChildren"] = array();
	   $tree["xmlChildren"][] = array(
	       'xmlName' => $vals[$i]['tag'], 
	       'xmlAttributes' => getAttributes($vals, $i), 
	       'xmlValue' => getValue($vals, $i), 
	       'xmlChildren' => GetChildren($vals, $i, '//'.$vals[$i]['tag']),
		   'xmlPath' => '//'.$vals[$i]['tag']
	   );
		$tree['xmlRoot'] = $tree['xmlChildren'][0];
		unset($tree['xmlChildren']);
	   return $tree;
   } else return false;
}

function xmlSearch($xmlNode,$match, $clearRet = false) {
	static $ret = array();
	if ( $clearRet ) $ret = array();
	if ( isset($xmlNode['xmlRoot']) ) $xmlNode = $xmlNode['xmlRoot'];
	if ( isset($xmlNode['xmlPath']) && $xmlNode['xmlPath'] == $match ) {
		$ret[] = $xmlNode;
	}
	if ( isset($xmlNode['xmlChildren']) && count($xmlNode['xmlChildren']) > 0 ) {
		for ( $n = 0 ; $n < count($xmlNode['xmlChildren']) ; $n++ ) {
			xmlSearch($xmlNode['xmlChildren'][$n],$match);
		}
	}
	return $ret;
}

//=============================================================+
?>