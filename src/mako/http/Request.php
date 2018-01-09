<?php

/**
 * @copyright Frederic G. Østby
 * @license   http://www.makoframework.com/license
 */

namespace mako\http;

use mako\http\request\Cookies;
use mako\http\request\Files;
use mako\http\request\Headers;
use mako\http\request\Parameters;
use mako\http\request\Server;
use mako\http\routing\Route;
use mako\security\Signer;
use mako\utility\Arr;
use mako\utility\ip\IP;

/**
 * Executes requets.
 *
 * @author Frederic G. Østby
 */
class Request
{
	/**
	 * Script name.
	 *
	 * @var string
	 */
	protected $scriptName;

	/**
	 * Get data
	 *
	 * @var \mako\http\request\Parameters
	 */
	public $query;

	/**
	 * Post data
	 *
	 * @var \mako\http\request\Parameters
	 */
	public $post;

	/**
	 * Cookie data.
	 *
	 * @var \mako\http\request\Cookies
	 */
	public $cookies;

	/**
	 * File data.
	 *
	 * @var \mako\http\request\Files
	 */
	public $files;

	/**
	 * Server info.
	 *
	 * @var \mako\http\request\Server
	 */
	public $server;

	/**
	 * Request headers.
	 *
	 * @var \mako\http\request\Headers
	 */
	public $headers;

	/**
	 * Raw request body.
	 *
	 * @var string
	 */
	protected $rawBody;

	/**
	 * Parsed request body.
	 *
	 * @var \mako\http\request\Parameters
	 */
	protected $parsedBody;

	/**
	 * Array of trusted proxy IP addresses.
	 *
	 * @var array
	 */
	protected $trustedProxies;

	/**
	 * Ip address of the client that made the request.
	 *
	 * @var string
	 */
	protected $ip;

	/**
	 * Base URL of the request.
	 *
	 * @var string
	 */
	protected $baseURL;

	/**
	 * Holds the request path.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Request language.
	 *
	 * @var array
	 */
	protected $language;

	/**
	 * Request language prefix.
	 *
	 * @var string
	 */
	protected $languagePrefix;

	/**
	 * Which request method was used?
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * The actual request method that was used.
	 *
	 * @var string
	 */
	protected $realMethod;

	/**
	 * The route that matched the request.
	 *
	 * @var \mako\http\routing\Route
	 */
	protected $route;

	/**
	 * Request attribuntes.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Constructor.
	 *
	 * @param array                      $request Request data and options
	 * @param \mako\security\Signer|null $signer  Signer instance used to validate signed cookies
	 */
	public function __construct(array $request = [], Signer $signer = null)
	{
		// Collect request data

		$this->query   = new Parameters($request['get'] ?? $_GET);
		$this->post    = new Parameters($request['post'] ?? $_POST);
		$this->cookies = new Cookies($request['cookies'] ?? $_COOKIE, $signer);
		$this->files   = new Files($request['files'] ?? $_FILES);
		$this->server  = new Server($request['server'] ?? $_SERVER);
		$this->headers = new Headers($this->server->getHeaders());
		$this->rawBody = $request['body'] ?? null;

		// Get the script name

		$this->scriptName = basename($this->server->get('SCRIPT_FILENAME'));

		// Set the request path and method

		$languages = $request['languages'] ?? [];

		$this->path = isset($request['path']) ? $this->stripLocaleSegment($languages, $request['path']) : $this->determinePath($languages);

		$this->method = $request['method'] ?? $this->determineMethod();
	}

	/**
	 * Strips the locale segment from the path.
	 *
	 * @param  array  $languages Locale segments
	 * @param  string $path      Path
	 * @return string
	 */
	protected function stripLocaleSegment(array $languages, string $path): string
	{
		foreach($languages as $key => $language)
		{
			if($path === '/' . $key || strpos($path, '/' . $key . '/') === 0)
			{
				$this->language = $language;

				$this->languagePrefix = $key;

				$path = '/' . ltrim(mb_substr($path, (mb_strlen($key) + 1)), '/');

				break;
			}
		}

		return $path;
	}

