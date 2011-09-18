<?php

/**
* Given a <response><href>...</href><propstat><prop><someprop/></prop><status>HTTP/1.1 200 OK</status></propstat>...</response>
* pull out the content of <someprop>content</someprop> and check to see if it has any href elements.  If it *does* then
* recurse into them, looking for the next deeper nesting of properties.
*/
function get_href_containers( &$multistatus_response ) {
  $propstat_set = $multistatus_response->GetElements('propstat');
  $propstat_200 = null;
  foreach( $propstat_set AS $k => $v ) {
    $status = $v->GetElements('status');
    if ( preg_match( '{^HTTP/\S+\s+200}', $status[0]->GetContent() ) ) {
      $propstat_200 = $v;
      break;
    }
  }
  if ( isset($propstat_200) ) {
    $props = $propstat_200->GetElements('prop');
    $properties = array();
    foreach( $props AS $k => $p ) {
      $properties = array_merge($properties,$p->GetElements());
    }
    $href_containers = array();
    foreach( $properties AS $k => $property ) {
      if ( !is_object($property) ) continue;
//      dbg_error_log('REPORT',' get_href_containers: Checking property "%s" for hrefs.', $property->GetNSTag() );
      $hrefs = $property->GetElements('href');
      if ( count($hrefs) > 0 ) {
        $href_containers[] = $property;
      }
    }
    if ( count($href_containers) > 0 ) {
      return $href_containers;
    }
  }
  return null;
}


/**
* Expand the properties, recursing only once
*/
function expand_properties( $urls, $ptree, &$reply, $recurse_again = true ) {
  if ( !is_array($urls) )  $urls = array($urls);
  if ( !is_array($ptree) ) $ptree = array($ptree);

  $responses = array();
  foreach( $urls AS $m => $url ) {
    $resource = new DAVResource($url);
    $props = array();
    $subtrees = array();
    foreach( $ptree AS $n => $property ) {
      if ( ! is_object($property) ) continue;
      $pname = $property->GetAttribute('name');
      $pns = $property->GetAttribute('namespace');
      if ( !isset($pns) || $pns == '' ) $pns = 'DAV:';  // Not sure if this is the correct way to default this.
      $pname = $pns .':'. $pname;
      $props[] = $pname;
      $subtrees[$pname] = $property->GetElements();
    }
    $part_response = $resource->RenderAsXML( $props, $reply );
    if ( isset($part_response) ) {
      if ( $recurse_again ) {
        $href_containers = get_href_containers($part_response);
        if ( isset($href_containers) ) {
          foreach( $href_containers AS $h => $property ) {
            $hrefs = $property->GetElements();
            $pname = $property->GetTag();
            $pns = $property->GetAttribute('xmlns');
            if ( !isset($pns) || $pns == '' ) $pns = 'DAV:';  // Not sure if this is the correct way to default this.
            $pname = $pns .':'. $pname;
            $paths = array();
            foreach( $hrefs AS $k => $v ) {
              $content = $v->GetContent();
              $paths[] = $content;
            }
//            dbg_error_log('REPORT',' Found property "%s" contains hrefs "%s"', $pname, implode(', ',$paths) );
            $property->SetContent( expand_properties($paths, $subtrees[$pname], $reply, false) );
          }
        }
//      else {
//        dbg_error_log('REPORT',' No href containers in response to "%s"', implode(', ', $props ) );
//      }
      }
      $responses[] = $part_response;
    }
  }

  return $responses;
}


/**
 * Build the array of properties to include in the report output
 */
$property_tree = $xmltree->GetPath('/DAV::expand-property/DAV::property');

$multistatus = new XMLElement( "multistatus", expand_properties( $request->path, $property_tree, $reply), $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
