<?

/**********************************************************************
 *                       XMPP PubSub for DAViCal
 *           Copyright 2009 Rob Ostensen rob@boxacle.net
 *         Licenced http://gnu.org/copyleft/gpl.html GNU GPL v2
 *
 *********************************************************************/


class xmpp
{
	private $connection,$streamTagBegin,$streamTagEnd,$mesgcount=0,$ready,$moredata=false,$username,$stream,$xmlparser,$xquery;
	private $namespaces = Array();
	private $recvTags = Array();
	private $recvHandlers = Array();
	private $sendHandlers = Array();
	private $finishedCommands = Array();
	private $sendQueue = Array();
	private $recvQueue = '';
	private $pubsubNext = Array();
	private $depth = 0,$processDepth=0;
	public $server,$port,$jid,$resource,$password,$tls,$idle,$status,$pubsubLayout='hometree';

	// constructor
	public function __construct ( )
	{
		$this->status = "online";
		$this->setupXmlParser ();
	}

	// figure out what server to connect to and make the connection, returns true if successful, false otherwise
	private function connect ()
	{
		if ( ! isset ( $this->jid ) )
			return $this->connection = false;
		if ( ! isset ( $this->idle ) )
			$this->idle = true;
		if ( ! isset ( $this->resource ) )
			$this->resource = 'caldav' . getmypid();
		if ( ! preg_match ( '/^\//', $this->resource ) )
			$this->resource = '/' . $this->resource;
		$temp = explode ( '@', $this->jid );
		$this->username = $temp[0];
		if ( ! isset ( $this->server ) )
		{
			$this->server = $temp[1];
		}
		$r = dns_get_record("_xmpp-client._tcp.". $this->server , DNS_SRV);
		if ( 0 < count ( $r ) )
		{
			$this->original_server   = $this->server;
			$this->server            = $r[0]['target'];
			$this->original_port     = $this->port;
			$this->port              = $r[0]['port'];
		}
		if ( ! isset ( $this->port ) )
			$this->port = 5222;
		if ( 'ssl' == $this->tls || ( ! isset ( $this->tls ) && 5223 == $this->port ) )
			$url = 'ssl://' . $this->server;
		elseif ( 'tls' == $this->tls || ( ! isset ( $this->tls ) && 5222 == $this->port ) )
			$url = 'tcp://' . $this->server;
		else
			$url = 'tcp://' . $this->server;
		if ( isset ( $this->original_server ) )
			$this->server = $this->original_server;
		$this->connection = stream_socket_client ( $url . ':' . $this->port, $errno, $errstring, 10, STREAM_CLIENT_ASYNC_CONNECT );
		if ( false === $this->connection )
		{
			if ( $errno != 0 )
				$log = $errstring;
			return false;
		}
		$this->initializeQueue ( );
		socket_set_blocking ( $this->connection, false );
		return true;
	}

	// handles the features tag, mostly related to authentication
	private function handleFeatures ( &$node )
	{
		if ( $this->debug ) $this->log ( 'handling features' );
		if ( 'STARTTLS' == $node->firstChild->nodeName )
		{
			$this->sendQueue[] = "<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>";
			return;
		}
		$elements = $this->query ( '*/MECHANISM', $node );
		if ( ! is_null ( $elements )  && $elements !== false )
		{
			if ( $this->debug ) $this->log ( " found " . $elements->length . " matching MECHANISM nodes ");
			$auth_mech = array ();
			foreach ( $elements as $e )
				$auth_mech[] = $e->nodeValue;
			if ( in_array ( 'PLAIN', $auth_mech ) )
				$this->sendQueue[] = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . base64_encode("\x00" . preg_replace('/@.*$/','',$this->jid) . "\x00" . $this->password) . "</auth>";
			elseif ( in_array ( 'DIGEST-MD5', $auth_mech ) ) // this code and the associated function are UNTESTED
			{
				$this->sendQueue[] = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>";
				$this->recvHandlers['challenge'] = 'digestAuth' ;
			}
			$this->recvHandlers['success'] = 'handleSuccess' ;
		}
		$elements = $this->query ( '*/BIND', $node );
		if ( ! is_null ( $elements ) && $elements->length > 0 )
		{
			// failure if we don't hit this, not sure how we can detect that failure yet.
			if ( $this->debug ) $this->log ( " found " . $elements->length . " matching BIND nodes ");
			$this->ready = true;
		}
	}

