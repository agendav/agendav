<?php

require_once("XMLDocument.php");

define('ATOM_XMLNS','http://www.w3.org/2005/Atom');
define('XHTML_XMLNS','http://www.w3.org/1999/xhtml');


/**
 * These two classes here sort of emulate the interface from the  Zend Framework API
 * with regard to ZendFeedWriteFeed for constructing an Atom feed.  Except we do it
 * in a DAViCal way, and we have some huge limitations:
 *  - We *only* support Atom feeds.
 *  - We *only* support creating them.
 *  
 * @author Andrew McMillan <andrew@morphoss.com>
 *
 */

class AtomXHTMLContent /* extends XMLElement */ {
   private $content_string;
   
   function __construct($xhtml) {
     $this->content_string = $xhtml;
   }
   
   function Render( $ignore1, $ignore2, $ignore3 ) {
     return $this->content_string . "\n";
   }
}


class AtomEntry {
  /**
  <entry xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <title type="html"><![CDATA[Woohoo!  Time to Par-tay! (1/1)]]></title>

    <summary type="html"><![CDATA[Have a microparty. All the best parties are monthly!]]></summary>
    <published>2008-10-25T11:07:49+13:00</published>
    <updated>2010-12-27T06:49:16+13:00</updated>
    <id>http://mycaldav/feed.php/user1/home/MICROPARTY-77C6-4FB7-BDD3-6882E2F1BE74.ics#UID:MICROPARTY-77C6-4FB7-BDD3-6882E2F1BE74</id>
    <content xmlns:xhtml="http://www.w3.org/1999/xhtml" type="xhtml">
      <xhtml:div xmlns:xhtml="http://www.w3.org/1999/xhtml"><xhtml:strong>Time:</xhtml:strong> 2008-11-21 16:00:00<xhtml:br/><xhtml:br/><xhtml:strong>Description</xhtml:strong>:<xhtml:br/>Have a microparty. All the best parties are monthly!</xhtml:div>

    </content>
  </entry>
  */
  private $id;
  private $title;
  private $updated;
  private $nodes;

  function __construct( $id, $title, $published, $updated ) {
    $this->nodes = array( 'id', 'title', 'updated' );  // placeholders
  }

  public function setId( $new_value ) {
    $this->id = new XMLElement('id', rtrim($new_value,"\r\n"));
    return $this->id;
  }
  
  public function setTitle( $new_value, $type = 'text' ) {
    $this->title = new XMLElement('title', $new_value, array( 'type' => $type ));
    return $this->title;
  }
  
  public static function setDate( $tagname, $epoch ) {
    // e.g. 2010-12-26T17:49:16+13:00
    return new XMLElement($tagname, date('Y-m-d\TH:i:sP',$epoch));
  }
  
  public function setDateModified( $epoch ) {
    $this->updated = self::setDate('updated', $epoch);
    return $this->updated;
  }
  
  public function setDateCreated( $epoch ) {
    $node = self::setDate('published', $epoch);
    $this->nodes[] = $node;
    return $node;
  }
  
  public function setLink( $new_value, $type="text/calendar", $rel='alternate' ) {
    return $this->addNode('link', $new_value, array( 'rel' => $rel, 'type' => $type ) );
  }

  public function addAuthor( $new_value ) {
    if ( is_array($new_value) && isset($new_value['name']) ) {
      $author = $this->addNode('author' );
      foreach( $new_value AS $k => $v ) {
        $author->NewElement($k, $v);
      }
      return $author;
    }
    throw new Exception("AtomFeed::addAuthor(\$new_value) the \$new_value MUST be an array with at least a 'name' element. RFC4287-3.2");
  }

  
  public function addCategory( $new_value ) {
    if ( is_array($new_value) && isset($new_value['term']) ) {
      $category = $this->addNode('category', null, $new_value );
      return $category;
    }
    throw new Exception("AtomFeed::addCategory(\$new_value) the \$new_value MUST be an array with at least a 'term' element, and potentially a 'scheme' and a 'label' element. RFC4287-4.2.2");
  }

  
  public function setDescription( $new_value, $type = 'text' ) {
    return $this->addNode('summary', $new_value, array( 'type' => $type ) );
  }
  
  public function setContent( $new_value, $type = 'xhtml' ) {
    $content = $this->addNode('content', null, array( 'type' => $type ) );
    if ( $type == 'xhtml' ) {
      $content->NewElement('div', array( new AtomXHTMLContent($new_value) ), array('xmlns' => XHTML_XMLNS));
    }
    else {
      $content->SetContent($new_value); 
    }
    return $content;
  }
  
