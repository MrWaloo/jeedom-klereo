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
  public static function cron10() { // DEBUG cron10 !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    self::actualizeValues();
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
      throw new Exception(__CLASS__ . '::' . $_function_name . '&nbsp;:</br>' . __('Echec de l\'authentification&nbsp;: ', __FILE__) . (isset($response['detail']) ? $response['detail'] : __('pas de détail retourné', __FILE__)));
    log::add('klereo', 'debug', __CLASS__ . '::' . $_function_name . ' / curl_request return OK');
    return array($header, $response);
  }
  
  /* Eventuellement appelée par plugins/klereo/core/ajax/klereo.ajax.php
  public static function reinit() { }
  */
  
  public static function getJwtToken() {
    $config_jwt_login_dt = config::byKey('jwt::login_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+55 minutes ' . $config_jwt_login_dt);
    //$expire_dt = strtotime('+1 minute ' . $config_jwt_login_dt); // DEBUG
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
    //$expire_dt = strtotime('+1 minute ' . $config_getIndex_dt); // DEBUG
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
        $getIndex = $body['response'];
        config::save('getIndex_dt', self::now(), __CLASS__);
        config::save('getIndex', $getIndex, __CLASS__);
        return $getIndex;
      } else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception de la liste des bassins', __FILE__));
    }
    
    return config::byKey('getIndex', __CLASS__);
  }
  
  public static function getPools() {
    $getIndex = self::getIndex();
    $getPools = array();
    foreach ($getIndex as $pool)
      $getPools[$pool['idSystem']] = $pool['poolNickname'];
    return $getPools;
  }
  
  public static function actualizeValues() {
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / Start');
    foreach (self::byType('klereo') as $eqKlereo) { // boucle sur les équipements
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $eqKlereo->getName() = ' . $eqKlereo->getName());
      if ($eqKlereo->getIsEnable() && $eqKlereo->getConfiguration('eqPoolId', '') != '') {
        $probes = $eqKlereo->getProbesInfos();
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $probes = ' . json_encode($probes));
        
        foreach ($probes as $name => $config) {
          $filteredCmd = $eqKlereo->getCmd(null, $name . '::filtered');
          if ($filteredCmd) {
            if ($config['filteredValue'] < $filteredCmd->getConfiguration('minValue'))
              $filteredCmd->setConfiguration('minValue', $config['filteredValue']);
            if ($config['filteredValue'] > $filteredCmd->getConfiguration('maxValue'))
              $filteredCmd->setConfiguration('maxValue', $config['filteredValue']);
            $eqKlereo->checkAndUpdateCmd($filteredCmd, $config['filteredValue']);
          }
          $directCmd = $eqKlereo->getCmd(null, $name . '::direct');
          if ($directCmd) {
            if ($config['directValue'] < $directCmd->getConfiguration('minValue'))
              $directCmd->setConfiguration('minValue', $config['directValue']);
            if ($config['directValue'] > $directCmd->getConfiguration('maxValue'))
              $directCmd->setConfiguration('maxValue', $config['directValue']);
            $eqKlereo->checkAndUpdateCmd($directCmd, $config['directValue']);
          }
        }
        
        $details = $eqKlereo->getPoolDetails();
        $pool_id = $details['idSystem'];
        $poolNickname = $details['poolNickname'];
        
        $getIndex = self::getIndex();
        $pool = null;
        foreach ($getIndex as $pool_info) {
          if ($pool_info['idSystem'] == $pool_id) {
            $pool = $pool_info;
            break;
          }
        }
        
        $PoolMode = $eqKlereo->getCmd(null, 'PoolMode');
        if ($PoolMode) {
          $PoolMode_arr = array(
            0 => __('Arrêt', __FILE__),
            1 => __('Mode éco', __FILE__),
            2 => __('Mode confort', __FILE__),
            4 => __('Mode hivernage', __FILE__),
            5 => __('Mode installation', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($PoolMode, $PoolMode_arr[$pool['RegulModes']['PoolMode']]);
        }
        
        $TraitMode = $eqKlereo->getCmd(null, 'TraitMode');
        if ($TraitMode) {
          $TraitMode_arr = array(
            0 => __('Aucun', __FILE__),
            1 => __('Chlore liquide', __FILE__),
            2 => __('Electrolyseur', __FILE__),
            3 => __('Electrolyseur KL1', __FILE__),
            4 => __('Oxygène actif', __FILE__),
            5 => __('Brome', __FILE__),
            6 => __('Electrolyseur KL2', __FILE__),
            8 => __('Electrolyseur KL3', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($TraitMode, $TraitMode_arr[$pool['RegulModes']['TraitMode']]);
        }
        
        $pHMode = $eqKlereo->getCmd(null, 'pHMode');
        if ($pHMode) {
          $pHMode_arr = array(
            0 => __('Aucun', __FILE__),
            1 => __('pH-Minus', __FILE__),
            2 => __('pH-Plus', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($pHMode, $pHMode_arr[$pool['RegulModes']['pHMode']]);
        }
        
        $HeaterMode = $eqKlereo->getCmd(null, 'HeaterMode');
        if ($HeaterMode) {
          $HeaterMode_arr = array(
            0 => __('Aucun', __FILE__),
            1 => __('Réchauffeur', __FILE__),
            2 => __('Pompe à chaleur KL1', __FILE__),
            3 => __('Chauffage sans consigne', __FILE__),
            4 => __('Pompe à chaleur KL2', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($HeaterMode, $HeaterMode_arr[$pool['RegulModes']['HeaterMode']]);
        }
        
        $access = $eqKlereo->getCmd(null, 'access');
        if ($access) {
          $accessValue = __('Valeur inconnue', __FILE__);
          if ($details['access'] == 5)
            $accessValue = __('Lecture seule', __FILE__);
          elseif ($details['access'] == 10)
            $accessValue = __('Client final', __FILE__);
          elseif ($details['access'] == 20)
            $accessValue = __('Professionnel', __FILE__);
          elseif ($details['access'] >= 25)
            $accessValue = __('Accès klereo', __FILE__);
          
          $eqKlereo->checkAndUpdateCmd($access, $accessValue);
        }
        
        $ProductIdx = $eqKlereo->getCmd(null, 'ProductIdx');
        if ($ProductIdx) {
          $ProductIdx_arr = array(
            0 => 'Care / Premium',
            1 => 'Kompact M5',
            2 => 'Undefined',
            3 => 'Kompact Plus M5',
            4 => 'Kalypso Pro Salt',
            5 => 'Kompact M9',
            6 => 'Kompact Plus M9',
            7 => 'Kompact Plus M2'
          );
          $eqKlereo->checkAndUpdateCmd($ProductIdx, $ProductIdx_arr[$details['ProductIdx']]);
        }
        
        $PumpType = $eqKlereo->getCmd(null, 'PumpType');
        if ($PumpType) {
          $PumpType_arr = array(
            0 => __('Pompe générique (pilotée par contacteur)', __FILE__),
            1 => __('Pompe KlereoFlô (pilotée par bus RS485)', __FILE__),
            2 => __('Pompe Pentair (pilotée par bus)', __FILE__),
            7 => __('Aucune pompe', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($PumpType, $PumpType_arr[$pool['PumpType']]);
        }
        
        $isLowSalt = $eqKlereo->getCmd(null, 'isLowSalt');
        if ($isLowSalt) {
          $isLowSalt_arr = array(
            0 => 'Gamme 5g/l',
            1 => 'Gamme 2g/l'
          );
          $eqKlereo->checkAndUpdateCmd($isLowSalt, $isLowSalt_arr[$pool['isLowSalt']]);
        }
        
        $alertCount = $eqKlereo->getCmd(null, 'alertCount');
        if ($alertCount) {
          $eqKlereo->checkAndUpdateCmd($alertCount, $pool['alertCount']);
        }
        
        $alert_1 = $eqKlereo->getCmd(null, 'alert_1');
        if ($alert_1) {
          $alert_1_arr = array(
            0   => __('Pas d\'alerte', __FILE__),
            1   => __('Capteur HS', __FILE__),
            2   => __('Problème de configuration relais', __FILE__),
            3   => __('Inversion sondes pH/Redox', __FILE__),
            5   => __('Piles faibles', __FILE__),
            6   => __('Calibration', __FILE__),
            7   => __('Minimum', __FILE__),
            8   => __('Maximum', __FILE__),
            10  => __('Non reçu', __FILE__),
            11  => __('Hors-gel', __FILE__),
            12  => __('Alerte #12 inconnue', __FILE__),
            13  => __('Surconsommation d\'eau', __FILE__),
            14  => __('Fuite d\'eau', __FILE__),
            21  => __('Défaut mémoire interne', __FILE__),
            22  => __('Problème de circulation', __FILE__),
            23  => __('Plages de filtration insuffisantes', __FILE__),
            25  => __('pH élevé, désinfectant inefficace', __FILE__),
            26  => __('Filtration sous-dimensionée', __FILE__),
            28  => __('Régulation arrêtée', __FILE__),
            29  => __('Filtration en mode MANUEL-ARRET', __FILE__),
            30  => __('Mode INSTALLATION', __FILE__),
            31  => __('Traitement choc', __FILE__),
            34  => __('Régulation suspendue ou désactivée', __FILE__),
            35  => __('Maintenance', __FILE__),
            36  => __('Limitation journalière de l\'injection', __FILE__),
            37  => __('Muticapteur défaillant', __FILE__),
            38  => __('Liaison électrolyseur défaillante', __FILE__),
            39  => __('Limite jounalière du brominateur', __FILE__),
            40  => __('Electrolyseur:', __FILE__),
            41  => __('Liaison pompe à chaleur défaillante', __FILE__),
            42  => __('Configuration des capteurs incohérente', __FILE__),
            43  => __('Electrolyseur sécurisé', __FILE__),
            44  => __('Entretien des pompes doseuses', __FILE__),
            45  => __('Apprentissage non fait', __FILE__),
            46  => __('Flux d\'eau analyse absent', __FILE__),
            47  => __('Configuration de la couverture incohérente', __FILE__),
            48  => __('Filtration non contrôlée', __FILE__),
            49  => __('Vérifiez l\'horloge', __FILE__),
            50  => __('Pompe à chaleur', __FILE__),
            51  => __('Pompe à chaleur', __FILE__),
            52  => __('Pompe à chaleur', __FILE__),
            53  => __('Liaison filtration défaillante', __FILE__),
            54  => __('Pompe de filtration', __FILE__),
            55  => __('Mulcapteur Gen3 ou Gen5 absent', __FILE__),
            56  => __('Etat de la filtration inconnu, risque de traitement sans filtration', __FILE__),
            57  => __('Multicapteur Gen3 ou Gen4 absent', __FILE__),
            58  => __('Mauvaise configuration de la pompe', __FILE__)
          );
          $alert_code = $pool['alerts'][0]['code'];
          $alert_param = $pool['alerts'][0]['param'];
          $param_text = '';
          if (in_array($alert_code, array(1, 7, 8, 10, 36))) { // param = CapteurID
            $sensor_types = self::getSensorTypes();
            $sensor_type = explode(';', $sensor_types[$alert_param]);
            $param_text = $sensor_type[0];
          } elseif ($alert_code == 5) {
            $param_text = 'RFID';
          } elseif ($alert_code == 6) {
            if ($param_text == 0)
              $param_text = 'pH';
            elseif ($param_text == 0)
              $param_text = __('Désinfectant', __FILE__);
          } // TODO ************************************************************************************************************** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!
          
          $eqKlereo->checkAndUpdateCmd($alert_1, $alert_1_arr[$alert_code] . (($param_text != '') ? ' - ' . $param_text : ''));
        }
        
      }
    }
  }
  
  /*   * *********************Méthodes d'instance************************* */
  
  // Fonction exécutée automatiquement avant la suppression de l'équipement
  //public function preRemove() {}

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    config::remove('getPoolDetails_dt::' . strval($eqPoolId), __CLASS__);
    config::remove('getPoolDetails::' . strval($eqPoolId), __CLASS__);
  }
  
  // Fonction exécutée automatiquement avant la sauvegarde de l'équipement (création ou mise à jour)
  // La levée d'une exception invalide la sauvegarde
  public function preSave() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $eqPoolId = ' . strval($eqPoolId));
    if ($eqPoolId === '')
      return true;
    if ($this->getIsEnable()) {
      $probes = $this->getProbesInfos();
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $probes = ' . json_encode($probes));
      
      $order = $this->getNextOrder();
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $order = ' . json_encode($order));
      foreach ($probes as $name => $config) {
        $descr = explode(';', $config['description']);
        $filteredCmd = $this->getCmd(null, $name . '::filtered');
        if (!is_object($filteredCmd)) {
          $filteredCmd = (new klereoCmd)
            ->setLogicalId( $name . '::filtered')
            ->setEqLogic_id($this->getId())
            ->setName($descr[0] . ' : ' . __('mesure en filtration', __FILE__))
            ->setType('info')
            ->setSubType('numeric')
            ->setConfiguration('minValue', $config['minValue'])
            ->setConfiguration('maxValue', $config['maxValue'])
            ->setUnite($descr[1])
            ->setOrder($order++)
            ->save();
        }
        $directCmd = $this->getCmd(null, $name . '::direct');
        if (!is_object($directCmd)) {
          $directCmd = (new klereoCmd)
            ->setLogicalId($name . '::direct')
            ->setEqLogic_id($this->getId())
            ->setName($descr[0] . ' : ' . __('mesure instantanée', __FILE__))
            ->setType('info')
            ->setSubType('numeric')
            ->setConfiguration('minValue', $config['minValue'])
            ->setConfiguration('maxValue', $config['maxValue'])
            ->setUnite($descr[1])
            ->setOrder($order++)
            ->save();
        }
      }
      
      $details = $this->getPoolDetails();
      $pool_id = $details['idSystem'];
      
      $PoolMode = $this->getCmd(null, 'PoolMode');
      if (!is_object($PoolMode)) {
        $PoolMode = (new klereoCmd)
          ->setLogicalId('PoolMode')
          ->setEqLogic_id($this->getId())
          ->setName(__('Mode de régulation', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $TraitMode = $this->getCmd(null, 'TraitMode');
      if (!is_object($TraitMode)) {
        $TraitMode = (new klereoCmd)
          ->setLogicalId('TraitMode')
          ->setEqLogic_id($this->getId())
          ->setName(__('Type de désinfectant', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $pHMode = $this->getCmd(null, 'pHMode');
      if (!is_object($pHMode)) {
        $pHMode = (new klereoCmd)
          ->setLogicalId('pHMode')
          ->setEqLogic_id($this->getId())
          ->setName(__('Type de correcteur de pH', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $HeaterMode = $this->getCmd(null, 'HeaterMode');
      if (!is_object($HeaterMode)) {
        $HeaterMode = (new klereoCmd)
          ->setLogicalId('HeaterMode')
          ->setEqLogic_id($this->getId())
          ->setName(__('Type de chauffage', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $access = $this->getCmd(null, 'access');
      if (!is_object($access)) {
        $access = (new klereoCmd)
          ->setLogicalId('access')
          ->setEqLogic_id($this->getId())
          ->setName(__('Droit d accès', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $ProductIdx = $this->getCmd(null, 'ProductIdx');
      if (!is_object($ProductIdx)) {
        $ProductIdx = (new klereoCmd)
          ->setLogicalId('ProductIdx')
          ->setEqLogic_id($this->getId())
          ->setName(__('Gamme de produit', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $PumpType = $this->getCmd(null, 'PumpType');
      if (!is_object($PumpType)) {
        $PumpType = (new klereoCmd)
          ->setLogicalId('PumpType')
          ->setEqLogic_id($this->getId())
          ->setName(__('Type de pompe de filtration', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $isLowSalt = $this->getCmd(null, 'isLowSalt');
      if (!is_object($isLowSalt)) {
        $isLowSalt = (new klereoCmd)
          ->setLogicalId('isLowSalt')
          ->setEqLogic_id($this->getId())
          ->setName(__('Gamme d électrolyseur', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      $alertCount = $this->getCmd(null, 'alertCount');
      if (!is_object($alertCount)) {
        $alertCount = (new klereoCmd)
          ->setLogicalId('alertCount')
          ->setEqLogic_id($this->getId())
          ->setName(__('Nombre d alertes', __FILE__))
          ->setType('info')
          ->setSubType('numeric')
          ->setConfiguration('minValue', 0)
          ->setConfiguration('maxValue', 10)
          ->setUnite('')
          ->setOrder($order++)
          ->save();
      }
      
      $alert_1 = $this->getCmd(null, 'alert_1');
      if (!is_object($alert_1)) {
        $alert_1 = (new klereoCmd)
          ->setLogicalId('alert_1')
          ->setEqLogic_id($this->getId())
          ->setName(__('Alerte 1', __FILE__))
          ->setType('info')
          ->setSubType('message')
          ->setOrder($order++)
          ->save();
      }
      
      self::actualizeValues();
    }
  }

 /*
  * Fonction exécutée automatiquement après la sauvegarde de l'équipement (création ou mise à jour)
  */
  //public function postSave()  { }
  
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
  
  public function getPoolDetails() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    $config_getPoolDetails_dt = config::byKey('getPoolDetails_dt::' . strval($eqPoolId), __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+9 minutes 50 seconds ' . $config_getPoolDetails_dt);
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
    if (strtotime(self::now()) >= $expire_dt || config::byKey('getPoolDetails::' . strval($eqPoolId), __CLASS__, '') === '') {
      if (config::byKey('login', __CLASS__, '') === '' || config::byKey('password', __CLASS__, '') === '')
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Les informations de connexions doivent être renseignées dans la configuration du plugin Klereo', __FILE__));
      $post_data = array(
        'poolID'  => $eqPoolId,
        'lang'    => substr(translate::getLanguage(), 0, 2)
      );
      $curl_setopt_array = array(
        CURLOPT_URL         => self::$_API_ROOT . 'GetPoolDetails.php',
        CURLOPT_POST        => true,
        CURLOPT_HTTPHEADER  => array(
          'User-Agent: ' . self::$_USER_AGENT,
          self::getJwtToken()
        )
      );
      $response = self::curl_request($curl_setopt_array, __FUNCTION__);
      $body = $response[1];
      if (isset($body['response']) && is_array($body['response'])) {
        $getPoolDetails = $body['response'][0];
        config::save('getPoolDetails_dt::' . strval($eqPoolId), self::now(), __CLASS__);
        config::save('getPoolDetails::' . strval($eqPoolId), $getPoolDetails, __CLASS__);
        return $getPoolDetails;
      } else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception des détails du bassin', __FILE__));
    }
    
    return config::byKey('getPoolDetails::' . strval($eqPoolId), __CLASS__);
  }
  
  public function getProbesInfos() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    if (!in_array($eqPoolId, array_keys(self::getPools())))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception des mesures du bassin : bassin inconnu.', __FILE__));
    $details = $this->getPoolDetails();
    $sensor_types = self::getSensorTypes();
    
    $probes = array();
    foreach ($details as $k => $v) {
      if (substr($k, -7) == 'Capteur') { // strlen('Capteur') = 7
        $probeName = substr($k, 0, -7);
        $probeInfo = array();
        foreach ($details['probes'] as $probe) {
          if ($probe['index'] === $v) {
            $probeInfo = array(
              'minValue'      => $probe['seuilMin'],
              'maxValue'      => $probe['seuilMax'],
              'filteredValue' => $probe['filteredValue'],
              'directValue'   => $probe['directValue'],
              'description'   => $sensor_types[$probe['type']]
            );
            break;
          }
        }
        if (isset($probeInfo['minValue']))
          $probes[$probeName] = $probeInfo;
      }
    }
    return $probes;
  }
  
	public function getNextOrder() {
		$values = array(
			'class'       => __CLASS__,
			'eqLogic_id'  => $this->getId()
		);
		$sql = 'SELECT MAX(`order`) as maxorder
    FROM `cmd`
    WHERE `eqType`=:class AND `eqLogic_id`=:eqLogic_id';
		$sqlResult = DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW);
    if ($sqlResult['maxorder'] == null)
      return 0;
    return intval($sqlResult['maxorder']) + 1;
	}
  
  public static function getSensorTypes() {
    return array(
      __('Température local technique', __FILE__) . ';°C',
      __('Température air', __FILE__) . ';°C',
      __('Niveau d\'eau', __FILE__) . ';%',
      __('pH seul', __FILE__) . ';pH',
      __('Redox seul', __FILE__) . ';mV',
      __('Température eau', __FILE__) . ';°C',
      __('Pression filtre', __FILE__) . ';mbar',
      __('Générique', __FILE__) . ';%',
      __('Débit', __FILE__) . ';m<sup>3</sup>/h',
      __('Niveau bidon', __FILE__) . ';%',
      __('Position volet / couverture', __FILE__) . ';%',
      __('Chlore', __FILE__) . ';'
    );
  }
  
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

?>