<?php
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__) . '/logentries.php';

function logger() {
	global $log;
	return $log;
}

class Controll {
	
	/**
	 * Convention API key for the ConTroll API
	 * @var string
	 */
	private $key;
	
	/**
	 * Convention API secret fo the ConTroll API
	 * @var string
	 */
	private $secret;

	/**
	 * Cached user data
	 * @var stdClass
	 */
	private $user_data = null;
	
	public function __construct($api_key, $secret) {
		$this->key = $api_key;
		$this->secret = $secret;
	}
	
	/**
	 * Start authentication with ConTroll and return the user to the specified URL.
	 * This method never returns
	 * @param string $return_url
	 */
	public function getAuthURL($return_url) {
		return 'http://api.con-troll.org/auth/select?redirect-url=' . urlencode($return_url);
	}
	
	public function getSessionToken() {
		return $_SESSION['controll-user-token'] ?: 'no-user-token'; // never return something falsy, so API won't
		// try to issue convention auth when we want a user auth
	}
	
	/**
	 * Check if the current request being processed is the result of a successful authentication,
	 * and if so - load user details into the session.
	 * Call this as early in the process of processing an incoming request as possible.
	 */
	public function checkAuthentication() {
		$token = @$_REQUEST['token'] ?: @$_REQUEST['api_token'];
		if (!$token)
			return;
		$token = $_GET['token'];
		$user = $this->getUserData($token);
		$_SESSION['controll-user-token'] = $token;
		$_SESSION['controll-user-data'] = $user;
	}

	/**
	 * Retrieve user data for an authentication token
	 * @param string $token
	 */
	public function getUserData($token) {
		return $this->user_data = $this->apiCall('auth/id', $token);
	}
	
	/**
	 * Retrieve the user's email, for a logged in user
	 * This will call ConTroll to verify the session token
	 * @return string|boolean The user's email, or false if there is no logged in user
	 */
	public function getUserEmail() {
		if ($this->user_data)
			return $this->user_data->email;
		if (@$_SESSION['controll-user-token'] && // we have a possibly stale session, try to validate
			$this->getUserData(@$_SESSION['controll-user-token']))
			return $this->user_data->email;
		return false;
	}
	
	/**
	 * Retrieve the user's name, for a logged in user
	 * This will call ConTroll to verify the session token
	 * @return string|boolean The user's name, or false if there is no logged in user
	 */
	public function getUserName() {
		if ($this->user_data)
			return $this->user_data->name;
		if (@$_SESSION['controll-user-token'] && // we have a possibly stale session, try to validate
			$this->getUserData(@$_SESSION['controll-user-token']))
			return $this->user_data->name;
		return false;
	}
	
	public function logout() {
		return $this->apiCall('/auth/logout', $this->getSessionToken());
	}
	
	/**
	 * return all public records for the con
	 */
	public function getPublicUserRecords($identifier, $get_all_versions = FALSE) {
		$data = $this->apiCall("entities/records/{$identifier}" . ($get_all_versions ? '?all=1' : ''));
		if ($data === false) return false;
		if (!is_array($data))
			return false;
		$records = [];
		foreach ($data as $record) {
			if ($record->content_type != 'application/json')
				continue;
			$records[] = [
					'data' => json_decode($record->data, true),
					'user' => (array)$record->user
					];
		}
		return $records;
	}
	
	/**
	 * Return a publicly readable record for this user
	 */
	public function getPublicUserRecord($email, $identifier) {
		$data = $this->apiCall('entities/records/' . $identifier . '?user=' . urlencode($email));
		if ($data === false) return false;
		if (is_array($data))
			$data = $data[0];
		if (!@$data->data)
			return false;
		if (@$data->data->data)
			$data = $data->data; // TOOD: I really should rewrite the user record API and this client
		if (@$data->content_type != 'application/json')
			return false;
		logger()->Debug("decoding " . $data->data);
		return json_decode($data->data, true); // yes, silly,isn't it
	}
	
	public function storeUserRecord($descriptor, $data, $acl = 'private') {
		$this->apiCall('entities/records', $this->getSessionToken(), [
			"descriptor" => $descriptor,
			"content_type" => 'application/json',
			"acl" => $acl,
			"data" => json_encode($data)]);
	}
	
	/**
	 * Trigger a password reset email for the user
	 * @param strin $email Email address of a user that needs a password reset
	 * @param string $url URL to send the reset token to
	 */
	public function passwordReset($email, $url) {
		$res = $this->apiCall('auth/passwordreset', null, [
				'email' => $email,
				'redirect-url' => $url,
		]);
		logger()->Debug("Password reset call said: " . print_r($res, true));
	}
	
	public function setPassword($token, $password) {
		$res = $this->apiCall('auth/passwordchange', $token, [
				'password' => $password,
		]);
		logger()->Debug("Changing password with token $token, ConTroll said: " . print_r($res,true));
		return is_object($res) and $res->status;
	}
	