  public function addNode( $in_tag, $content=false, $attributes=false, $xmlns=null ) {
    $node = new XMLElement($in_tag,$content,$attributes,$xmlns);
    if ( !isset($node) ) return null;
    $this->nodes[] = $node;
    return $node;
  }

  public function getXML() {
    $this->nodes[0] = $this->id;
    $this->nodes[1] = $this->title;
    $this->nodes[2] = $this->updated;
    return $this->nodes;
  }
}


class AtomFeed extends XMLDocument {

  private $id;
  private $title;
  private $updated;
  private $nodes;

  public function __construct() {
    global $c;
    parent::__construct( array( ATOM_XMLNS => null, XHTML_XMLNS => 'xhtml' ) );
    $this->title = 'DAViCal Atom Feed';
    $this->nodes = array( 'id', 'title', 'updated',  // placeholders
        new XMLElement('generator', 'DAViCal', array('uri' => 'http://www.davical.org/', 'version' => $c->version_string ) )
      );
  }

  /*
  <id>http://mycaldav/feed.php/user1/home.ics</id>
  <title type="text">CalDAV Feed: User 1's Calendaranza</title>
  <updated>2010-12-26T17:49:16+13:00</updated>
  <generator uri="http://framework.zend.com" version="1.10.7">Zend_Feed_Writer</generator>
  <link rel="alternate" type="text/html" href="http://mycaldav/feed.php/user1/home.ics"/>
  <link rel="self" type="application/atom+xml" href="http://mycaldav/feed.php/user1/home/"/>
  <author>
    <name>User 1</name>
    <email>user1@example.net</email>
    <uri>http://mycaldav/feed.php/caldav.php/user1/</uri>
  </author>
  */

  public function setId( $new_value ) {
    $this->id = $this->NewXMLElement('id', $new_value);
    return $this->id;
  }
  
  public function setTitle( $new_value, $type = 'text' ) {
    $this->title = $this->NewXMLElement('title', $new_value, array( 'type' => $type ));
    return $this->title;
  }
  
  public function setDateModified( $epoch ) {
    $this->updated = AtomEntry::setDate('updated', $epoch);
    return $this->updated;
  }
  
  public function setLink( $new_value, $type="text/calendar", $rel='alternate' ) {
    return $this->addNode('link', $new_value, array( 'rel' => $rel, 'type' => $type ) );
  }


  /**
   * Sets the feed link (rel=self), ignoring the parameter which is for
   * compatibility with the Zend library API, although we use this for
   * the Id, whereas they use the first link that is set. 
   * @param uri $new_value The link target
   * @return XMLElement the node that was added. 
   */
  public function setFeedLink( $new_value, $type = null ) {
    $this->setId($new_value);
    return $this->setLink($new_value , 'application/atom+xml', 'self' );
  }
  
  public function addAuthor( $new_value ) {
    if ( is_array($new_value) && isset($new_value['name']) ) {
      $author = $this->addNode('author' );
      foreach( $new_value AS $k => $v ) {
        $this->NSElement($author, $k, $v);
      }
      return $author;
    }
    throw new Exception("AtomFeed::addAuthor(\$new_value) the \$new_value MUST be an array with at leas a 'name' element. RFC4287-3.2");
  }

  
  public function setDescription( $new_value, $type = 'text' ) {
    return $this->addNode('subtitle', $new_value, array( 'type' => $type ) );
  }
  
  public function addNode( $in_tag, $content=false, $attributes=false, $xmlns=null ) {
    $node = $this->NewXMLElement($in_tag,$content,$attributes,$xmlns);
    if ( !isset($node) ) return null;
    $this->nodes[] = $node;
    return $node;
  }

  public function addEntry( $new_entry ) {
    if ( !isset($new_entry) ) return;
    $this->nodes[] = new XMLElement('entry', $new_entry->getXML() );
  }

  public function createEntry( $id=null, $title=null, $published=null, $updated=null ) {
    return new AtomEntry($id,$title,$published,$updated);
  }

  public function export( $format='atom' ) {
    if ( $format != 'atom' ) throw new Exception("AtomFeed class only supports creation of Atom 1.0 format feeds.");
    $this->nodes[0] = $this->id;
    $this->nodes[1] = $this->title;
    $this->nodes[2] = $this->updated;
    return $this->Render('feed', $this->nodes );
  }
}

