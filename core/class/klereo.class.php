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

  public static $_version = '0.1 beta1';
  
  static $_WEB_VERSION = '3.87';
  static $_API_ROOT = 'https://connect.klereo.fr/php/';
  static $_USER_AGENT = 'Jeedom plugin';
  
  /*   * ***********************Methode static*************************** */
  
 /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  
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
   
   * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
   public static function cron30() {}
   
   * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  
   * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  
   * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function health() {}
   */
  
  // public static function deamon_info() { }
  // public static function deamon_start() { }
  
  static function now() {
    return date('Y-m-d H:i:s');
  }
  
  static function setopt_merge($arr1, $arr2) {
    $ret = $arr1;
    foreach ($arr2 as $k => $v)
      $ret[$k] = $v;
    return $ret;
  }
  
  static function curl_request($_curl_setopt_array = null, $_function_name = '') {
    if (is_null($_curl_setopt_array))
      throw new Exception(__CLASS__ . '::curl_request&nbsp;:</br>' . __('Paramètres insuffisants.', __FILE__));
    if (config::byKey('login', __CLASS__, '') === '' || config::byKey('password', __CLASS__, '') === '')
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Les informations de connexions doivent être renseignées dans la configuration du plugin Klereo.', __FILE__));
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
      throw new Exception(__CLASS__ . '::' . $_function_name . '&nbsp;:</br>' . __('Echec de la requête&nbsp;: ', __FILE__) . (isset($response['detail']) ? $response['detail'] : __('pas de détail retourné.', __FILE__)));
    log::add('klereo', 'debug', __CLASS__ . '::' . $_function_name . ' / ' . __FUNCTION__ . ' return OK');
    return array($header, $response);
  }
  
  /* Eventuellement appelée par plugins/klereo/core/ajax/klereo.ajax.php
  public static function reinit() { }
  */
  
  static function getJwtToken() {
    $config_jwt_login_dt = config::byKey('jwt::login_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+55 minutes ' . $config_jwt_login_dt);
    //$expire_dt = strtotime('+1 minute ' . $config_jwt_login_dt); // DEBUG
    if (strtotime(self::now()) >= $expire_dt || config::byKey('jwt::Authorization', __CLASS__, '') === '') {
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
  
  static function getIndex() {
    $config_getIndex_dt = config::byKey('getIndex_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+24 hours ' . $config_getIndex_dt);
    //$expire_dt = strtotime('+1 minute ' . $config_getIndex_dt); // DEBUG
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
    if (strtotime(self::now()) >= $expire_dt || config::byKey('getIndex', __CLASS__, '') === '') {
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
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception de la liste des bassins.', __FILE__));
    }
    
    return config::byKey('getIndex', __CLASS__);
  }
  
  static function getPools() {
    $getIndex = self::getIndex();
    $getPools = array();
    foreach ($getIndex as $pool)
      $getPools[$pool['idSystem']] = $pool['poolNickname'];
    return $getPools;
  }
  
  static function actualizeValues() {
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / Start');
    foreach (self::byType('klereo') as $eqKlereo) { // boucle sur les équipements
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $eqKlereo->getName() = ' . $eqKlereo->getName());
      if ($eqKlereo->getIsEnable() && $eqKlereo->getConfiguration('eqPoolId', '') != '') {
        $probes = $eqKlereo->getProbesInfos();
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $probes = ' . json_encode($probes));
        
        foreach ($probes as $name => $config) {
          $filteredCmd = $eqKlereo->getCmd(null, $name . '::filtered');
          if ($filteredCmd) {
            $filteredCmd->adjustMinMax($config['filteredValue'], $config['filteredValue']);
            $eqKlereo->checkAndUpdateCmd($filteredCmd, $config['filteredValue']);
          }
          $directCmd = $eqKlereo->getCmd(null, $name . '::direct');
          if ($directCmd) {
            $directCmd->adjustMinMax($config['directValue'], $config['directValue']);
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
        
        $Filtration_TotalTime = $eqKlereo->getCmd(null, 'Filtration_TotalTime');
        if ($Filtration_TotalTime) {
          $totalTime = $details['params']['Filtration_TotalTime'] / 3600;
          $Filtration_TotalTime->adjustMinMax($totalTime, $totalTime);
          $eqKlereo->checkAndUpdateCmd($Filtration_TotalTime, $totalTime);
        }
        $PHMinus_Today = $eqKlereo->getCmd(null, 'PHMinus_Today');
        if ($PHMinus_Today) {
          $totalPHMinus = $details['params']['PHMinus_TodayTime'] * $details['params']['PHMinus_Debit'] / 36;
          $PHMinus_Today->adjustMinMax($totalPHMinus, $totalPHMinus);
          $eqKlereo->checkAndUpdateCmd($PHMinus_Today, $totalPHMinus);
        }
        $PHMinus_Total = $eqKlereo->getCmd(null, 'PHMinus_Total');
        if ($PHMinus_Total) {
          $totalPHMinus = $details['params']['PHMinus_TotalTime'] * $details['params']['PHMinus_Debit'] / 36000;
          $PHMinus_Total->adjustMinMax($totalPHMinus, $totalPHMinus);
          $eqKlereo->checkAndUpdateCmd($PHMinus_Total, $totalPHMinus);
        }
        $Chlore_Today = $eqKlereo->getCmd(null, 'Chlore_Today');
        if ($Chlore_Today) {
          $totalChlore = $details['params']['ElectroChlore_TodayTime'] * $details['params']['Chlore_Debit'] / 36;
          $Chlore_Today->adjustMinMax($totalChlore, $totalChlore);
          $eqKlereo->checkAndUpdateCmd($Chlore_Today, $totalChlore);
        }
        $Chlore_Total = $eqKlereo->getCmd(null, 'Chlore_Total');
        if ($Chlore_Total) {
          $totalChlore = $details['params']['ElectroChlore_TotalTime'] * $details['params']['Chlore_Debit'] / 36000;
          $Chlore_Total->adjustMinMax($totalChlore, $totalChlore);
          $eqKlereo->checkAndUpdateCmd($Chlore_Total, $totalChlore);
        }
        $Filtration_TodayTime = $eqKlereo->getCmd(null, 'Filtration_TodayTime');
        if ($Filtration_TodayTime) {
          $Filtration_TodayTime->adjustMinMax(0, 24);
          $eqKlereo->checkAndUpdateCmd($Filtration_TodayTime, $details['params']['Filtration_TodayTime'] / 3600);
        }
        $Chauff_TotalTime = $eqKlereo->getCmd(null, 'Chauff_TotalTime');
        if ($Chauff_TotalTime) {
          $totalTime = $details['params']['Chauff_TotalTime'] / 3600;
          $Chauff_TotalTime->adjustMinMax($totalTime, $totalTime);
          $eqKlereo->checkAndUpdateCmd($Chauff_TotalTime, $totalTime);
        }
        $Chauff_TodayTime = $eqKlereo->getCmd(null, 'Chauff_TodayTime');
        if ($Chauff_TodayTime) {
          $Chauff_TodayTime->adjustMinMax(0, 24);
          $eqKlereo->checkAndUpdateCmd($Chauff_TodayTime, $details['params']['Chauff_TodayTime'] / 3600);
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
          elseif ($details['access'] == 16)
            $accessValue = __('Utilisateur avancé', __FILE__);
          elseif ($details['access'] == 20)
            $accessValue = __('Piscinier (pro)', __FILE__);
          elseif ($details['access'] > 20)
            $accessValue = __('Accès Klereo', __FILE__);
          
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
            0 => 'Gamme 5g/h',
            1 => 'Gamme 2g/h'
          );
          $eqKlereo->checkAndUpdateCmd($isLowSalt, $isLowSalt_arr[$pool['isLowSalt']]);
        }
        $alertCount = $eqKlereo->getCmd(null, 'alertCount');
        if ($alertCount) {
          $alert_count = $pool['alertCount'];
          $alertCount->adjustMinMax(0, $alert_count);
          $eqKlereo->checkAndUpdateCmd($alertCount, $alert_count);
        }
        $alerts = $eqKlereo->getCmd(null, 'alerts');
        if ($alerts) {
          $alerts_arr = array(
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
          $alerts_value = '';
          foreach ($pool['alerts'] as $pool_alert) {
            $alert_code = $pool_alert['code'];
            $alert_param = $pool_alert['param'];
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
            } elseif (in_array($alert_code, array(13, 14))) { // param = DebitID
              $param_text = __('débit', __FILE__) . ' ' . strval($alert_param);
            } elseif ($alert_code == 35) { // param = OutID
              $param_text = __('sortie', __FILE__) . ' ' . strval($alert_param);
            } elseif ($alert_code == 40) { // param = BSVError
              $param_text = __('BSVError', __FILE__) . ' ' . strval($alert_param);
            } elseif ($alert_code == 40) { // param = ComId
              $param_text = __('Communication', __FILE__) . ' ' . strval($alert_param);
            } elseif (in_array($alert_code, array(50, 51, 52, 54))) { // param = ErrCodeEX, -PX, -FX
              $param_text = __('code d\'erreur', __FILE__) . ' ' . strval($alert_param);
            } elseif ($alert_code == 53) { // param = PumpID
              $param_text = __('Pompe numéro', __FILE__) . ' ' . strval($alert_param);
            }
            if ($alerts_value != '')
              $alerts_value += ' || ';
            
            $alerts_value .= $alerts_arr[$alert_code] . (($param_text != '') ? ' - ' . $param_text : '');
          }
          $eqKlereo->checkAndUpdateCmd($alerts, $alerts_value);
          
          foreach ($details['outs'] as $out) {
            $infos = self::getOutInfo($out['index']);
            $cmd = $eqKlereo->getCmd(null, $infos[0]);
            $eqKlereo->checkAndUpdateCmd($cmd, $out['status']);
          }
        }
      }
    }
  }
  
  static function getOutInfo($index = null) {
    $logicalIds = array(
      0 =>  'lighting',
      1 =>  'filtration',
      2 =>  'pH_adjuster',
      3 =>  'disinfectant',
      4 =>  'heating',
      5 =>  'auxiliary_1',
      6 =>  'auxiliary_2',
      7 =>  'auxiliary_3',
      8 =>  'flocculant',
      9 =>  'auxiliary_4',
      10 => 'auxiliary_5',
      11 => 'auxiliary_6',
      12 => 'auxiliary_7',
      13 => 'auxiliary_8',
      14 => 'auxiliary_9',
      15 => 'hybride_disinfectant'
    );
    $names = array(
      0 =>  __('Eclairage', __FILE__),
      1 =>  __('Filtration', __FILE__),
      2 =>  __('Correcteur pH', __FILE__),
      3 =>  __('Désinfectant', __FILE__),
      4 =>  __('Chauffage', __FILE__),
      5 =>  __('Auxiliaire 1', __FILE__),
      6 =>  __('Auxiliaire 2', __FILE__),
      7 =>  __('Auxiliaire 3', __FILE__),
      8 =>  __('Floculant', __FILE__),
      9 =>  __('Auxiliaire 4', __FILE__),
      10 => __('Auxiliaire 5', __FILE__),
      11 => __('Auxiliaire 6', __FILE__),
      12 => __('Auxiliaire 7', __FILE__),
      13 => __('Auxiliaire 8', __FILE__),
      14 => __('Auxiliaire 9', __FILE__),
      15 => __('Désinfectant hybride', __FILE__)
    );
    if (is_numeric($index))
      return array($logicalIds[$index], $names[$index]);
    else
      return array($logicalIds, $names);
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
        $this->createCmdInfo($name . '::filtered', $descr[0] . ' : ' . __('mesure en filtration', __FILE__), 'numeric', $order, $config['minValue'], $config['maxValue'], $descr[1]);
        $this->createCmdInfo($name . '::direct', $descr[0] . ' : ' . __('mesure instantanée', __FILE__), 'numeric', $order, $config['minValue'], $config['maxValue'], $descr[1]);
      }
      
      $details = $this->getPoolDetails();
      
      $this->createCmdInfo('Filtration_TodayTime', __('Temps de filtration jour', __FILE__), 'numeric', $order, 0, 24, 'h');
      $this->createCmdInfo('Filtration_TotalTime', __('Temps de filtration total', __FILE__), 'numeric', $order, 0, 45000, 'h');
      $this->createCmdInfo('PHMinus_Today', __('Consommation pH-Minus jour', __FILE__), 'numeric', $order, 0, 36, 'ml');
      $this->createCmdInfo('PHMinus_Total', __('Consommation pH-Minus totale', __FILE__), 'numeric', $order, 0, 10, 'l');
      $this->createCmdInfo('Chlore_Today', __('Consommation chlore jour', __FILE__), 'numeric', $order, 0, 36, 'ml');
      $this->createCmdInfo('Chlore_Total', __('Consommation chlore totale', __FILE__), 'numeric', $order, 0, 10, 'l');
      $this->createCmdInfo('Chauff_TodayTime', __('Temps de chauffage jour', __FILE__), 'numeric', $order, 0, 24, 'h');
      $this->createCmdInfo('Chauff_TotalTime', __('Temps de chauffage total', __FILE__), 'numeric', $order, 0, 45000, 'h');
      $this->createCmdInfo('PoolMode', __('Mode de régulation', __FILE__), 'string', $order);
      $this->createCmdInfo('TraitMode', __('Type de désinfectant', __FILE__), 'string', $order);
      $this->createCmdInfo('pHMode', __('Type de correcteur de pH', __FILE__), 'string', $order);
      $this->createCmdInfo('HeaterMode', __('Type de chauffage', __FILE__), 'string', $order);
      $this->createCmdInfo('access', __('Droit d accès', __FILE__), 'string', $order);
      $this->createCmdInfo('ProductIdx', __('Gamme de produit', __FILE__), 'string', $order);
      $this->createCmdInfo('PumpType', __('Type de pompe de filtration', __FILE__), 'string', $order);
      $this->createCmdInfo('isLowSalt', __('Gamme d électrolyseur', __FILE__), 'string', $order);
      $this->createCmdInfo('alertCount', __('Nombre d alertes', __FILE__), 'numeric', $order, 0, 10, '');
      $this->createCmdInfo('alerts', __('Alertes', __FILE__), 'string', $order);
      
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $details[\'outs\'] = ' . json_encode($details['outs']));
      
      foreach ($details['outs'] as $out) {
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $out = ' . json_encode($out));
        
        $infos = self::getOutInfo($out['index']);
        $this->createCmdInfo($infos[0], $infos[1], 'binary', $order);
        if ($out['index'] != 1) {
          if (in_array($out['index'], array(2, 3, 8, 15)) && $details['access'] < 20)
            continue; // Accès pro uniquement pour le correcteur pH (2), le désinfectant (3), le floculant (8) et le désinfectant hybride (15)
          $this->createCmdAction($infos[0] . '_action', $infos[1] . ' ' . __('CMD', __FILE__), 'other', $order, null, null, null, $infos[0]);
          
        } else { // 3 commandes action pour la filtration (1)
          $this->createCmdAction($infos[0] . '_off', $infos[1] . ' ' . __('OFF', __FILE__), 'other', $order);
          $this->createCmdAction($infos[0] . '_on', $infos[1] . ' ' . __('ON', __FILE__), 'other', $order, null, null, null, $infos[0]);
          $this->createCmdAction($infos[0] . '_auto', $infos[1] . ' ' . __('AUTO', __FILE__), 'other', $order);
          
        }
      }
    }
  }
  
  public function postSave() {
    self::actualizeValues();
    
  }
  
  function createCmdInfo($logicalId, $name, $subType, &$order, $min = null, $max = null, $unite = null) {
    $command = $this->getCmd(null, $logicalId);
    if (!is_object($command)) {
      $command = (new klereoCmd)
        ->setLogicalId($logicalId)
        ->setEqLogic_id($this->getId())
        ->setName($name)
        ->setType('info')
        ->setSubType($subType)
        ->setOrder($order++);
      if ($subType === 'numeric') {
        $command->setConfiguration('minValue', $min);
        $command->setConfiguration('maxValue', $max);
        $command->setUnite($unite);
        $command->setConfiguration('historizeRound', 1);
      }
      $command->save();
    }
  }
  
  // TODO
  function createCmdAction($logicalId, $name, $subType, &$order, $min = null, $max = null, $unite = null, $linkedLogicalId = null) {
    $command = $this->getCmd(null, $logicalId);
    if (!is_object($command)) {
      $command = (new klereoCmd)
        ->setLogicalId($logicalId)
        ->setEqLogic_id($this->getId())
        ->setName($name)
        ->setType('action')
        ->setSubType($subType)
        ->setOrder($order++);
      if ($subType === 'slider') {
        $command->setConfiguration('minValue', $min);
        $command->setConfiguration('maxValue', $max);
        $command->setUnite($unite);
      }
      if (!is_null($linkedLogicalId)) {
        $linkedCmd = $this->getCmd(null, $linkedLogicalId);
        $command->setValue($linkedCmd->getId());
      }
      $command->save();
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
  
  * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
  public static function postConfig_<Variable>() {}
  
  * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
  public static function preConfig_<Variable>() {}
  */

  /*   * **********************Getteur Setteur*************************** */
  
  function getPoolDetails() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    $config_getPoolDetails_dt = config::byKey('getPoolDetails_dt::' . strval($eqPoolId), __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime('+9 minutes 50 seconds ' . $config_getPoolDetails_dt);
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
    if (strtotime(self::now()) >= $expire_dt || config::byKey('getPoolDetails::' . strval($eqPoolId), __CLASS__, '') === '') {
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
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception des détails du bassin.', __FILE__));
    }
    
    return config::byKey('getPoolDetails::' . strval($eqPoolId), __CLASS__);
  }
  
  function getProbesInfos() {
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
  
	function getNextOrder() {
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
  
  static function getSensorTypes() {
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
  
  function setOutState($_index, $_state) {
    if (is_null($_index) || is_null($_state))
      return;
    
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_index = *' . var_export($_index, true) . '*');
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_state = *' . var_export($_state, true) . '*');
    
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    $details = $this->getPoolDetails();
    
    foreach ($details['outs'] as &$out) {
      if ($_index == $out['index']) {
        $out['status'] = $_state;
      }
    }
    config::save('getPoolDetails::' . strval($eqPoolId), $details, __CLASS__);
  }
  
  function setOut($_out_index, $_mode, $_state) {
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_out_index = *' . var_export($_out_index, true) . '*');
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_mode = *' . var_export($_mode, true) . '*');
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_state = *' . var_export($_state, true) . '*');
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    if (!in_array($eqPoolId, array_keys(self::getPools())))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de l\'envoi d\'une commande.', __FILE__));
    $details = $this->getPoolDetails();
    if ($_out_index == 4)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Le chauffage ne peut pas être piloté via l\'API.', __FILE__));
    if (in_array($_out_index, array(2, 3, 8, 15)) && $details['access'] < 20)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Pour cette sortie, il faut au minimum un accès "professionnel / piscinier".', __FILE__));
    $valid_index = array();
    foreach ($details['outs'] as $out) {
      $valid_index[] = $out['index'];
    }
    if (!in_array($_out_index, $valid_index))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Index de sortie inconnu.', __FILE__));
    if (($_out_index != 4 && !in_array($_mode, array(0, 1, 2, 3, 4, 6, 8, 9))) || ($_out_index == 4 && !in_array($_mode, array(0, 1, 2, 3))))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Mode non pris en charge.', __FILE__));
    if (!in_array($_state, array(0, 1, 2)))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Etat de la sortie non pris en charge.', __FILE__));
    
    $post_data = array(
      'poolID'    => $eqPoolId,
      'outIdx'    => $_out_index,
      'newMode'   => $_mode,
      'newState'  => $_state,
      'comMode'   => 1
    );
    $curl_setopt_array = array(
      CURLOPT_URL         => self::$_API_ROOT . 'SetOut.php',
      CURLOPT_POST        => true,
      CURLOPT_HTTPHEADER  => array(
        'User-Agent: ' . self::$_USER_AGENT,
        self::getJwtToken()
      ),
      CURLOPT_POSTFIELDS  => $post_data
    );
    $response = self::curl_request($curl_setopt_array, __FUNCTION__);
    
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $response = *' . var_export($response, true) . '*');
    
    $body = $response[1];
    if (isset($body['response']) && is_array($body['response'])) {
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $body[\'response\'] = *' . var_export($body['response'], true) . '*');
      $cmdID = $body['response'][0]['cmdID'];
      return $cmdID;
      
    } else
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de l\'envoi de la commande.', __FILE__));
    
  }
  
  function waitCommand($_cmd_id) {
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_cmd_id = *' . var_export($_cmd_id, true) . '*');
    
    $post_data = array(
      'cmdID' => $_cmd_id
    );
    $curl_setopt_array = array(
      CURLOPT_URL         => self::$_API_ROOT . 'WaitCommand.php',
      CURLOPT_POST        => true,
      CURLOPT_HTTPHEADER  => array(
        'User-Agent: ' . self::$_USER_AGENT,
        self::getJwtToken()
      ),
      CURLOPT_POSTFIELDS  => $post_data
    );
    $response = self::curl_request($curl_setopt_array, __FUNCTION__);
    
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $response = *' . var_export($response, true) . '*');
    
    $body = $response[1];
    if (isset($body['response']) && is_array($body['response'])) {
      $status = $body['response']['status'];
      return $status;
      
    } else
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la vérification de la commande.', __FILE__));
    
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
    
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' *** execute ***: ' . var_export($_option, true));
    
    if ($this->getType() != 'action')
      return;
    
    $eqKlereo = $this->getEqLogic();
    $outInfos = klereo::getOutInfo();
    $logicalIds = $outInfos[0];
    //$names = $outInfos[1];
    
    $logicalId = $this->getLogicalId();
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $logicalId: ' . var_export($logicalId, true));
    
    $shortLogicalId = '';
    $is_filtration = false;
    $filtration_cmd = '';
    
    // 'normal' action
    if (substr($logicalId, -7) === '_action') {
      $shortLogicalId = substr($logicalId, 0, -7);
      
    // filtration
    } else {
      $is_filtration = true;
      $filtration_cmd = substr($logicalId, 11);
      // filtration off
      if (substr($logicalId, -4) === '_off') {
        $shortLogicalId = substr($logicalId, 0, -4);
        
      // filtration on
      } elseif (substr($logicalId, -3) === '_on') {
        $shortLogicalId = substr($logicalId, 0, -3);
        
      // filtration auto
      } elseif (substr($logicalId, -5) === '_auto') {
        $shortLogicalId = substr($logicalId, 0, -5);
        
      }
    }
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $shortLogicalId: ' . var_export($shortLogicalId, true));
    
    $cmdInfo = $eqKlereo->getCmd(null, $shortLogicalId);
    $cmdInfoValue = $cmdInfo->execCmd();
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $cmdInfoValue: ' . var_export($cmdInfoValue, true));
    
    $outInfoIndex = array_keys($logicalIds, $shortLogicalId)[0];
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $outInfoIndex: ' . var_export($outInfoIndex, true));
    
    if ($is_filtration) {
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $filtration_cmd: ' . var_export($filtration_cmd, true));
      
      switch ($filtration_cmd) {
        case 'off':
          
          break;
        case 'on':
          
          break;
        case 'auto':
          
          break;
      }
      
    } else {
      $newState = 1 - intval($cmdInfoValue);
      $cmdID = $eqKlereo->setOut($outInfoIndex, 0, $newState);
      $status = $eqKlereo->waitCommand($cmdID);
      if ($status == 9) {
        $eqKlereo->checkAndUpdateCmd($cmdInfo, $newState);
        $eqKlereo->setOutState($outInfoIndex, $newState);
      }
    }
    
  }

//  public function postInsert() {}
//  public function postRemove() {}
//  public function postSave() {}
  
  // Fonction exécutée automatiquement avant la sauvegarde de la commande (création ou mise à jour)
  // La levée d'une exception invalide la sauvegarde
  //public function preSave() { }
  
  public function adjustMinMax($min = null, $max = null) {
    if ($min !== null)
      $this->setConfiguration('minValue', min($min, $this->getConfiguration('minValue')));
    if ($max !== null)
      $this->setConfiguration('maxValue', max($max, $this->getConfiguration('maxValue')));
    $this->save();
  }
  
  /*   * **********************Getteur Setteur*************************** */
}

?>