	public function registerUser($email, $name, $password) {
		$res = $this->apiCall('auth/register', null, [
				'email' => $email,
				'password-register' => $password,
				'name' => $name,
		]);
		if (!is_object($res) or $res->status === false)
			logger()->Debug("User registration of $name <$email> failed: " . print_r($res,true));
		return is_object($res) and $res->status;
	}
	
	public function getKey() {
		return $this->key;
	}
		
	/**
	 * Retrieves the convention settings
	 * @return StdClass
	 */
	public function getSettings() {
		return $this->apiCall('entities/conventions/self')->settings;
	}
	
	/**
	 * Checks whether the current convention users daily passes for registration, or allows buying tickets
	 * @return boolean true if the convention uses daily passes
	 */
	public function usesPasses() {
		return $this->getSettings()->{"registration-type"} == "passes";
	}

	/**
	 * Get a Timeslots entity handler
	 */
	public function tags() : ConTrollTags {
		return new ConTrollTags($this);
	}

	/**
	 * Get a Timeslots entity handler
	 */
	public function timeslots() : ConTrollTimeslots {
		return new ConTrollTimeslots($this);
	}

	/**
	 * Get a Tickets entity handler
	 */
	public function locations() : ConTrollLocations {
		return new ConTrollLocations($this);
	}

	/**
	 * Get a Tickets entity handler
	 */
	public function tickets() : ConTrollTickets {
		return new ConTrollTickets($this);
	}
	
	/**
	 * Get a passes entity handler
	 */
	public function passes() : ConTrollPasses {
		return new ConTrollPasses($this);
	}

	/**
	 * Get a Coupons entity handler
	 */
	public function coupons() : ConTrollCoupons {
		return new ConTrollCoupons($this);
	}
	
	/**
	 * Get a Merchandise SKU entity handler
	 */
	public function merchandise() : ConTrollMerchandise {
		return new ConTrollMerchandise($this);
	}

	/**
	 * Get a Purchases entity handler
	 */
	public function purchases() : ConTrollPurchases {
		return new ConTrollPurchases($this);
	}
	
	public function apiCall($api, $user_token = null, $data = null, $method = null) {
		$res = @file_get_contents("http://api.con-troll.org/" . $api, false,
						$this->getAuthorizedStreamContext($user_token, $data, $method));
		logger()->Debug("Calling http://api.con-troll.org/" . $api);
		if (strstr($_SERVER['QUERY_STRING'], 'debug-response'))
			logger()->Debug("Controll API response to 'http://api.con-troll.org/$api': " . print_r($res, true));
		if ($res === false)
			return false; // 404?
		return json_decode($res);
	}
	
	private function getAuthorizedStreamContext($token, $data = null, $method = null) {
		$headers = ['Convention' => $this->key ];
		if ($data) {
			$http = [
					'method'  => 'POST',
					'content' => json_encode($data),
					'ignore_errors' => true,
			];
			$headers['Content-Type'] = 'application/json';
		} else {
			$http = [
					"method" => "GET" ,
					'ignore_errors' => true,
			];
		}
		if ($method)
			$http['method'] = $method;
		if ($token == 'public') {
			// caller specifically asked no authorization
		} elseif ($token) {
			$headers['Authorization'] = $token;
		} elseif ($this->secret) {
			$headers['Authorization'] = $this->generateConventionAuth();
		}
		if (strstr($_SERVER['QUERY_STRING'], 'debug-response'))
			logger()->Debug("Sending API authorization: " . $headers['Authorization'] . " with data " . print_r($http, true));
		$http['header'] = '';
		foreach ($headers as $field => $value)
			$http['header'] .= "$field: $value\r\n";
		return stream_context_create([ 'http' => $http ]);
	}
	
	private function generateConventionAuth() {
		$key = time() . ':' . base64_encode(mcrypt_create_iv(10)) /* salt */;
		$signature = sha1($key . $this->secret);
		return "convention {$key}:{$signature}";
	}
	
}

class ConTrollTags {
	
	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}

	public function catalog() {
		logger()->Debug("Loading tag list");
		return $this->api->apiCall('entities/tagtypes');
	}

}

class ConTrollTimeslots {
	
	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}

	public function catalog($event_status = null) {
		return $this->api->apiCall('entities/timeslots'.
				($event_status ? "?by_event_status=$event_status" : ""));
	}

	public function publicCatalog($filters = []) {
		$with_filters = '';
		if (!empty($filters)) {
			$filterlist = [];
			foreach ($filters as $key => $value)
				$filterlist[] = urlencode('by_' . $key) . '=' . urlencode($value);
			$with_filters = '?' . join('&', $filterlist);
		}
		return $this->api->apiCall('entities/timeslots' . $with_filters,'public');
	}
	
	public function myHosting() {
		$res = $this->api->apiCall('entities/timeslots?by_host=' . $this->api->getUserEmail(),'public');
		if (!is_array($res) && $res->status === false) {
			logger()->Err("Error getting hostings list for user ".$this->api->getUserEmail());
			return false;
		}
		return $res;
	}
	
	public function get($id) {
		return $this->api->apiCall("entities/timeslots/$id");
	}
}

