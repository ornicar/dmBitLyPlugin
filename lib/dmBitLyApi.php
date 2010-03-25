<?php

class dmBitLyApi
{
  protected
  $login,
  $apiKey,
  $browser,
  $table;

  public function __construct($login, $apiKey, dmWebBrowser $browser)
  {
    if(!$login || !$apiKey)
    {
      throw new dmException('You must provide a login and an api key in services.yml');
    }
    $this->login    = $login;
    $this->apiKey   = $apiKey;
    
    $this->browser  = $browser;

    $this->table    = dmDb::table('DmBitLyUrl');
  }

  public function shorten($longUrl)
  {
    if($short = $this->table->findShortByExpanded($longUrl))
    {
      return $short;
    }

    $url = sprintf('http://api.bit.ly/shorten?version=2.0.1&longUrl=%s&login=%s&apiKey=%s',
      urlencode($longUrl),
      $this->login,
      $this->apiKey
    );
    
    if($this->browser->get($url)->responseIsError())
    {
      throw new dmException(sprintf('The given URL (%s) returns an error (%s: %s)', $url, $browser->getResponseCode(), $browser->getResponseMessage()));
    }

    $response = json_decode($this->browser->getResponseText(), true);

    if($response['statusCode'] !== 'OK')
    {
      throw new dmException('Error when shortening URL: '.$response['errorMessage']);
    }

    $short = $response['results'][$longUrl]['shortUrl'];

    $this->table->create(array(
      'short' => $short,
      'expanded' => $longUrl
    ))->save();

    return $short;
  }
}