	// handle proceed tag/enable tls
	private function enableTLS ( $node )
	{
		stream_set_blocking ( $this->connection, true );
		stream_socket_enable_crypto ( $this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT );
		stream_set_blocking ( $this->connection, false );
		$this->sendQueue[] = "<"."?xml version=\"1.0\"?".">\n\n<stream:stream to='" . $this->server . "' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
	}

	// do digest auth
	private function digestAuth ( &$node )
	{
		// this code is based solely on the description found @ http://web.archive.org/web/20050224191820/http://cataclysm.cx/wip/digest-md5-crash.html
		// UNTESTED please shoot me an email if you get this to work !!
		$contents = $node->nodeValue;
		if ( ! is_null ( $elements ) )
		{
			$challlenge = array ();
			$parts = explode ( ',', base64_decode ( $contents ) );
			foreach ( $parts as $text )
			{
				$temp = explode ( '=', $text );
				$challenge[$temp[0]] = $temp[1];
			}
			if ( $challenge['realm'] == $this->server ) // might fail need to handle a response with multiple realms
			{
				$cnonce =  md5((mt_rand() * time() / mt_rand())+$challenge['nonce']);
				$X =  md5 ( preg_replace('/@.*$/','',$this->jid) . ':' . $this->server . ':' .  $this->password, true );
				$HA1 = md5 ( $X . ':' . $challenge['nonce'] . ':' . $cnonce . ':' . $this->jid . $this->resource );
				$HA2 = md5 ( "AUTHENTICATE:xmpp/" . $this->server );
				$resp = md5 ( $HA1 . ':' . $challenge['nonce'] . ':00000001:' . $cnonce . ':auth' . $HA2 );
				$this->sendQueue[] = "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>" .
					base64_encode("username=\"" . preg_replace('/@.*$/','',$this->jid) . "\"," .
					"realm=\"" . $this->server . "\",nonce=\"" . $challenge['nonce'] . "\",cnonce=\"". $cnonce . "\"," .
					"nc=00000001,qop=auth,digest-uri=\"xmpp/" . $this->server . "\",response=" . $resp .
					",charset=utf-8,authzid=\"". $this->jid . $this->resource . "\"" ) . "</response>" // note the PID component to the resource, just incase
					;
			}
			elseif ( $challenge['rspauth'] )
				$this->sendQueue[] = "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>" ;
		}
	}

	// do basic setup to get the connection logged in and going
	private function handleSuccess ( &$node )
	{
		$this->loggedIn = true;
		$this->sendQueue[] = "<"."?xml version=\"1.0\"?".">\n\n<stream:stream to='" . $this->server . "' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
		$this->sendQueue[] = "<iq xmlns='jabber:client' type='set' id='1'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>" . preg_replace('/^\//','',$this->resource) . "</resource></bind></iq>";
		$this->recvHandlers['stream:error'] = 'handleError' ;
		$this->recvHandlers['iq'] = 'handleIq' ;
		$this->recvHandlers['message'] = 'handleMessage' ;
		$this->mesgcount = 1;
	}

	// do something with standard iq messages also does some standard setup like setting presence
	private function handleIq ( &$node )
	{
		if ( $this->debug ) $this->log ( "Handle IQ id:" . $node->getAttribute ( 'id' ) . ' type:' . $node->getAttribute ( 'type' ) . "");
		if ( $node->getAttribute ( 'type' ) == 'result' || $node->getAttribute ( 'type' ) == 'error' )
		{
			$commandId = $node->getAttribute ( 'id' );
			$this->command[$commandId] = true;
			if ( isset ( $this->handleCommand[$commandId] ) )
			{
				$this->finishedCommands[$commandId] = true;
				if ( method_exists ( $this, $this->handleCommand[$commandId] ) )
					call_user_func_array ( array ( $this, $this->handleCommand[$commandId] ),  array ( &$node ) );
				else
					call_user_func_array ( $this->handleCommand[$commandId], array ( &$node ) );
			}
		}
		if ( $node->getAttribute ( 'id' ) == $this->mesgcount && $this->mesgcount < 3 )
		{
			$this->sendQueue[] = "<iq xmlns='jabber:client' type='set' id='" . ( $this->mesgcount++ ) . "'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>";
			$this->sendQueue[] = "<iq xmlns='jabber:client' type='get' id='" . ( $this->mesgcount++ ) . "'><query xmlns='jabber:iq:roster' /></iq>";
		}
		if ( $node->getAttribute ( 'id' ) == '2' && $this->command['2'] == true )
		{
			$this->nextreply = $this->mesgcount++;
			$this->sendQueue[] = "<presence id='" . $this->nextreply . "' ><status>" . $this->status . '</status></presence>';
			$this->ready = true;
		}
	}

