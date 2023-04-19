<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class klereo extends eqLogic {
  /*   * *************************Attributs****************************** */

 /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

 /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  */
  public static $_encryptConfigKey = array('login', 'password', 'jwt::Authorization');

  public static $_version = '1.0 beta1';
  
  public static $_WEB_VERSION = '3.85';
  public static $_API_ROOT = 'https://connect.klereo.fr/php/';
  public static $_USER_AGENT = 'Jeedom plugin';

  /*   * ***********************Methode static*************************** */
  
 /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
   * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
   public static function cron5() {}
   */

  // Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {
    
  }
  
  /*
   * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
   public static function cron15() {}
   */

  /*
   * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
   public static function cron30() {}
   */

  /*
   * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
   */

  /*
   * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
   */
  
  /*
   * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function health() {}
   */
  
  // public static function deamon_info() { }
  
  // public static function deamon_start() { }
  
  
  
  /*   * *********************Méthodes d'instance************************* */
  
  // Fonction exécutée automatiquement avant la suppression de l'équipement
  //public function preRemove() {}

  // Fonction exécutée automatiquement après la suppression de l'équipement
  //public function postRemove() { }
  
  // Fonction exécutée automatiquement avant la sauvegarde de l'équipement (création ou mise à jour)
  // La levée d'une exception invalide la sauvegarde
  //public function preSave() { }

 /*
  * Fonction exécutée automatiquement après la sauvegarde de l'équipement (création ou mise à jour)
  public function postSave() {}
  */
  
  //public function postAjax() { }

 /*
  * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
  public function toHtml($_version = 'dashboard') {}
  */

 /*
  * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
  public static function postConfig_<Variable>() {}
  */

 /*
  * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
  public static function preConfig_<Variable>() {}
  */

  /*   * **********************Getteur Setteur*************************** */
  
  public static function now() {
    return date('Y-m-d H:i:s');
  }
  
  public static function setopt_merge($arr1, $arr2) {
    $ret = $arr1;
    foreach ($arr2 as $k => $v)
      $ret[$k] = $v;
    return $ret;
  }
  
  public static function curl_request($_curl_setopt_array = null, $_function_name = '') {
    if (is_null($_curl_setopt_array))
      throw new Exception(__CLASS__ . '::curl_request&nbsp;:</br>' . __('Paramètres insuffisants', __FILE__));
    $default_setopt_array = array(
      CURLOPT_USERAGENT       => self::$_USER_AGENT,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_FOLLOWLOCATION  => true,
      CURLOPT_CONNECTTIMEOUT  => 10,
      CURLOPT_TIMEOUT         => 20,
      CURLOPT_SSL_VERIFYPEER  => true,
      CURLOPT_SSL_VERIFYHOST  => 2,
      CURLOPT_HEADER          => true
    );
    $curl_stopt_array = self::setopt_merge($default_setopt_array, $_curl_setopt_array);
    $ch = curl_init();
    curl_setopt_array($ch, $curl_stopt_array);
    $page_content = curl_exec($ch);
    log::add('klereo', 'debug', __CLASS__ . '::' . $_function_name . ' / $page_content = *' . $page_content . '*');
    $curl_info = curl_getinfo($ch);
    if (curl_error($ch)) {
      $error_message = curl_strerror(curl_errno($ch));
      curl_close($ch);
      throw new Exception(__CLASS__ . '::' . $_function_name . '&nbsp;:</br>' . __('Erreur d\'exécution de la requête d\'authentification&nbsp;: ', __FILE__) . $error_message);
    }
    curl_close($ch);
    if ($curl_info['http_code'] != 200)
      throw new Exception(__CLASS__ . ';;' . $_function_name . '&nbsp;:</br>' . __('Erreur d\'exécution de la requête d\'authentification&nbsp;: ', __FILE__) . 'http_code = ' . $curl_info['http_code']);
    $header_size = $curl_info['header_size'];
    $header = substr($page_content, 0, $header_size);
    $body = substr($page_content, $header_size);  
    $response = json_decode($body, true);
    if (!is_array($response) || !isset($response['status']))
      throw new Exception(__CLASS__ . '::' . $_function_name . '&nbsp;:</br>' . __('Réponse inatendue&nbsp;: ', __FILE__) . $body);
    if ($response['status'] != 'ok')
      throw new Exception(__CLASS__ . '::' . $_function_name . '&nbsp;:</br>' . __('Echec de l\'authentification&nbsp;: ', __FILE__) . (isset($response->detail) ? $response->detail : __('pas de détail retourné', __FILE__)));
    log::add('klereo', 'debug', __CLASS__ . '::' . $_function_name . ' / curl_request return OK');
    return array($header, $response);
  }
  
  public static function getJwtToken() {
    $config_jwt_login_dt = config::byKey('jwt::login_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+55 minutes ' . $config_jwt_login_dt);
    if (strtotime(self::now()) >= $expire_dt || config::byKey('jwt::Authorization', __CLASS__, '') === '') {
      if (config::byKey('login', __CLASS__, '') === '' || config::byKey('password', __CLASS__, '') === '')
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Les informations de connexions doivent être renseignées dans la configuration du plugin Klereo', __FILE__));
      $post_data = array(
        'login'     => config::byKey('login', __CLASS__),
        'password'  => sha1(config::byKey('password', __CLASS__)),
        'version'   => self::$_WEB_VERSION
      );
      $curl_setopt_array = array(
        CURLOPT_URL         => self::$_API_ROOT . 'GetJWT.php',
        CURLOPT_POST        => true,
        CURLOPT_POSTFIELDS  => $post_data
      );
      $response = self::curl_request($curl_setopt_array, __FUNCTION__);
      $header = $response[0];
      $body = $response[1];
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $header = *' . $header . '*');
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $body = *' . json_encode($body) . '*');
      preg_match('/.*?^(Authorization\: Bearer \S*?)\s*$.*/ms', $header, $matches);
      $jwt_from_header = $matches[1];
      config::save('jwt::login_dt', self::now(), __CLASS__);
      config::save('jwt::Authorization', $jwt_from_header, __CLASS__);
      return $jwt_from_header;
    }
    
    return config::byKey('jwt::Authorization', __CLASS__);
  }
  
  public static function getIndex() {
    $config_getIndex_dt = config::byKey('getIndex_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+24 hours ' . $config_getIndex_dt);
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
    if (strtotime(self::now()) >= $expire_dt || config::byKey('getIndex', __CLASS__, '') === '') {
      if (config::byKey('login', __CLASS__, '') === '' || config::byKey('password', __CLASS__, '') === '')
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Les informations de connexions doivent être renseignées dans la configuration du plugin Klereo', __FILE__));
      $curl_setopt_array = array(
        CURLOPT_URL         => self::$_API_ROOT . 'GetIndex.php',
        CURLOPT_POST        => false,
        CURLOPT_HTTPHEADER  => array(
          'User-Agent: ' . self::$_USER_AGENT,
          self::getJwtToken()
        )
      );
      $response = self::curl_request($curl_setopt_array, __FUNCTION__);
      $body = $response[1];
      if (isset($body['response']) && is_array($body['response'])) {
        $getIndex = array();
        foreach ($body['response'] as $pool)
          $getIndex[$pool['idSystem']] = $pool['poolNickname'];
        config::save('getIndex_dt', self::now(), __CLASS__);
        config::save('getIndex', $getIndex, __CLASS__);
        return $getIndex;
      } else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception de la liste des bassins', __FILE__));
    }
    
    return config::byKey('getIndex', __CLASS__);
  }
  
  public static function getPoolsDetails($_pool_id) {
    $config_getPoolsDetails_dt = config::byKey('getPoolsDetails_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+9 minutes 50 seconds ' . $config_getPoolsDetails_dt);
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
    if (strtotime(self::now()) >= $expire_dt || config::byKey('getPoolsDetails', __CLASS__, '') === '') {
      if (config::byKey('login', __CLASS__, '') === '' || config::byKey('password', __CLASS__, '') === '')
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Les informations de connexions doivent être renseignées dans la configuration du plugin Klereo', __FILE__));
      $post_data = array(
        'poolID'  => $_pool_id,
        'lang'    => substr(translate::getLanguage(), 0, 2)
      );
      $curl_setopt_array = array(
        CURLOPT_URL         => self::$_API_ROOT . 'GetPoolsDetails.php',
        CURLOPT_POST        => true,
        CURLOPT_HTTPHEADER  => array(
          'User-Agent: ' . self::$_USER_AGENT,
          self::getJwtToken()
        )
      );
      $response = self::curl_request($curl_setopt_array, __FUNCTION__);
      $body = $response[1];
      if (isset($body['response']) && is_array($body['response'])) {
        $getPoolsDetails = $body['response'];
        config::save('getPoolsDetails_dt', self::now(), __CLASS__);
        config::save('getPoolsDetails', $getPoolsDetails, __CLASS__);
        return $getPoolsDetails;
      } else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception des détails du bassin', __FILE__));
    }
    
    return config::byKey('getPoolsDetails', __CLASS__);
  }
  
  //public static function getDeamonState() { }
  
  //public static function getDeamonLaunchable($eqConfig='') { }
  
}

class klereoCmd extends cmd {
  /*   * *************************Attributs****************************** */

  /*   * ***********************Methode static*************************** */

  /*   * *********************Methode d'instance************************* */

  /*
   * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
    return true;
    }
   */
  
  public function execute($_option=array()) {
    
  }

//  public function postInsert() {}
//  public function postRemove() {}
//  public function postSave() {}
  
  // Fonction exécutée automatiquement avant la sauvegarde de la commande (création ou mise à jour)
  // La levée d'une exception invalide la sauvegarde
  //public function preSave() { }

  /*   * **********************Getteur Setteur*************************** */
}