	/**
	 * Determines the request path.
	 *
	 * @param  array  $languages Locale segments
	 * @return string
	 */
	protected function determinePath(array $languages): string
	{
		$path = '/';

		$server = $this->server->all();

		if(isset($server['PATH_INFO']))
		{
			$path = $server['PATH_INFO'];
		}
		elseif(isset($server['REQUEST_URI']))
		{
			if($path = parse_url($server['REQUEST_URI'], PHP_URL_PATH))
			{
				// Remove base path from the request path

				$basePath = pathinfo($server['SCRIPT_NAME'], PATHINFO_DIRNAME);

				if($basePath !== '/' && stripos($path, $basePath) === 0)
				{
					$path = mb_substr($path, mb_strlen($basePath));
				}

				// Remove "/index.php" from the path

				if(stripos($path, '/' . $this->scriptName) === 0)
				{
					$path = mb_substr($path, (strlen($this->scriptName) + 1));
				}

				$path = rawurldecode($path);
			}
		}

		return $this->stripLocaleSegment($languages, $path);
	}

	/**
	 * Determines the request method.
	 *
	 * @return string
	 */
	protected function determineMethod(): string
	{
		$this->realMethod = $method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));

		if($method === 'POST')
		{
			return strtoupper($this->post->get('REQUEST_METHOD_OVERRIDE', $this->server->get('HTTP_X_HTTP_METHOD_OVERRIDE', 'POST')));
		}

		return $method;
	}

	/**
	 * Returns the base name of the script that handled the request.
	 *
	 * @return string
	 */
	public function scriptName(): string
	{
		return $this->scriptName;
	}

	/**
	 * Set the route that matched the request.
	 *
	 * @param \mako\http\routing\Route $route Route
	 */
	public function setRoute(Route $route)
	{
		$this->route = $route;
	}

	/**
	 * Returns the route that matched the request.
	 *
	 * @return \mako\http\routing\Route|null
	 */
	public function getRoute()
	{
		return $this->route;
	}

	/**
	 * Sets a request attribute.
	 *
	 * @param string $name  Attribute name
	 * @param mixed  $value Attribute value
	 */
	public function setAttribute(string $name, $value)
	{
		Arr::set($this->attributes, $name, $value);
	}

	/**
	 * Gets a request attribute.
	 *
	 * @param  string $name    Attribute name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function getAttribute(string $name, $default = null)
	{
		return Arr::get($this->attributes, $name, $default);
	}

	/**
	 * Returns the raw request body.
	 *
	 * @return string
	 */
	public function getRawBody(): string
	{
		if($this->rawBody === null)
		{
			$this->rawBody = file_get_contents('php://input');
		}

		return $this->rawBody;
	}

	/**
	 * Returns the raw request body as a stream.
	 *
	 * @return resource
	 */
	public function getRawBodyAsStream()
	{
		return fopen('php://input', 'r');
	}

	/**
	 * Parses the request body and returns the chosen value.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	protected function getParsed(string $key = null, $default = null)
	{
		return ($key === null) ? $this->getBody()->all() : $this->getBody()->get($key, $default);
	}

	/**
	 * Returns the query string.
	 *
	 * @return \mako\http\request\Parameters
	 */
	public function getQuery(): Parameters
	{
		return $this->query;
	}

	/**
	 * Returns the post data.
	 *
	 * @return \mako\http\request\Parameters
	 */
	public function getPost(): Parameters
	{
		return $this->post;
	}

	/**
	 * Returns the cookies.
	 *
	 * @return \mako\http\request\Cookies
	 */
	public function getCookies(): Cookies
	{
		return $this->cookies;
	}

	/**
	 * Returns the files.
	 *
	 * @return \mako\http\request\Files
	 */
	public function getFiles(): Files
	{
		return $this->files;
	}

	/**
	 * Returns the files.
	 *
	 * @return \mako\http\request\Server
	 */
	public function getServer(): Server
	{
		return $this->server;
	}

	/**
	 * Returns the files.
	 *
	 * @return \mako\http\request\Headers
	 */
	public function getHeaders(): Headers
	{
		return $this->headers;
	}

	/**
	 * Converts the request body into an associative array.
	 *
	 * @return array
	 */
	protected function parseBody(): array
	{
		$contentType = rtrim(strtok((string) $this->headers->get('content-type'), ';'));

		if($contentType === 'application/x-www-form-urlencoded')
		{
			$parsed = [];

			parse_str($this->getRawbody(), $parsed);

			return $parsed;
		}
		elseif($contentType === 'application/json' || $contentType === 'text/json')
		{
			return json_decode($this->getRawbody(), true);
		}

		return [];
	}

	/**
	 * Returns the parsed request body.
	 *
	 * @return \mako\http\request\Parameters
	 */
	public function getBody(): Parameters
	{
		if($this->parsedBody === null)
		{
			$this->parsedBody = new Parameters($this->parseBody());
		}

		return $this->parsedBody;
	}

	/**
	 * Returns the data of the current request method.
	 *
	 * @return \mako\http\request\Parameters
	 */
	public function getData(): Parameters
	{
		switch($this->realMethod)
		{
			case 'GET':
				return $this->getQuery();
			case 'POST':
				return $this->getPost();
			default:
				return $this->getBody();
		}
	}

	/**
	 * Fetch data from the GET parameters.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function get(string $key = null, $default = null)
	{
		return ($key === null) ? $this->query->all() : $this->query->get($key, $default);
	}

	/**
	 * Fetch data from the POST parameters.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function post(string $key = null, $default = null)
	{
		return ($key === null) ? $this->post->all() : $this->post->get($key, $default);
	}

	/**
	 * Fetch data from the PUT parameters.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function put(string $key = null, $default = null)
	{
		return $this->getParsed($key, $default);
	}

	/**
	 * Fetch data from the PATCH parameters.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function patch(string $key = null, $default = null)
	{
		return $this->getParsed($key, $default);
	}

	/**
	 * Fetch data from the DELETE parameters.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function delete(string $key = null, $default = null)
	{
		return $this->getParsed($key, $default);
	}

	/**
	 * Fetch unsigned cookie data.
	 *
	 * @deprecated
	 * @param  string $name    Cookie name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function cookie(string $name, $default = null)
	{
		return $this->cookies->get($name, $default);
	}

	/**
	 * Fetch signed cookie data.
	 *
	 * @deprecated
	 * @param  string $name    Cookie name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function signedCookie(string $name, $default = null)
	{
		return $this->cookies->getSigned($name, $default);
	}

	/**
	 * Fetch uploaded file.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function file(string $key = null, $default = null)
	{
		return ($key === null) ? $this->files->all() : $this->files->get($key, $default);
	}

	/**
	 * Fetch server info.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function server(string $key = null, $default = null)
	{
		return ($key === null) ? $this->server->all() : $this->server->get($key, $default);
	}

	/**
	 * Checks if the keys exist in the data of the current request method.
	 *
	 * @deprecated
	 * @param  string $key Array key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		$method = strtolower($this->realMethod);

		return Arr::has($this->$method(), $key);
	}

	/**
	 * Fetch data the current request method.
	 *
	 * @deprecated
	 * @param  string|null $key     Array key
	 * @param  mixed       $default Default value
	 * @return mixed
	 */
	public function data(string $key = null, $default = null)
	{
		$method = strtolower($this->realMethod);

		return $this->$method($key, $default);
	}

	/**
	 * Returns request data where keys not in the whitelist have been removed.
	 *
	 * @deprecated
	 * @param  array $keys     Keys to whitelist
	 * @param  array $defaults Default values
	 * @return array
	 */
	public function whitelisted(array $keys, array $defaults = []): array
	{
		return array_intersect_key($this->data(), array_flip($keys)) + $defaults;
	}

	/**
	 * Returns request data where keys in the blacklist have been removed.
	 *
	 * @deprecated
	 * @param  array $keys     Keys to whitelist
	 * @param  array $defaults Default values
	 * @return array
	 */
	public function blacklisted(array $keys, array $defaults = []): array
	{
		return array_diff_key($this->data(), array_flip($keys)) + $defaults;
	}

	/**
	 * Returns a request header.
	 *
	 * @deprecated
	 * @param  string $name    Header name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function header(string $name, $default = null)
	{
		return $this->headers->get($name, $default);
	}

	/**
	 * Returns an array of acceptable content types in descending order of preference.
	 *
	 * @deprecated
	 * @return array
	 */
	public function acceptableContentTypes(): array
	{
		return $this->headers->acceptableContentTypes();
	}

	/**
	 * Returns an array of acceptable content types in descending order of preference.
	 *
	 * @deprecated
	 * @return array
	 */
	public function acceptableLanguages(): array
	{
		return $this->headers->acceptableLanguages();
	}

	/**
	 * Returns an array of acceptable content types in descending order of preference.
	 *
	 * @deprecated
	 * @return array
	 */
	public function acceptableCharsets(): array
	{
		return $this->headers->acceptableCharsets();
	}

	/**
	 * Returns an array of acceptable content types in descending order of preference.
	 *
	 * @deprecated
	 * @return array
	 */
	public function acceptableEncodings(): array
	{
		return $this->headers->acceptableEncodings();
	}

	/**
	 * Set the trusted proxies.
	 *
	 * @param array $trustedProxies Array of trusted proxy IP addresses
	 */
	public function setTrustedProxies(array $trustedProxies)
	{
		$this->trustedProxies = $trustedProxies;
	}

	/**
	 * Returns the ip of the client that made the request.
	 *
	 * @return string
	 */
	public function ip(): string
	{
		if(empty($this->ip))
		{
			$ip = $this->server->get('REMOTE_ADDR');

			if(!empty($this->trustedProxies))
			{
				$ips = $this->server->get('HTTP_X_FORWARDED_FOR');

				if(!empty($ips))
				{
					$ips = array_map('trim', explode(',', $ips));

					foreach($ips as $key => $value)
					{
						foreach($this->trustedProxies as $trustedProxy)
						{
							if(IP::inRange($value, $trustedProxy))
							{
								unset($ips[$key]);

								break;
							}
						}
					}

					$ip = end($ips);
				}
			}

			$this->ip = (filter_var($ip, FILTER_VALIDATE_IP) !== false) ? $ip : '127.0.0.1';
		}

		return $this->ip;
	}

	/**
	 * Returns true if the request was made using Ajax and false if not.
	 *
	 * @return bool
	 */
	public function isAjax(): bool
	{
		return $this->server->get('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
	}

	/**
	 * Returns true if the request was made using HTTPS and false if not.
	 *
	 * @return bool
	 */
	public function isSecure(): bool
	{
		return filter_var($this->server->get('HTTPS', false), FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Returns true if the request method is considered safe and false if not.
	 *
	 * @return bool
	 */
	public function isSafe(): bool
	{
		return in_array($this->method, ['GET', 'HEAD']);
	}

	/**
	 * Is PHP running as a CGI program?
	 *
	 * @return bool
	 */
	public function isCGI(): bool
	{
		return strpos(PHP_SAPI, 'cgi') !== false;
	}

	/**
	 * Returns the base url of the request.
	 *
	 * @return string
	 */
	public function baseURL(): string
	{
		if(empty($this->baseURL))
		{
			// Get the protocol

			$protocol = $this->isSecure() ? 'https://' : 'http://';

			// Get the server name and port

			if(($host = $this->server->get('HTTP_HOST')) === null)
			{
				$host = $this->server->get('SERVER_NAME');

				$port = $this->server->get('SERVER_PORT');

				if($port !== null && $port != 80)
				{
					$host = $host . ':' . $port;
				}
			}

			// Get the base path

			$path = $this->server->get('SCRIPT_NAME');

			$path = str_replace(basename($path), '', $path);

			// Put them all together

			$this->baseURL = rtrim($protocol . $host . $path, '/');
		}

		return $this->baseURL;
	}

	/**
	 * Returns the request path.
	 *
	 * @return string
	 */
	public function path(): string
	{
		return $this->path;
	}

	/**
	 * Returns true if the resource was requested with a "clean" URL and false if not.
	 *
	 * @return bool
	 */
	public function isClean(): bool
	{
		return strpos($this->server->get('REQUEST_URI'), $this->server->get('SCRIPT_NAME')) !== 0;
	}

	/**
	 * Returns the request language.
	 *
	 * @return array|null
	 */
	public function language()
	{
		return $this->language;
	}

	/**
	 * Returns the request language prefix.
	 *
	 * @return string|null
	 */
	public function languagePrefix()
	{
		return $this->languagePrefix;
	}

	/**
	 * Returns the request method that was used.
	 *
	 * @return string
	 */
	public function method(): string
	{
		return $this->method;
	}

	/**
	 * Returns the real request method that was used.
	 *
	 * @return string
	 */
	public function realMethod(): string
	{
		return $this->realMethod;
	}

	/**
	 * Returns true if the request method has been faked and false if not.
	 *
	 * @return bool
	 */
	public function isFaked(): bool
	{
		return $this->realMethod !== $this->method;
	}

	/**
	 * Returns the basic HTTP authentication username or null.
	 *
	 * @return string|null
	 */
	public function username()
	{
		return $this->server->get('PHP_AUTH_USER');
	}

	/**
	 * Returns the basic HTTP authentication password or null.
	 *
	 * @return string|null
	 */
	public function password()
	{
		return $this->server->get('PHP_AUTH_PW');
	}

	/**
	 * Returns the referer.
	 *
	 * @param  mixed $default Value to return if no referer is set
	 * @return mixed
	 */
	public function referer($default = null)
	{
		return $this->headers->get('referer', $default);
	}
}
