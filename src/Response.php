<?php namespace Interactive\Http;


use Interactive\Http\Exception\HttpResponseException;

/**
 * Class Response
 *
 * @package Interactive\Http
 */
class Response
{
    /**
     * Request instance
     *
     * @var Request
     */
    protected $request;

    /**
     * Raw response
     *
     * @var
     */
    protected $raw;

    /**
     * Response body
     *
     * @var
     */
    protected $body;

    /**
     * Response headers
     *
     * @var
     */
    protected $headers;

    /**
     * HTTP Status code
     *
     * @var null|Int
     */
    protected $httpCode;

    protected $summary;

    public $contentType;

    public $charset;

    public function __construct(Request &$request, $raw) {
        if(!$raw){
            // client error, server doesn't respond
            throw new HttpResponseException("Request Error, {$request->getUrl()} doesn't respond");
        }

        $this->request  = $request;
        $this->raw      = $raw;
        $this->httpCode = $this->fetch($request->info, 'http_code', 0);

        $this->parseRaw();
    }

    /**
     * Parse raw content
     */
    protected function parseRaw() {
        $parts = explode("\r\n\r\nHTTP/", $this->raw);
        $parts = (count($parts) > 1 ? 'HTTP/' : '') . array_pop($parts);
        list($headers, $body) = explode("\r\n\r\n", $parts, 2);

        $this->headers = $headers;
        $this->body    = $body;

        $this->raw = sprintf("%s\r\n\r\n%s", $this->headers, $this->body);

        $this->parseHeaders();
    }

    /**
     * Parse header informations
     */
    protected function parseHeaders() {
        $lines = array_map(function($line) {
            return trim($line);
        }, explode("\n", $this->headers));

        $this->summary = $lines[ 0 ];

        array_shift($lines);

        $this->headers = array();
        foreach ($lines as $line) {
            if (preg_match("/([^:]+):\ ?(.*)/i", $line, $matches)) {
                $this->headers[ $matches[ 1 ] ] = $matches[ 2 ];

                // parse content-type
                if (strtolower($matches[ 1 ]) == 'content-type') {
                    $values            = preg_split("/;\ ?/i", $matches[ 2 ]);
                    $this->contentType = $this->fetch($values, 0);
                    $charset           = trim(str_replace("charset=", "", $this->fetch($values, 1, '')));

                    if (!empty($charset)) {
                        $this->charset = $charset;
                    }
                }
            }
        }
    }

    /**
     * Helper to fetch data from array
     *
     * @param      $array
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    protected function fetch($array, $key, $default = null) {
        return array_key_exists($key, $array) ? $array[ $key ] : $default;
    }

    /**
     * Fetch header information
     *
     * @param $key
     *
     * @return null
     */
    public function header($key) {
        return $this->fetch($this->headers, $key);
    }

    /**
     * access the request instance
     *
     * @return \Interactive\Http\Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Fetch the response body
     *
     * @return mixed
     */
    public function getBody() {
        return $this->body;
    }
}