	// do something with standard messages
	private function handleMessage ( &$node )
	{
		if ( $node->getAttribute ( 'type' ) == 'chat' )
		{
			$this->command[$node->getAttribute ( 'id' )] = true;
			$elements = $this->query ( '//*/body', $node );
			if ( 0 < $elements->length )
			{
				$temp = $elements->items(0);
				if ( $this->debug ) $this->log ( "received message " . $temp->nodeValue );
			}
		}
	}

	// handle stream errors by logging a message and closing the connection
	private function handleError ( &$node )
	{
		$this->log ( 'STREAM ERROR OCCURRED! XMPP closing connection, this is probably a bug' );
		$this->idle = false;
		$this->close ();
	}

	//  disco a pubsub collection
	private function disco ( $to, $type, $name )
	{
		$msg = $this->mesgcount++;
		$send  = "<iq type='get' from='" . $this->jid . $this->resource . "' to='$to' id='" . $msg . "'>";
		$send .= "	<query xmlns='http://jabber.org/protocol/disco#$type' node='$name'/>";
		$send .= "</iq>";
		$this->handleCommand[$msg] = 'discoResult';
		$this->sendQueue[] = $send;
		$this->go();
	}

	//  result from disco
	private function discoResult ( &$node )
	{
		if ( $this->debug ) $this->log ( $node->ownerDocument->saveXML($node) );
		$id = $node->getAttribute ( 'id' );
		$identity = $this->query ( '*/IDENTITY', $node );
		if ( @is_array ( $this->pubsub [ 'create' ] [ $id ] ) && 0 == $identity->length )
		{
			$this->pubsubCreateNode( $this->pubsub [ 'create' ] [ $id ] [ 0 ],
				$this->pubsub [ 'create' ] [ $id ] [ 1 ],
				$this->pubsub [ 'create' ] [ $id ] [ 2 ],
				$this->pubsub [ 'create' ] [ $id ] [ 3 ] );
		}
	}

	//  send a message to a jid
	public function sendMessage ( $to, $message )
	{
		$msg = $this->mesgcount++;
		$out .= "<message id='" . $msg . "' from='" . $this->jid . $this->resource . "' to='" . $to. "' >";
		$out .= "<body>" . $message . "</body></message>";
		$this->sendQueue[] = $out;
		$this->go();
	}

	//	get a pubsub collection/leaf node and create if it doesn't exist
	public function pubsubCreate ( $to, $type, $name, $configure = null )
	{
		if ( 1 > strlen ( $to ) )
			$to = 'pubsub.' . $this->server;
		if ( 1 > strlen ( $type ) )
			$type = 'set';
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$this->pubsub['create'][$this->mesgcount+1] = array ( $to, $type, $name, $configure );
		$this->disco ( $to, 'info', $node );
	}

	//	create a pubsub collection/leaf node
	private function pubsubCreateNode ( $to, $type, $name, $configure = null )
	{
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$msg = $this->mesgcount++;
		$out = '<iq from="' . $this->jid . $this->resource . '" to="' . $to . '" type="' . $type . '" id="' . $msg . '">';
		$out .= '<pubsub xmlns="http://jabber.org/protocol/pubsub" ><create node="' . $node . '"/>';
		if ( $configure )
			$out .= '<configure>' . $configure .' </configure>';
		else
			$out .= '<configure/>';
		$out .= '</pubsub>';
		$out .= '</iq>';
		$this->sendQueue[] = $out;
		$this->handleCommand[ $msg ] = 'pubsubResult';
		$this->go();
	}