class ConTrollLocations {
	
	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}
	
	public function catalog() {
		return $this->api->apiCall('entities/locations', $this->api->getSessionToken());
	}
	

	public function get($id) {
		return $this->api->apiCall("entities/locations/$id");
	}
}

class ConTrollTickets {

	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}
	
	public function catalog() {
		return $this->api->apiCall('entities/tickets', $this->api->getSessionToken());
	}

	public function create($timeslot_id) {
		return $this->api->apiCall('entities/tickets', $this->api->getSessionToken(), [
				'timeslot' => (int)$timeslot_id,
				'amount' => 1
		]);
	}
	
	public function update($timeslot_id, $amount) {
		return $this->api->apiCall('entities/tickets/'.$timeslot_id, $this->api->getSessionToken(), [
				'amount' => $amount
		], 'PUT');
	}
	
	public function cancel($ticket_id) {
		return $this->api->apiCall('entities/tickets/'.$ticket_id, $this->api->getSessionToken(), null, 'DELETE');
	}
}

class ConTrollPasses {

	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}

	/**
	 * List available convention passes
	 * @return array
	 */
	public function catalog() {
		return $this->api->apiCall('entities/passes');
	}

	/**
	 * List User's purchased passes
	 * @return array
	 */
	public function user_catalog() {
		return $this->api->apiCall('entities/userpasses', $this->api->getSessionToken());
	}
	
	/**
	 * Buy a user pass
	 * @param int $passid ConTroll pass ID
	 * @param string $name Peron's name to register this pass for
	 * @return StdClass pass bought
	 */
	public function buy($passid, $name) {
		return $this->api->apiCall('entities/userpasses', $this->api->getSessionToken(), [
			'pass' => $passid,
			'name' => $name,
		]);
	}
	
	/**
	 * Cancel a user's pass purchase before it is authorized
	 * @param int $userpassid ConTroll user pass ID
	 * @return boolean whether deletion was successful
	 */
	public function delete($userpassid) {
		return $this->api->apiCall('entities/userpasses/'.$userpassid, $this->api->getSessionToken(), null, 'DELETE');
	}
	
	/**
	 * Return the list of user passes, with an additional boolean field "available" specifying if that user pass
	 * can register for that timeslot or not
	 * @param int $timeslotid ConTroll timeslot ID
	 * @return array
	 */
	public function timeslot_availability($timeslot_id) {
		return $this->api->apiCall('entities/userpasses?for_timeslot='.$timeslot_id, $this->api->getSessionToken());
	}
	
	/**
	 * Register a new ticket for the specified timeslot using the specified daily pass
	 * @param int $passid ConTroll pass ID
	 * @param int $timeslotid ConTroll timeslot ID
	 */
	public function register($passid, $timeslot_id) {
		return $this->api->apiCall('entities/tickets', $this->api->getSessionToken(), [
				'timeslot' => (int)$timeslot_id,
				'user_passes' => $passid
		]);
	}
}

class ConTrollCoupons {

	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}
	
	public function catalog() {
		return $this->api->apiCall('entities/coupons?self=1', $this->api->getSessionToken());
	}
}

class ConTrollMerchandise {

	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;

	public function __construct(Controll $api) {
		$this->api = $api;
	}

	public function catalog() {
		return $this->api->apiCall('entities/merchandiseskus', $this->api->getSessionToken());
	}
}

class ConTrollPurchases {

	/**
	 * ConTroll API endpoint handler
	 * @var Controll
	 */
	private $api;
	
	public function __construct(Controll $api) {
		$this->api = $api;
	}
	
	public function catalog() {
		return $this->api->apiCall('entities/purchases', $this->api->getSessionToken());
	}

	public function create($sku, $amount = 1) {
		return $this->api->apiCall('entities/purchases', $this->api->getSessionToken(), [
				'sku' => $sku,
				'amount' => $amount
		]);
	}
	
	public function update($sku, $amount) {
		$res = $this->api->apiCall('entities/purchases/'. str_replace('+','%20',urlencode($sku)) , $this->api->getSessionToken(), [
				'amount' => $amount
		], 'PUT');
		if (!$res->status)
			logger()->Error("Error in purchase update: " . print_r($res, true));
		return $res;
	}
}

if (!function_exists('mcrypt_create_iv')) {
	function mcrypt_create_iv($length, $type = null) {
		return openssl_random_pseudo_bytes($length);
	}
}

if (!session_id()) // init the PHP session if we are loaded without a session context
	session_start();
