<?php
/**
 * Created for analytics.
 * User: suman
 * Date: 12/4/16
 * Time: 1:37 PM
 */


/**
 * Gets URL contents within the HTTP server response header fields.
 * This function uses cURL to fetch remote pages.
 * @param  string   $URL   web page URL
 * @param  array    $opts  custom cURL options
 * @return array           Transfer information (the web page content is in the "content" array key)
 * @link  http://es2.php.net/manual/en/curl.constants.php
 * @link  http://es2.php.net/manual/en/function.curl-setopt.php
 */
function get_remote_webpage($URL, $opts = array())
{
    if (empty($URL)) return false;
    // basic options (regular GET requests)
    $options = array(
        CURLOPT_URL            => $URL,
        CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
        CURLOPT_RETURNTRANSFER => true,   // return transfer as a string
        CURLOPT_HEADER         => false,  // don't return headers
        CURLOPT_ENCODING       => "",     // handle all encodings
        CURLOPT_CONNECTTIMEOUT => 10,     // timeout on connect
        CURLOPT_TIMEOUT        => 60,     // timeout on response
        CURLOPT_SSL_VERIFYPEER => false,  // try to fetch SSL pages too
        CURLOPT_SSL_VERIFYHOST => false
    );

    /* cURL should follow redirections!
     * But safe mode (deprecated) and open_basedir (useless) are incompatible
     * with CURLOPT_FOLLOWLOCATION.
     * Also see this solution: http://www.php.net/manual/en/function.curl-setopt.php#71313
     */
    if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
        $options[ CURLOPT_FOLLOWLOCATION ] = true;  // follow redirects
        $options[ CURLOPT_AUTOREFERER ]    = true;  // automatically set the Referer: field
        $options[ CURLOPT_MAXREDIRS ]      = 5;     // limit redirect loops

    }

    // add custom cURL options (e.g. POST requests, cookies, etc.)
    if (count($opts) > 0)
    {
        foreach ($opts as $key => $value) {
            $options[$key] = $value;
        }
    }

    $ch = curl_init();

    curl_setopt_array($ch, $options);

    $content  = curl_exec($ch);     // the Web page
    $transfer = curl_getinfo($ch);  // transfer information (http://www.php.net/manual/en/function.curl-getinfo.php)
    $errnum   = curl_errno($ch);    // codes: http://curl.haxx.se/libcurl/c/libcurl-errors.html
    $errmsg   = curl_error($ch);    // empty string on success

    curl_close($ch);

    // extend transfer info
    $transfer['errnum']  = $errnum;
    $transfer['errmsg']  = $errmsg;
    $transfer['content'] = $content;
    // $transfer['url'] is the final URL after redirections, if CURLOPT_FOLLOWLOCATION is set to true

    return $transfer;
}

/**
 * Assigns an unique identifier for each client machine.
 * @return  string
 */
function get_client_id()
{
    if (isset($_COOKIE['va-id'])) {
        $id = $_COOKIE['va-id'];
    } else {
        $id = md5( mt_rand() + date("now") + mt_rand() );
        $expires = time() + 60 * 60 * 24 * 365; // 1 year
        setcookie('va-id', $id, $expires, "/");
    }

    return $id;
}

/**
 * Removes va scripts from DOM.
 */
function remove_va_scripts(&$dom)
{
    $scripts = $dom->getElementsByTagName("script");
    $scriptsToRemove = array();
    // mark
    foreach ($scripts as $script)
    {
        $src = $script->getAttribute("src");
        // use hardcoded strings instead of defined constants since file versions will change
        if (strstr($src, "va-record") || strstr($src, "va-aux") || strstr($src, "va-api")) {
            $scriptsToRemove[] = $script;
        }
    }
    // sweep
    foreach ($scriptsToRemove as $script) {
        $script->parentNode->removeChild($script);
    }
}


function json_response($array){
    cors();
    header('Content-type: application/json');
    echo json_encode($array);
    exit(0);
}

function cors() {

    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }


}
?>