	//	configure a pubsub collection or leaf
	public function pubsubConfig ( $to, $type, $name )
	{
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$msg = $this->mesgcount++;
		$out = '<iq from="' . $this->jid . $this->resource . '" to="' . $to . '" type="get" id="' . $msg . '">';
		$out .= '<pubsub xmlns="http://jabber.org/protocol/pubsub#owner" ><configure node="' . $node . '"/>';
		$out .= '</pubsub>';
		$out .= '</iq>';
		$this->handleCommand[ $msg ] = 'pubsubResult';
		$this->sendQueue[] = $out;
		$this->go();
	}

	//	delete a pubsub collection or leaf
	public function pubsubDelete ( $to, $type, $name )
	{
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$msg = $this->mesgcount++;
		$out = '<iq from="' . $this->jid . $this->resource . '" to="' . $to . '" type="' . $type . '" id="' . $msg . '">';
		$out .= '<pubsub xmlns="http://jabber.org/protocol/pubsub#owner"><delete node="' . $node . '"/>';
		$out .= '</pubsub>';
		$out .= '</iq>';
		$this->handleCommand[ $msg ] = 'pubsubResult';
		$this->sendQueue[] = $out;
		$this->go();
	}

	//	purge a pubsub collection or leaf
	public function pubsubPurge ( $to, $type, $name )
	{
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$msg = $this->mesgcount++;
		$out = '<iq from="' . $this->jid . $this->resource . '" to="' . $to . '" type="' . $type . '" id="' . $msg . '">';
		$out .= '<pubsub xmlns="http://jabber.org/protocol/pubsub#owner"><purge node="' . $node . '"/>';
		$out .= '</pubsub>';
		$out .= '</iq>';
		$this->handleCommand[ $msg ] = 'pubsubResult';
		$this->sendQueue[] = $out;
		$this->go();
	}

	//	publish to a pubsub collection
	public function pubsubPublish ( $to, $type, $name, $contents, $nodeId )
	{
		if ( 1 > strlen ( $to ) )
			$to = 'pubsub.' . $this->server;
		if ( 1 > strlen ( $type ) )
			$type = 'set';
		if ( 1 > strlen ( $nodeId ) )
			$id = "id='$nodeId'";
		else
			$id = '';
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$msg = $this->mesgcount++;
		$out = '<iq from="' . $this->jid . $this->resource . '" to="' . $to . '" type="' . $type . '" id="' . $msg . '">';
		$out .= '<pubsub xmlns="http://jabber.org/protocol/pubsub"><publish node="' . $node . '">';
		if ( preg_match ( '/^<item/', $contents ) )
			$out .= $contents;
		else
			$out .= '<item ' . $id . '>' . $contents . '</item>';
		$out .= '</publish></pubsub>';
		$out .= '</iq>';
		$this->sendQueue[] = $out;
		$this->handleCommand[ $msg ] = 'pubsubResult';
		$this->go();
	}

	// subscribe to a pubsub collection,leaf or item
	private function pubsubSubscribe ( $to, $type, $name )
	{
		$msg = $this->mesgcount++;
		if ( 'hometree' == $this->pubsubLayout )
			$node = '/home/' . $this->server . '/' . $this->username . $name;
		else
			$node= $name;
		$out = '<iq from="' . $this->jid . $this->resource . '" to="' . $to . '" type="' . $type . '" id="' . $msg . '">';
		$out .= '<pubsub xmlns="http://jabber.org/protocol/pubsub"><subscribe node="' . $name . '" jid="' . $this->jid . $this->resource . '"/>';
		$out .= '</pubsub>';
		$out .= '</iq>';
		$this->sendQueue[] = $out;
		$this->handleCommand[ $msg ] = 'pubsubResult';
		$this->go();
	}

