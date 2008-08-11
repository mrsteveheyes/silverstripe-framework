<?
/**
 * Basic SOAP Server to access and modify DataObject instances.
 * You can enable SOAP access on a DataObject by setting {@link DataObject::$api_access} to true.
 * 
 * Usage - Getting a record:
 * <code>
 * $c = new SoapClient('http://mysite.com/soap/v1/wsdl');
 * echo $c->getXML("MyClassName", 99); // gets record #99 as xml
 * </code>
 *
 * Usage - Updating a record:
 * <code>
 * $c = new SoapClient('http://mysite.com/soap/v1/wsdl');
 * $data = array('MyProperty' => 'MyUpdatedValue');
 * echo $c->putXML("MyClassName", 99, null, $data);
 * </code>
 * 
 * Usage - Creating a record:
 * <code>
 * $c = new SoapClient('http://mysite.com/soap/v1/wsdl');
 * $data = array('MyProperty' => 'MyValue');
 * echo $c->putXML("MyClassName", null, null, $data);
 * </code>
 *
 * Usage - Creating a record:
 * <code>
 * $c = new SoapClient('http://mysite.com/soap/v1/wsdl');
 * echo $c->deleteXML("MyClassName");
 * </code>
 *
 * @todo Test relation methods
 * 
 * @package sapphire
 * @subpackage api
 */
class SOAPModelAccess extends SapphireSoapServer {
	
	public static $methods = array(
		'getXML' => array(
			'class' => 'string',
			'id' => 'int',
			'relation' => 'string',
			'_returns' => 'string',
		),
		'getJSON' => array(
			'class' => 'string',
			'id' => 'int',
			'relation' => 'string',
			'_returns' => 'string',
		),
		'putXML' => array(
			'class' => 'string',
			'id' => 'int',
			'relation' => 'string',
			'data' => 'string',
			'username' => 'string',
			'password' => 'string',
			'_returns' => 'boolean',
		),
		'putJSON' => array(
			'class' => 'string',
			'id' => 'int',
			'relation' => 'string',
			'_returns' => 'boolean',
		),
	);
	
	function Link() {
		return "soap/v1/";
	}
	
	/**
	 * Used to emulate RESTful GET requests with XML data.
	 * 
	 * @param string $class
	 * @param Number $id
	 * @param string $relation Relation name
	 * @return string
	 */
	function getXML($class, $id, $relation = false, $username = null, $password = null) {
		$this->authenticate($username, $password);
		
		$response = Director::test(
			$this->buildRestfulURL($class, $id, $relation, 'xml'),
			null,
			null,
			'GET'
		);

		return ($response->isError()) ? $this->getErrorMessage($response) : $response->getBody();
	}
	
	/**
	 * Used to emulate RESTful GET requests with JSON data.
	 * 
	 * @param string $class
	 * @param Number $id
	 * @param string $relation Relation name
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	function getJSON($class, $id, $relation = false, $username = null, $password = null) {
		$this->authenticate($username, $password);
		
		$response = Director::test(
			$this->buildRestfulURL($class, $id, $relation, 'json'),
			null,
			null,
			'GET'
		);
		
		return ($response->isError()) ? $this->getErrorMessage($response) : $response->getBody();
	}
	
	/**
	 * Used to emulate RESTful POST and PUT requests with XML data.
	 * 
	 * @param string $class
	 * @param Number $id
	 * @param string $relation Relation name
	 * @param array $data 
 	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	function putXML($class, $id = false, $relation = false, $data, $username = null, $password = null) {
		$this->authenticate($username, $password);

		$response = Director::test(
			$this->buildRestfulURL($class, $id, $relation, 'xml'),
			$data,
			null,
			($id) ? 'PUT' : 'POST'
		);

		return ($response->isError()) ? $this->getErrorMessage($response) : $response->getBody();
	}
	
	/**
	 * Used to emulate RESTful POST and PUT requests with JSON data.
	 * 
	 * @param string $class
	 * @param Number $id
	 * @param string $relation Relation name
	 * @param array $data
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	function putJSON($class = false, $id = false, $relation = false, $data, $username = null, $password = null) {
		$this->authenticate($username, $password);
		
		$response = Director::test(
			$this->buildRestfulURL($class, $id, $relation, 'json'),
			$data,
			null,
			($id) ? 'PUT' : 'POST'
		);
		
		return ($response->isError()) ? $this->getErrorMessage($response) : $response->getBody();
	}
	
	/**
	 * Used to emulate RESTful DELETE requests.
	 *
	 * @param string $class
	 * @param Number $id
	 * @param string $relation Relation name
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	function deleteXML($class, $id, $relation = false, $username = null, $password = null) {
		$this->authenticate($username, $password);
		
		$response = Director::test(
			$this->buildRestfulURL($class, $id, $relation, 'xml'),
			null,
			null,
			'DELETE'
		);
		
		return ($response->isError()) ? $this->getErrorMessage($response) : $response->getBody();
	}
	
	/**
	 * Used to emulate RESTful DELETE requests.
	 *
	 * @param string $class
	 * @param Number $id
	 * @param string $relation Relation name
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	function deleteJSON($class, $id, $relation = false, $username = null, $password = null) {
		$this->authenticate($username, $password);
		
		$response = Director::test(
			$this->buildRestfulURL($class, $id, $relation, 'json'),
			null,
			null,
			'DELETE'
		);
		
		return ($response->isError()) ? $this->getErrorMessage($response) : $response->getBody();
	}
	
	/**
	 * Faking an HTTP Basicauth login in the PHP environment
	 * that RestfulServer can pick up. 
	 *
	 * @param string $username Username
	 * @param string $password Plaintext password
	 */
	protected function authenticate($username, $password) {
		$_SERVER['PHP_AUTH_USER'] = $username;
		$_SERVER['PHP_AUTH_PW'] = $password;
	}
	
	/**
	 * @param string $class
	 * @param Number $id
	 * @param string $relation
	 * @param string $extension
	 * @return string
	 */
	protected function buildRestfulURL($class, $id, $relation, $extension) {
	   $url = "api/v1/{$class}";
	   if($id) $url .= "/{$id}";
	   if($relation) $url .= "/{$relation}";
	   if($extension) $url .= "/.{$extension}";
	   return $url;
	}
	
	/**
	 * @param HTTPResponse $response
	 * @return string XML string containing the HTTP error message
	 */
	protected function getErrorMessage($response) {
		return "<error type=\"authentication\" code=\"" . $response->getStatusCode() . "\">" . $response->getStatusDescription() . "</error>";
	}
}

?>