<?php

class Eyeem_Ressource
{

  /* Context */

  protected $eyeem = null;

  /* Static Properties */

  public static $name;

  public static $endpoint;

  public static $properties = array();

  public static $collections = array();

  /* Object Properties */

  public $id;

  public $updated;

  public function __construct($infos = array())
  {
    if (is_int($infos) || is_string($infos)) {
      $this->id = $infos;
    }
    if (is_array($infos)) {
      $this->setInfos($infos);
    }
  }

  public function setInfos($infos = array())
  {
    foreach ($infos as $key => $value) {
      if (in_array($key, static::$properties)) {
        $this->$key = $value;
      }
    }
  }

  public function getInfos()
  {
    $infos = $this->getRessource();
    $this->setInfos($infos);
    return $infos;
  }

  public function getName()
  {
    return static::$name;
  }

  public function getCacheKey()
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    $id = $this->id == 'me' ? $this->getEyeem()->getAccessToken() : $this->id;
    $updated = $this->getUpdated();
    return static::$name . '_' . $id . ($updated ? '_' . $updated : '');
  }

  public function getEndpoint()
  {
    if (empty($this->id)) {
      throw new Exception("Unknown id.");
    }
    return str_replace('{id}', $this->id, static::$endpoint);
  }

  public function getRessource()
  {
    // From Cache
    $cacheKey = $this->getCacheKey();
    if (!$value = Eyeem_Cache::get($cacheKey)) {
      // Fresh
      $name = $this->getName();
      $response = $this->request( $this->getEndpoint() );
      if (empty($response[$name])) {
        throw new Exception("Missing ressource in response ($name).");
      }
      $value = $response[$name];
      Eyeem_Cache::set($cacheKey, $value, $this->getUpdated() ? 0 : null);
    }
    return $value;
  }

  public function getRessourceObject($type, $infos = array())
  {
    return $this->getEyeem()->getRessourceObject($type, $infos);
  }

  public function getCollection($name)
  {
    $type = static::$collections[$name];
    $collection = new Eyeem_RessourceCollection($name, $type, $this);
    return $collection;
  }

  public function request($endpoint, $method = 'GET', $params = array())
  {
    $response = $this->getEyeem()->request($endpoint, $method, $params);
    return $response;
  }

  public function __get($key)
  {
    if (in_array($key, static::$properties)) {
      $infos = $this->getInfos();
      return $infos[$key];
    }
    throw new Exception("Unknown property ($key).");
  }

  public function __call($name, $arguments)
  {
    // Get methods
    if (substr($name, 0, 3) == 'get') {
      $key = lcfirst(substr($name, 3));
      // Collection Objects
      if (isset(static::$collections[$key])) {
        return $this->getCollection($key);
      }
      // Default (read object property)
      return $this->$key;
    }
    // Set methods
    if (substr($name, 0, 3) == 'set') {
      $key = lcfirst(substr($name, 3));
      // Default (write object property)
      return $this->$key = $arguments[0];
    }
    throw new Exception("Unknown method ($name).");
  }

  public function toArray()
  {
    // To Fetch or Not To Fetch missing data?
    $array = array();
    foreach (static::$properties as $key) {
      $array[$key] = $this->$key;
    }
    return $array;
  }

}