	private function pubsubResult ( &$node )
	{
		if ( $this->debug ) $this->log ( "pubsub RESULT   !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
		if ( $node->getAttribute ( 'type' ) == 'error' )
		{
			$errnode = $this->query ( 'ERROR', $node );
			if ( $errnode->length > 0 && (  '403' == $errnode->item( 0 )->getAttribute ( 'code' ) || '500' == $errnode->item( 0 )->getAttribute ( 'code' ) ) )
			{
				if ( 'CREATE' == $node->firstChild->firstChild->tagName )
				{
					$pubnode = $node->firstChild->firstChild->getAttribute ( 'node' );
					if ( $this->debug ) $this->log ( "403 error during CREATE for node '" . $pubnode . "' ");
					$name =  preg_replace ( '/^.*?\/' . $this->username . '\//','', $pubnode );
					$newnode = '';
					if ( ! in_array ( 'create', $this->pubsubNext ) )
					{
						$a = array ( );
						foreach ( explode ( '/', $name ) as $v )
						{
							$newnode .= '/' . $v;
							$a[] = array (
								'call' => 'create',
								'to' => $node->getAttribute ( 'from' ),
								'name' => $newnode );
						}
						foreach ( array_reverse ( $a ) as $v )
							array_unshift ( $this->pubsubNext, $v );
						$this->pubsubDoNext ( );
					}
				}
			}
			elseif ( $errnode->length > 0 && '404' == $errnode->item( 0 )->getAttribute ( 'code' ) )
			{
				if ( 'PUBLISH' == $node->firstChild->firstChild->tagName )
				{
					$pubnode = $node->firstChild->firstChild->getAttribute ( 'node' );
					if ( $this->debug ) $this->log ( "404 error during PUBLISH for node '" . $pubnode . "' ");
					$publish = $this->query ( '//*/PUBLISH', $node );
					$this->pubsubNext[] = array (
						'call' => 'publish',
						'to' => $node->getAttribute ( 'from' ),
						'name' => preg_replace ( '/^.*?\/' . $this->username . '/','', $pubnode ) ,
						'contents' => $publish->item( 0 )->firstChild->nodeValue );
					if ( $this->debug ) $this->log ( "attempting to create node '" . $this->pubsubNext[0]['name'] . "' ");
					$this->pubsubCreateNode ( $node->getAttribute ( 'from' ) ,'set', preg_replace ( '/^.*?\/' . $this->username . '/','', $pubnode ) );
				}
			}
			elseif ( $errnode->length > 0 && '409' == $errnode->item( 0 )->getAttribute ( 'code' ) )
			{
				if ( 'CANCEL' == $errnode->item( 0 )->firstChild->tagName || 'CONFLICT' == $errnode->item( 0 )->firstChild->tagName )
					$this->pubsubDoNext ( );
			}
		}
		elseif ( 0 < count ( $this->pubsubNext ) )
			$this->pubsubDoNext ( );
	}

	// do next pubsub request
	private function pubsubDoNext ( )
	{
		if ( 0 < count ( $this->pubsubNext ) )
		{
			$pub = array_shift ( $this->pubsubNext );
			if ( 'publish' == $pub['call'] )
			{
				if ( $this->debug ) $this->log ( "attempting to publish to node '" . $pub['name'] . "'  contents '" . $pub['contents'] . "'");
				$this->pubsubPublish ( $pub[$to], 'set', $pub['name'], $pub['contents'] );
			}
			if ( 'create' == $pub['call'] )
			{
				if ( $this->debug ) $this->log ( "attempting to create node '" . $pub['name'] . "' ");
				$this->pubsubCreateNode ( $pub[$to], 'set', $pub['name'] );
			}
		}
	}

	// do basic setup to get the connection logged in and going
	private function initializeQueue ( )
	{
		$this->loggedIn = false;
		$this->streamTagBegin = '<'."?xml version='1.0'?"."><stream:stream to='" . $this->server . "' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
		$this->streamTagEnd = '</stream:stream>';
		$this->sendQueue[] = $this->streamTagBegin;
		$this->recvHandlers['stream:features'] = 'handleFeatures' ;
		$this->recvHandlers['features'] = 'handleFeatures' ;
		$this->recvHandlers['proceed'] = 'enableTLS' ;
	}

	// send data out the socket
	private function send ( $data )
	{
		$len = strlen ( $data );
		if ( $this->debug ) $this->log ( "SEND: $data");
		if ( false !== $this->connection )
		{
			if ( fwrite ( $this->connection, $data, $len) === $len )
				return true;
			else
				return false;
		}
		return false;
	}

	// receive any data waiting on the socket
	private function recv ()
	{
		if ( false !== $this->connection )
		{
			$data = '';
			$data = fgets ( $this->connection, 4096 );
			if ( 4094 < strlen ( $data ) )
			{
				$count = 0;
				while ( 0 != strlen ( $moredata = fgets ( $this->connection, 1024 ) ) && 20 < $count++ )
				{
					$data .= $moredata;
					usleep ( 10 );
				}
			}
			if ( 0 < strlen ( $data ) )
			{
				$data = preg_replace ( '/^<\?xml version=\'1.0\'\?'.'>/', '', $data );
				$this->stream .= $data;
				if ( $this->debug ) $this->log ( "RECV: $data" );
				return $data;
			}
			else
				return false;
		}
		return false;
	}

	private function go ()
	{
		$this->recvQueue = implode ( '', $this->sendQueue );
		$count = 0;
		$this->moredata = false;
		while ( false !== $this->connection )
		{
			if ( 0 < count ( $this->sendQueue ) )
			{
				$count = 0;
				while ( $data = array_shift ( $this->sendQueue ) )
					$this->send ( $data );
			}
			$data = $this->recv ( );
			xml_parse ( $this->xmlparser, $data, false );
			while ( $rnode = array_shift ( $this->recvTags ) )
			{
				$rname = strtolower ( $rnode->localName );
				if ( $this->debug ) $this->log ( " processing $rname ");
				if ( isset ( $this->recvHandlers[$rname] ) ) //&& is_callable ( $this->recvHandlers[$r->name] ) )
				{
					if ( method_exists ( $this, $this->recvHandlers[$rname] ) )
						call_user_func_array ( array ( $this, $this->recvHandlers[$rname] ),  array ( &$rnode ) );
					else
						call_user_func_array ( $this->recvHandlers[$rname], array ( &$rnode ) );
				}
			}
			$count++;
			if ( $count > 20 )
			{
				if ( $this->idle === true )
				{
					$count = 0;
					usleep ( 200 );
				}
				else
				{
					if ( $this->ready == true && count ( $this->handleCommand ) <= count ( $this->command ) )
					{
						$count = 0;
						return ;
					}
				}
			}
			else
				usleep ( 20 );
		}
	}


	// xml parser start element
	private function startElement ( $parser, $name, $attrs )
	{
		$this->depth++;
		$namespace = '';

		if ( 'STREAM:STREAM' == $name )
			$this->processDepth++;
		foreach ( $attrs as $k => $v )
			if ( preg_match ( '/^xmlns:?(.*)/i', $k, $matches ) )
			{
				if ( strlen ( $matches[1] ) > 0 && ! isset ( $this->namespaces [ $matches[1] ] ) )
				{
					$this->xquery->registerNamespace ( $matches[1], $v );
					$this->namespaces [ $matches[1] ] = $v;
					$namespace = $v;
					if ( $this->debug ) $this->log ( " adding namespace $k => $v ");
				}
			}
		if ( $namespace != '' )
			$node = $this->doc->createElementNS ( $namespace, $name );
		else
			$node = $this->doc->createElement ( $name );
		foreach ( $attrs as $k => $v )
			$node->setAttribute ( strtolower ( $k ), $v );
		$this->currentXMLNode = $this->currentXMLNode->appendChild ( $node );
	}

	// xml parser start element
	private function endElement ( $parser, $name )
	{
		$this->depth--;
		//if ( $this->debug ) $this->log ( "depth: " . $this->depth . " processDepth: " . $this->processDepth . " ");
		if ( $this->depth == $this->processDepth || 'STREAM:STREAM' == $name || 'STREAM:FEATURES' == $name || 'PROCEED' == $name )
		{
			if ( $this->debug ) $this->log ( " adding $name to tags to process ");
			array_push ( $this->recvTags, $this->currentXMLNode ); // replace with tag
		}
		$this->currentXMLNode = $this->currentXMLNode->parentNode;
	}

	// xml parser start element
	private function parseData ( $parser, $text )
	{
		$this->currentXMLNode->appendChild ( $this->doc->createTextNode ( $text ) );
	}

	// xml parser start element
	private function setupXmlParser ( )
	{
		$this->depth = 0;
		$this->xmlparser = xml_parser_create ( );
		xml_set_object ( $this->xmlparser, $this );
		xml_set_element_handler ( $this->xmlparser, 'startElement', 'endElement' );
		xml_set_character_data_handler ( $this->xmlparser, 'parseData' );
		$this->doc = new DOMDocument ();
		$this->xquery = new DOMXpath ( $this->doc );
		$this->xquery->registerNamespace ( 'stream', 'http://etherx.jabber.org/streams' );
		$this->currentXMLNode = $this->doc->appendChild ( $this->doc->createElement ( 'start' ) );
	}

	// xml XPath query
	private function query ( $expression, &$node = '' )
	{
		if ( '' == $node )
			return $this->xquery->query	( $expression );
		else
			return $this->xquery->query	( $expression , $node );
	}


	// open xmpp connection, will accept jid and password
	public function open ( $jid = null, $password = null)
	{
		if ( null != $jid )
			$this->jid = $jid;
		if ( null != $password )
			$this->password = $password;
		$this->ready = false;
		if ( false !== $this->connect () )
		{
			sleep(2);
			$this->go ();
		}
		else
			return false;
		return true;
	}

	public function close ()
	{
		if ( false !== $this->connection )
		{
			$this->send ( '</stream:stream>');
			fclose ( $this->connection );
			$this->connection = false;
		}
	}

	// add a send or recv handler, direction = [ send | recv ], command = command to handle, handler = function ref
	public function addHandler ( $direction, $command, $handler )
	{
		if ( 'send' == $direction )
			$this->sendHandler[$command] = $handler;
		if ( 'recv' == $direction )
			$this->recvHandler[$command] = $handler;
	}

	// handle logging
	private function log ( $message )
	{
		error_log ( 'XMPP: ' . $message );
		//echo  'XMPP: ' . $message . "\n";
	}
}


/**
	* * Log the action
	* * @param string $action_type INSERT / UPDATE or DELETE
	* * @param string $uid The UID of the modified item
	* * @param integer $user_no The user owning the containing collection.
	* * @param integer $collection_id The ID of the containing collection.
	* * @param string $dav_name The DAV path of the item, relative to the DAViCal base path
	* */
function log_caldav_action( $action_type, $uid, $user_no, $collection_id, $dav_name )
{
  global $c;
	$t = new xmpp();
	$t->tls =  'none';
	$t->idle =  false;
	if ( 1 == $c->dbg["ALL"] || 1 == $c->dbg["push"] )
		$t->debug = true ;
	else
		$t->debug = false ;
	// for now use a flat node tree layout
	$t->pubsubLayout = 'flat';
	// get the principal_id for this collection, that's what the client will be looking for
	$qry = new AwlQuery ('SELECT principal_id FROM principal JOIN collection USING (user_no) WHERE collection_id= :collection_id',
                           array( ':collection_id' => $collection_id ) );
	$qry->Exec('pubsub');
	$row = $qry->Fetch();

	$t->open ( $c->notifications_server['jid'], $c->notifications_server['password'] );
	if ( isset ( $c->notifications_server['debug_jid'] ) )
		$t->sendMessage ( $c->notifications_server['debug_jid'], "ACTION: $action_type\nUSER: $user_no\nDAV NAME: $dav_name\nPRINCIPAL ID: " . $row->principal_id );
	$t->pubsubCreate ( '', 'set', '/davical-' . $row->principal_id, '<x xmlns="jabber:x:data" type="submit"><field var="FORM_TYPE" type="hidden"><value>http://jabber.org/protocol/pubsub#node_config</value></field><field var="pubsub#access_model"><value>open</value></field><field var=\'pubsub#type\'>plist-apple<value></value></field></x>' );
	$t->pubsubPublish ( '', 'set', '/davical-' . $row->principal_id , '<item xmlns="plist-apple" id="' . $uid . ' " ><plistfrag xmlns="plist-apple"><key>davical</key><string>' . $uid . '</string></plistfrag></item>', $uid );
	$t->close();
}

