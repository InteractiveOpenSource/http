<?php namespace Interactive\Http;


/**
 * Class Request
 *
 * @package Interactive\Http
 */
class Request
{
    const PARSED_PROTOCOL = 2;
    const PARSED_HOST = 3;
    const PARSED_PATH = 4;
    const PARSED_BASE_URL = 1;

    protected $url;

    /**
     * @var Response
     */
    protected $httpResponse;

    /**
     * @var mixed Curl instance
     */
    public $ch;

    /**
     * @var
     */
    public $info;

    public $error;

    protected $headers = array();

    protected $data = array();

    /**
     * Function __construct
     * Set member variables
     *
     * @param string $url // Url to send http request to
     */
    public function __construct($url = null) {

        // Initialize curl
        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

        if (isset($url)) {
            $this->setUrl($url);
        }
    }

    /**
     * Function setUrl
     * Set http request url
     *
     * @param $url
     *
     * @return $this
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getHost() {
        return $this->parseUrl(self::PARSED_HOST);
    }

    public function getProtocol() {
        return $this->parseUrl(self::PARSED_PROTOCOL);
    }

    public function getUri() {
        return $this->parseUrl(self::PARSED_PATH, '');
    }

    protected function parseUrl($key = null, $default = false) {
        preg_match("/((https?):\/\/([^\/]+))(\/(.*))/i", $this->url, $m);

        if (is_null($key)) {
            return $m;
        } else {
            return array_key_exists($key, $m) ? $m[ $key ] : $default;
        }
    }

    /**
     * Function setPostData
     * Set data to be posted to the url
     *
     * @param array $params // Key value pairs of data to be posted
     *
     * @return $this
     */
    public function setPostData($params) {
        $this->data = array_merge($this->data, $params);

        return $this;
    }

    /**
     * Set http method
     *
     * @param $method
     *
     * @return $this
     */
    public function setMethod($method){
        switch (strtoupper($method)){
            case 'GET':
                curl_setopt($this->ch, CURLOPT_POST, false);
                break;
            case 'POST':
                curl_setopt($this->ch, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($this->ch, CURLOPT_PUT, true);
                break;
            case 'HEAD':
                curl_setopt($this->ch, CURLOPT_HEADER, true);
                break;
        }

        return $this;
    }

    public function setUserAgent($user){
        curl_setopt($this->ch, CURLOPT_USERAGENT, $user);

        return $this;
    }

    /**
     * Function setHeaders
     * Set http request headers
     *
     * @param array $headers // array containing headers
     *
     * @return $this
     */
    public function setHeaders(array $headers) {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Function send
     * Send http request
     *
     * @return Response
     */
    public function send() {
        curl_setopt($this->ch, CURLOPT_URL, $this->url);

        if (!empty($this->headers)) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        if (!empty($this->data)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->data);
        }

        $raw = curl_exec($this->ch);
        $this->error = curl_error($this->ch);
        $this->info  = curl_getinfo($this->ch);

        // execute curl
        $this->httpResponse = new Response($this, $raw);

        return $this->getResponse();
    }

    /**
     * Function getResponse
     * return response of last http request sent
     *
     * @return Response
     */
    public function getResponse() {
        return $this->httpResponse;
    }

    /**
     * Function __destruct
     * class destructor
     */
    public function __destruct() {
        curl_close($this->ch);
    }
}