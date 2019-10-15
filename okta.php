<?php

use MediaWiki\Session\SessionManager;

$wgOktaSessionUser = false;

function testme() {
  //$session = \MediaWiki\Session\SessionManager::getGlobalSession();
  var_dump($_COOKIE);
}

$wgInvalidUsernameCharacters = "";

$wgAuthRemoteuserUserName = function() {
  //var_dump($this);
  //$session = \MediaWiki\Session\SessionManager::getGlobalSession();
  if($_COOKIE['okta_access_token']) {
    return get_okta_user_info($_COOKIE['okta_access_token'])->preferred_username;
  } else {
    return false;
  }
  //echo $_SESSION;
};

$wgAuthRemoteuserUserPrefs = [
  'realname' => function($metadata) {
    return get_okta_user_info($_COOKIE['okta_access_token'])->name;
  }
];

function okta_code_to_access_token($code) {
  $authHeaderSecret = base64_encode('<CLIENTID>' .  ':' .  '<CLIENTSECRET>');
  $query = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => 'http://ots-macfound-test.com/lfc/index.php/Special:OktaLogin'
  ]);
  $headers = [
    'Authorization: Basic ' . $authHeaderSecret,
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded',
    'Connection: close',
    'Content-Length: 0'
  ];
  $url = 'https://dev-354109.oktapreview.com/oauth2/default/v1/token?' . $query;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POST, 1);
  $output = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_error($ch)) {
      $httpcode = 500;
  }
  curl_close($ch);
  return json_decode($output)->access_token;
}

function get_okta_user_info($access_token) {
  $headers = [
    'Authorization: Bearer ' . $access_token,
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded',
    'Connection: close',
    'Content-Length: 0'
  ];
  $url = 'https://dev-354109.oktapreview.com/oauth2/default/v1/userinfo';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POST, 1);
  $output = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_error($ch)) {
    $httpcode = 500;
  }
  curl_close($ch);
  return json_decode($output);
}

function okta_personal_url(&$personal_urls, $title, $skin) {
  global $wgUser;
  if(!$wgUser->isLoggedIn()) {
    $href = Title::newFromText("Special:OktaLogin");
    $personal_urls['login'] = ['text' => 'Log In', 'href' => $href->getFullURL(), 'active' => false];
  }
  return true;
};

class OktaLogin extends SpecialPage {
  public function __construct() {
    parent::__construct('OktaLogin');
  }

  public function execute($subPage) {
    if($this->getRequest()->getVal('code')) {
      $access_token = okta_code_to_access_token($this->getRequest()->getVal('code'));
      setcookie('okta_access_token', $access_token);
      $this->getOutput()->redirect('/lfc/');
      return;
    } else {
      $q = http_build_query([
        'client_id' => '0oanvlylo8mGLMmtc0h7',
        'response_type' => 'code',
        'response_mode' => 'query',
        'scope' => 'openid profile',
        'redirect_uri' => $this->getTitle()->getFullURL(),
        'state' => 'Start',
        'nonce' => uniqid()
      ]);
      $this->getOutput()->redirect('https://dev-354109.oktapreview.com/oauth2/default/v1/authorize?'.$q);
      return;
    }
  }
};

$wgHooks['PersonalUrls'][] = "okta_personal_url";
$wgSpecialPages['OktaLogin'] = "OktaLogin";

?>
