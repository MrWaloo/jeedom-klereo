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

  public static $_version = '0.4 beta';
  
  static $_WEB_VERSION = '389-W';
  static $_API_ROOT = 'https://connect.klereo.fr/php/';
  static $_USER_AGENT = 'Jeedom plugin';
  
  // Time between 2 actualizations (compatible with strtotime())
  static $_ACTUALIZE_TIME_JWT = '+55 minutes';
  static $_ACTUALIZE_TIME_GETINDEX = '+3 hours 55 minutes'; // DEBUG: '+24 hours' /// '+1 minute'
  static $_ACTUALIZE_TIME_GETPOOLDETAILS = '+9 minutes 50 seconds';
  
  /*   * ***********************Methode static*************************** */
  
 /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  
   * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
   public static function cron5() {}
   */

  // Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {
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
  
  public static function reinit() {
    $configKeys = config::searchKey('getPoolDetails', __CLASS__);
    foreach ($configKeys as $match) {
      if ($match['plugin'] != __CLASS__)
        continue;
      config::remove($match['key'], __CLASS__);
    }
    config::remove('getIndex', __CLASS__);
    config::remove('getIndex_dt', __CLASS__);
    config::remove('jwt::Authorization', __CLASS__);
    config::remove('jwt::login_dt', __CLASS__);
    
    $klereoPlugin = plugin::byId(__CLASS__);
    $eqLogics = self::byType($klereoPlugin->getId());
    foreach ($eqLogics as $eqLogic)
      $eqLogic->remove();
  }
  
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
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . $_function_name . ' / $page_content = *' . $page_content . '*');
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
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . $_function_name . ' / ' . __FUNCTION__ . ' return OK');
    return array($header, $response);
  }
  
  static function getJwtToken() {
    $config_jwt_login_dt = config::byKey('jwt::login_dt', __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime(self::$_ACTUALIZE_TIME_JWT . ' ' . $config_jwt_login_dt);
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
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $header = *' . $header . '*');
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $body = *' . var_export($body, true) . '*');
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
    $expire_dt = strtotime(self::$_ACTUALIZE_TIME_GETINDEX . ' ' . $config_getIndex_dt);
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
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
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / Start');
    foreach (self::byType('klereo') as $eqKlereo) { // boucle sur les équipements
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $eqKlereo->getName() = ' . $eqKlereo->getName());
      if ($eqKlereo->getIsEnable() && $eqKlereo->getConfiguration('eqPoolId', '') != '') {
        $probes = $eqKlereo->getProbesInfos();
        log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $probes = ' . var_export($probes, true));
        
        foreach ($probes as $probe) {
          $filteredCmd = $eqKlereo->getCmd('info', $probe['logicalId'] . '_filtered');
          if ($filteredCmd) {
            $filteredCmd->adjustMinMax(floor($probe['filteredValue']), ceil($probe['filteredValue']));
            $eqKlereo->checkAndUpdateCmd($filteredCmd, $probe['filteredValue']);
          }
          $directCmd = $eqKlereo->getCmd('info', $probe['logicalId'] . '_direct');
          if ($directCmd) {
            $directCmd->adjustMinMax(floor($probe['directValue']), ceil($probe['directValue']));
            $eqKlereo->checkAndUpdateCmd($directCmd, $probe['directValue']);
          }
        }
        
        $details = $eqKlereo->getPoolDetails();
        $pool_id = $details['idSystem'];
        //$poolNickname = $details['poolNickname'];
        
        $getIndex = self::getIndex();
        $pool = null;
        foreach ($getIndex as $pool_info) {
          if ($pool_info['idSystem'] == $pool_id) {
            $pool = $pool_info;
            break;
          }
        }
        
        $Filtration_TodayTime = $eqKlereo->getCmd('info', 'Filtration_TodayTime');
        if ($Filtration_TodayTime) {
          $totalTime = $details['params']['Filtration_TodayTime'] / 3600;
          $Filtration_TodayTime->adjustMinMax(floor($totalTime), ceil($totalTime));
          $eqKlereo->checkAndUpdateCmd($Filtration_TodayTime, $totalTime);
        }
        $Filtration_TotalTime = $eqKlereo->getCmd('info', 'Filtration_TotalTime');
        if ($Filtration_TotalTime) {
          $totalTime = $details['params']['Filtration_TotalTime'] / 3600;
          $Filtration_TotalTime->adjustMinMax(floor($totalTime), ceil($totalTime));
          $eqKlereo->checkAndUpdateCmd($Filtration_TotalTime, $totalTime);
        }
        $PHMinus_Today = $eqKlereo->getCmd('info', 'PHMinus_Today');
        if ($PHMinus_Today) {
          $totalPHMinus = $details['params']['PHMinus_TodayTime'] * $details['params']['PHMinus_Debit'] / 36;
          $PHMinus_Today->adjustMinMax(floor($totalPHMinus), ceil($totalPHMinus));
          $eqKlereo->checkAndUpdateCmd($PHMinus_Today, $totalPHMinus);
        }
        $PHMinus_Total = $eqKlereo->getCmd('info', 'PHMinus_Total');
        if ($PHMinus_Total) {
          $totalPHMinus = $details['params']['PHMinus_TotalTime'] * $details['params']['PHMinus_Debit'] / 36000;
          $PHMinus_Total->adjustMinMax(floor($totalPHMinus), ceil($totalPHMinus));
          $eqKlereo->checkAndUpdateCmd($PHMinus_Total, $totalPHMinus);
        }
        $Chlore_Today = $eqKlereo->getCmd('info', 'Chlore_Today');
        if ($Chlore_Today) {
          $totalChlore = $details['params']['ElectroChlore_TodayTime'] * $details['params']['Chlore_Debit'] / 36;
          $Chlore_Today->adjustMinMax(floor($totalChlore), ceil($totalChlore));
          $eqKlereo->checkAndUpdateCmd($Chlore_Today, $totalChlore);
        }
        $Chlore_Total = $eqKlereo->getCmd('info', 'Chlore_Total');
        if ($Chlore_Total) {
          $totalChlore = $details['params']['ElectroChlore_TotalTime'] * $details['params']['Chlore_Debit'] / 36000;
          $Chlore_Total->adjustMinMax(floor($totalChlore), ceil($totalChlore));
          $eqKlereo->checkAndUpdateCmd($Chlore_Total, $totalChlore);
        }
        $Filtration_TodayTime = $eqKlereo->getCmd('info', 'Filtration_TodayTime');
        if ($Filtration_TodayTime) {
          $Filtration_TodayTime->adjustMinMax(0, 24);
          $eqKlereo->checkAndUpdateCmd($Filtration_TodayTime, $details['params']['Filtration_TodayTime'] / 3600);
        }
        $Chauff_TotalTime = $eqKlereo->getCmd('info', 'Chauff_TotalTime');
        if ($Chauff_TotalTime) {
          $totalTime = $details['params']['Chauff_TotalTime'] / 3600;
          $Chauff_TotalTime->adjustMinMax(floor($totalTime), ceil($totalTime));
          $eqKlereo->checkAndUpdateCmd($Chauff_TotalTime, $totalTime);
        }
        $Chauff_TodayTime = $eqKlereo->getCmd('info', 'Chauff_TodayTime');
        if ($Chauff_TodayTime) {
          $Chauff_TodayTime->adjustMinMax(0, 24);
          $eqKlereo->checkAndUpdateCmd($Chauff_TodayTime, $details['params']['Chauff_TodayTime'] / 3600);
        }
        $PoolMode = $eqKlereo->getCmd('info', 'PoolMode');
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
        $TraitMode = $eqKlereo->getCmd('info', 'TraitMode');
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
        $pHMode = $eqKlereo->getCmd('info', 'pHMode');
        if ($pHMode) {
          $pHMode_arr = array(
            0 => __('Aucun', __FILE__),
            1 => __('pH-Minus', __FILE__),
            2 => __('pH-Plus', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($pHMode, $pHMode_arr[$pool['RegulModes']['pHMode']]);
        }
        $HeaterMode = $eqKlereo->getCmd('info', 'HeaterMode');
        if ($HeaterMode) {
          $HeaterMode_arr = array(
            0 => __('Aucun', __FILE__),
            1 => __('PAC ou réchauffeur ON/OFF', __FILE__),
            2 => __('Pompe à chaleur EasyTherm', __FILE__),
            3 => __('Chauffage ON/OFF sans consigne', __FILE__),
            4 => __('Pompe à chaleur KlereoTherm', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($HeaterMode, $HeaterMode_arr[$pool['RegulModes']['HeaterMode']]);
        }
        $access = $eqKlereo->getCmd('info', 'access');
        if ($access) {
          $accessValue = __('Valeur inconnue', __FILE__);
          if ($details['access'] == 5)
            $accessValue = __('Lecture seule', __FILE__);
          elseif ($details['access'] == 10)
            $accessValue = __('Client final', __FILE__);
          elseif ($details['access'] == 16)
            $accessValue = __('Utilisateur avancé', __FILE__);
          elseif ($details['access'] == 20)
            $accessValue = __('Pisciniste (pro)', __FILE__);
          elseif ($details['access'] > 20)
            $accessValue = __('Accès Klereo', __FILE__);
          
          $eqKlereo->checkAndUpdateCmd($access, $accessValue);
        }
        $ProductIdx = $eqKlereo->getCmd('info', 'ProductIdx');
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
        $PumpType = $eqKlereo->getCmd('info', 'PumpType');
        if ($PumpType) {
          $PumpType_arr = array(
            0 => __('Pompe générique (pilotée par contacteur)', __FILE__),
            1 => __('Pompe KlereoFlô (pilotée par bus RS485)', __FILE__),
            2 => __('Pompe Pentair (pilotée par bus)', __FILE__),
            7 => __('Aucune pompe', __FILE__)
          );
          $eqKlereo->checkAndUpdateCmd($PumpType, $PumpType_arr[$pool['PumpType']]);
        }
        $isLowSalt = $eqKlereo->getCmd('info', 'isLowSalt');
        if ($isLowSalt) {
          $isLowSalt_arr = array(
            0 => 'Gamme 5g/h',
            1 => 'Gamme 2g/h'
          );
          $eqKlereo->checkAndUpdateCmd($isLowSalt, $isLowSalt_arr[$pool['isLowSalt']]);
        }
        $alertCount = $eqKlereo->getCmd('info', 'alertCount');
        if ($alertCount) {
          $alert_count = count($pool['alerts']);
          $alertCount->adjustMinMax(0, $alert_count);
          $eqKlereo->checkAndUpdateCmd($alertCount, $alert_count);
        }
        $alerts = $eqKlereo->getCmd('info', 'alerts');
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
            58  => __('Mauvaise configuration de la pompe', __FILE__),
            59  => __('Problème de communication avec le module de mesure de consommation', __FILE__),
            60  => __('Écran de la pompe à vitesse variable verrouillé', __FILE__)
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
            
            if (in_array($alert_code, array_keys($alerts_arr)))
              $alerts_value .= $alerts_arr[$alert_code] . (($param_text != '') ? ' - ' . $param_text : '');
            else
              $alerts_value .= __('Code alerte inconnu par le plugin&nbsp;:', __FILE__) . ' ' . strval($alert_code);
          }
          $eqKlereo->checkAndUpdateCmd($alerts, $alerts_value);
          
          foreach (array('ConsigneEau', 'ConsignePH', 'ConsigneRedox', 'ConsigneChlore') as $consigne) {
            $cmd = $eqKlereo->getCmd('info', $consigne);
            if ($cmd && array_key_exists($consigne, $details['params'])) {
              $cmd->adjustMinMax(floor($details['params'][$consigne]), ceil($details['params'][$consigne]));
              $eqKlereo->checkAndUpdateCmd($cmd, $details['params'][$consigne]);
            }
          }
          
          foreach ($details['outs'] as $out) {
            if (is_null($out['type']))
              continue;
            
            [$outN, $name] = $eqKlereo->getOutInfo($out['index']);
            $cmd = $eqKlereo->getCmd('info', $outN);
            $eqKlereo->checkAndUpdateCmd($cmd, $out['status']);
            if ($out['index'] == 1) { // Filtration (1)
              $cmdOff = $eqKlereo->getCmd('info', $outN . '_off');
              if ($cmdOff->execCmd() == '') // filtration_off est gérée par cmd->execute(), ici on initialise juste la valeur de la commande
                $eqKlereo->checkAndUpdateCmd($cmdOff, 0);
              if ($details['PumpMaxSpeed'] > 1) { // Analogic pump
                $cmdValue = $eqKlereo->getCmd('info', $outN . '_value');
                $eqKlereo->checkAndUpdateCmd($cmdValue, intval($out['status']));
              } else {
                $cmdOn = $eqKlereo->getCmd('info', $outN . '_on');
                $eqKlereo->checkAndUpdateCmd($cmdOn, $out['mode'] == klereoCmd::$_OUT_MODE_MAN && $out['status'] == klereoCmd::$_OUT_STATE_ON);
              }
              $cmdAuto = $eqKlereo->getCmd('info', $outN . '_auto');
              $eqKlereo->checkAndUpdateCmd($cmdAuto, $out['mode'] == klereoCmd::$_OUT_MODE_TIME_SLOTS);
              $cmdRegul = $eqKlereo->getCmd('info', $outN . '_regulation');
              $eqKlereo->checkAndUpdateCmd($cmdRegul, $out['mode'] == klereoCmd::$_OUT_MODE_REGUL);
              
            } elseif ($out['index'] == 4) { // Chauffage (4)
              $cmdOff = $eqKlereo->getCmd('info', $outN . '_off');
              if ($cmdOff->execCmd() == '') // heating_off est gérée par cmd->execute(), ici on initialise juste la valeur de la commande
                $eqKlereo->checkAndUpdateCmd($cmdOff, 0);
              $cmdRegul = $eqKlereo->getCmd('info', $outN . '_regulation');
              $eqKlereo->checkAndUpdateCmd($cmdRegul, $out['mode']);
              
            }  elseif (in_array($out['index'], array(0, 5, 6, 7, 9, 10, 11, 12, 13, 14))) { // Eclairage (0) ou AuxN (5, 6, 7, 9, 10, 11, 12, 13, 14)
              $cmdOff = $eqKlereo->getCmd('info', $outN . '_off');
              if ($cmdOff->execCmd() == '') // out_nnn_off est gérée par cmd->execute(), ici on initialise juste la valeur de la commande
                $eqKlereo->checkAndUpdateCmd($cmdOff, 0);
              $cmdOn = $eqKlereo->getCmd('info', $outN . '_on');
              $eqKlereo->checkAndUpdateCmd($cmdOn, $out['mode'] == klereoCmd::$_OUT_MODE_MAN && $out['status'] == klereoCmd::$_OUT_STATE_ON);
              $cmdOffDelay = $eqKlereo->getCmd('info', 'offDelay_' . $outN);
              $eqKlereo->checkAndUpdateCmd($cmdOffDelay, $out['offDelay']);
              $cmdTimer = $eqKlereo->getCmd('info', $outN . '_timer');
              $eqKlereo->checkAndUpdateCmd($cmdTimer, $out['mode'] == klereoCmd::$_OUT_MODE_TIMER);
              $cmdTimeSlots = $eqKlereo->getCmd('info', $outN . '_timeSlots');
              $eqKlereo->checkAndUpdateCmd($cmdTimeSlots, $out['mode'] == klereoCmd::$_OUT_MODE_TIME_SLOTS);
              
            }
          }
        }
      }
    }
  }
  
  static function getSensorTypes() {
    return array(
      0   =>  __('Température local technique', __FILE__) . ';°C',
      1   =>  __('Température air', __FILE__) . ';°C',
      2   =>  __('Niveau d\'eau', __FILE__) . ';%',
      3   =>  __('pH seul', __FILE__) . ';pH',
      4   =>  __('Redox seul', __FILE__) . ';mV',
      5   =>  __('Température eau', __FILE__) . ';°C',
      6   =>  __('Pression filtre', __FILE__) . ';mbar',
      10  =>  __('Générique', __FILE__) . ';%',
      11  =>  __('Débit', __FILE__) . ';m³/h',
      12  =>  __('Niveau bidon', __FILE__) . ';%',
      13  =>  __('Position volet / couverture', __FILE__) . ';%',
      14  =>  __('Chlore', __FILE__) . ';mg/L'
    );
  }
  
  /*   * *********************Méthodes d'instance************************* */
  
  // Fonction exécutée automatiquement avant la suppression de l'équipement
  //public function preRemove() {}

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    config::remove('getPoolDetails_dt::' . $eqPoolId, __CLASS__);
    config::remove('getPoolDetails::' . $eqPoolId, __CLASS__);
  }
  
  // Fonction exécutée automatiquement avant la sauvegarde de l'équipement (création ou mise à jour)
  // La levée d'une exception invalide la sauvegarde
  public function preSave() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $eqPoolId = ' . $eqPoolId);
    if ($eqPoolId === '')
      return true;
    
    $probes = $this->getProbesInfos();
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $probes = ' . var_export($probes, true));
    
    $order = $this->getNextOrder();
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $order = ' . var_export($order, true));
    $this->createCmdAction('refresh', __('Rafraichissement', __FILE__), 'other', $order);
    
    foreach ($probes as $probe) {
      $descr = explode(';', $probe['description']);
      $this->createCmdInfo($probe['logicalId'] . '_filtered', $descr[0] . ' : ' . __('mesure en filtration', __FILE__), 'numeric', $order, $probe['minValue'], $probe['maxValue'], $descr[1]);
      $this->createCmdInfo($probe['logicalId'] . '_direct', $descr[0] . ' : ' . __('mesure instantanée', __FILE__), 'numeric', $order, $probe['minValue'], $probe['maxValue'], $descr[1]);
    }
    
    $details = $this->getPoolDetails();
    $pool_id = $details['idSystem'];
    
    $getIndex = self::getIndex();
    $pool = null;
    foreach ($getIndex as $pool_info) {
      if ($pool_info['idSystem'] == $pool_id) {
        $pool = $pool_info;
        break;
      }
    }
    
    if (isset($details['params'])) {
      if (isset($details['params']['Filtration_TodayTime']))
        $this->createCmdInfo('Filtration_TodayTime', __('Temps de filtration jour', __FILE__), 'numeric', $order, 0, 24, 'h');
      if (isset($details['params']['Filtration_TotalTime']))
        $this->createCmdInfo('Filtration_TotalTime', __('Temps de filtration total', __FILE__), 'numeric', $order, 0, 5000, 'h');
      if (isset($pool['RegulModes']['pHMode']) && $pool['RegulModes']['pHMode'] > 0) {
        if (isset($details['params']['PHMinus_TodayTime']))
          $this->createCmdInfo('PHMinus_Today', __('Consommation pH-Minus jour', __FILE__), 'numeric', $order, 0, 36, 'mL');
        if (isset($details['params']['PHMinus_TotalTime']))
          $this->createCmdInfo('PHMinus_Total', __('Consommation pH-Minus totale', __FILE__), 'numeric', $order, 0, 20, 'L');
      }
      if (isset($details['params']['ElectroChlore_TodayTime']))
        $this->createCmdInfo('Chlore_Today', __('Consommation chlore jour', __FILE__), 'numeric', $order, 0, 36, 'mL');
      if (isset($details['params']['ElectroChlore_TotalTime']))
        $this->createCmdInfo('Chlore_Total', __('Consommation chlore totale', __FILE__), 'numeric', $order, 0, 10, 'L');
      if (isset($pool['RegulModes']['HeaterMode']) && $pool['RegulModes']['HeaterMode'] > 0) {
        if (isset($details['params']['Chauff_TodayTime']))
          $this->createCmdInfo('Chauff_TodayTime', __('Temps de chauffage jour', __FILE__), 'numeric', $order, 0, 24, 'h');
        if (isset($details['params']['Chauff_TotalTime']))
          $this->createCmdInfo('Chauff_TotalTime', __('Temps de chauffage total', __FILE__), 'numeric', $order, 0, 5000, 'h');
      }
    }
    if (isset($pool['RegulModes'])) {
      if (isset($pool['RegulModes']['PoolMode']))
        $this->createCmdInfo('PoolMode', __('Mode de régulation', __FILE__), 'string', $order);
      if (isset($pool['RegulModes']['TraitMode']))
        $this->createCmdInfo('TraitMode', __('Type de désinfectant', __FILE__), 'string', $order);
      if (isset($pool['RegulModes']['pHMode']))
        $this->createCmdInfo('pHMode', __('Type de correcteur de pH', __FILE__), 'string', $order);
      if (isset($pool['RegulModes']['HeaterMode']))
        $this->createCmdInfo('HeaterMode', __('Type de chauffage', __FILE__), 'string', $order);
    }
    $this->createCmdInfo('access', __('Droit d accès', __FILE__), 'string', $order);
    if (isset($details['ProductIdx']))
      $this->createCmdInfo('ProductIdx', __('Gamme de produit', __FILE__), 'string', $order);
    if (isset($pool['PumpType']))
      $this->createCmdInfo('PumpType', __('Type de pompe de filtration', __FILE__), 'string', $order);
    if (isset($pool['isLowSalt']))
      $this->createCmdInfo('isLowSalt', __('Gamme d électrolyseur', __FILE__), 'string', $order);
    $this->createCmdInfo('alertCount', __('Nombre d alertes', __FILE__), 'numeric', $order, 0, 5, '');
    $this->createCmdInfo('alerts', __('Alertes', __FILE__), 'string', $order);
    
    if (isset($details['params'])) {
      if (isset($pool['RegulModes']['HeaterMode']) && $pool['RegulModes']['HeaterMode'] > 0 && array_key_exists('ConsigneEau', $details['params']) && !in_array($details['params']['ConsigneEau'], array(-1000, -2000))) {
        $this->createCmdInfo('ConsigneEau', __('Lecture consigne chauffage', __FILE__), 'numeric', $order, $details['params']['EauMin'], $details['params']['EauMax'], '°C');
        $this->createCmdAction('ConsigneEau', __('Consigne chauffage', __FILE__), 'slider', $order, $details['params']['EauMin'], $details['params']['EauMax'], '°C', 'ConsigneEau', null, 0.5);
      }
      if ($details['access'] > 16) {
        if (isset($pool['RegulModes']['pHMode']) && $pool['RegulModes']['pHMode'] > 0 && array_key_exists('ConsignePH', $details['params']) && !in_array($details['params']['ConsignePH'], array(-1000, -2000))) {
          $this->createCmdInfo('ConsignePH', __('Lecture consigne pH', __FILE__), 'numeric', $order, $details['params']['pHMin'], $details['params']['pHMax'], 'pH');
          $this->createCmdAction('ConsignePH', __('Ecriture consigne pH', __FILE__), 'slider', $order, $details['params']['pHMin'], $details['params']['pHMax'], 'pH', 'ConsignePH', null, 0.1);
        }
        if (array_key_exists('ConsigneRedox', $details['params']) && array_key_exists('ConsigneRedox', $details['params']) && !in_array($details['params']['ConsigneRedox'], array(-1000, -2000))) {
          $this->createCmdInfo('ConsigneRedox', __('Lecture consigne Redox', __FILE__), 'numeric', $order, $details['params']['OrpMin'], $details['params']['OrpMax'], 'mV');
          $this->createCmdAction('ConsigneRedox', __('Ecriture consigne Redox', __FILE__), 'slider', $order, $details['params']['OrpMin'], $details['params']['OrpMax'], 'mV', 'ConsigneRedox');
        }
        if (array_key_exists('ConsigneChlore', $details['params']) && array_key_exists('ConsigneChlore', $details['params']) && !in_array($details['params']['ConsigneChlore'], array(-1000, -2000))) {
          $this->createCmdInfo('ConsigneChlore', __('Lecture consigne chlore', __FILE__), 'numeric', $order, 0, 5, 'mg/L');
          $this->createCmdAction('ConsigneChlore', __('Ecriture consigne chlore', __FILE__), 'slider', $order, 0, 5, 'mg/L', 'ConsigneChlore', null, 0.1);
        }
      }
    }
    
    foreach ($details['outs'] as $out) {
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ': $out = ' . var_export($out, true));
      if (is_null($out['type']))
        continue;
      
      [$outN, $name] = $this->getOutInfo($out['index']);
      $this->createCmdInfo($outN, $name . ' ' . __('état', __FILE__), 'binary', $order);
      if ($out['index'] == 1) { // Filtration (1)
        $this->createCmdInfo($outN . '_off', $name . ' ' . __('OFF état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_off', $name . ' ' . __('OFF CMD', __FILE__), 'other', $order, null, null, null, $outN . '_off');
        if ($details['PumpMaxSpeed'] > 1) { // Analogic pump
          $this->createCmdInfo($outN . '_value', $name . ' ' . __('Mesure', __FILE__), 'numeric', $order, 0, $details['PumpMaxSpeed'], '');
          $this->createCmdAction($outN . '_value', $name . ' ' . __('Consigne', __FILE__), 'slider', $order, 0, $details['PumpMaxSpeed'], '', $outN . '_value');
        } else { // Pump on/off
          $this->createCmdInfo($outN . '_on', $name . ' ' . __('ON état', __FILE__), 'binary', $order);
          $this->createCmdAction($outN . '_on', $name . ' ' . __('ON CMD', __FILE__), 'other', $order, null, null, null, $outN . '_on');
        }
        $this->createCmdInfo($outN . '_auto', $name . ' ' . __('AUTO état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_auto', $name . ' ' . __('AUTO CMD', __FILE__), 'other', $order, null, null, null, $outN . '_auto');
        $this->createCmdInfo($outN . '_regulation', $name . ' ' . __('Régulation état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_regulation', $name . ' ' . __('Régulation CMD', __FILE__), 'other', $order, null, null, null, $outN . '_regulation');
        
      } elseif ($out['index'] == 4) { // Chauffage (4)
        $this->createCmdInfo($outN . '_off', $name . ' ' . __('OFF état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_off', $name . ' ' . __('OFF CMD', __FILE__), 'other', $order, null, null, null, $outN . '_off');
        $this->createCmdInfo($outN . '_regulation', $name . ' ' . __('Régulation état', __FILE__), 'numeric', $order, 0, 3, '');
        $this->createCmdAction($outN . '_regulation', $name . ' ' . __('Régulation CMD', __FILE__), 'select', $order, null, null, null, $outN . '_regulation',
                                '0|' . __('Arrêt', __FILE__) . ';1|' . __('Automatique', __FILE__) . ';2|' . __('Refroidissement', __FILE__) . ';3|' . __('Chauffage', __FILE__));
        
      } elseif (in_array($out['index'], array(0, 5, 6, 7, 9, 10, 11, 12, 13, 14))) { // Eclairage (0) ou AuxN (5, 6, 7, 9, 10, 11, 12, 13, 14)
        $this->createCmdInfo($outN . '_off', $name . ' ' . __('OFF état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_off', $name . ' ' . __('OFF CMD', __FILE__), 'other', $order, null, null, null, $outN . '_off');
        $this->createCmdInfo($outN . '_on', $name . ' ' . __('ON état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_on', $name . ' ' . __('ON CMD', __FILE__), 'other', $order, null, null, null, $outN . '_on');
        $this->createCmdInfo('offDelay_' . $outN, $name . ' ' . __('Temps minuterie', __FILE__), 'numeric', $order, 1, 600, 'min');
        $this->createCmdAction('offDelay_' . $outN, $name . ' ' . __('Consigne temps minuterie', __FILE__), 'slider', $order, 1, 600, 'min', 'offDelay_' . $outN);
        $this->createCmdInfo($outN . '_timer', $name . ' ' . __('Minuterie état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_timer', $name . ' ' . __('Minuterie CMD', __FILE__), 'other', $order, null, null, null, $outN . '_timer');
        $this->createCmdInfo($outN . '_timeSlots', $name . ' ' . __('Plage état', __FILE__), 'binary', $order);
        $this->createCmdAction($outN . '_timeSlots', $name . ' ' . __('Plage CMD', __FILE__), 'other', $order, null, null, null, $outN . '_timeSlots');
        
      }
    }
  }
  
  public function postSave() {
    self::actualizeValues();
    
  }
  
  function createCmdInfo($logicalId, $name, $subType, &$order, $min = null, $max = null, $unite = null) {
    $command = $this->getCmd('info', $logicalId);
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
  
  function createCmdAction($logicalId, $name, $subType, &$order, $min = null, $max = null, $unite = null, $linkedLogicalId = null, $listValue = null, $sliderStep = null) {
    $command = $this->getCmd('action', $logicalId);
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
        if (!is_null($sliderStep))
          $command->setDisplay('parameters', array('step' => $sliderStep));
      }
      if (!is_null($linkedLogicalId)) {
        $linkedCmd = $this->getCmd('info', $linkedLogicalId);
        $command->setValue($linkedCmd->getId());
      }
      if ($subType === 'select' && !is_null($listValue))
        $command->setConfiguration('listValue', $listValue);
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
  
  function getPoolDetails($_force = false) {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    $config_getPoolDetails_dt = config::byKey('getPoolDetails_dt::' . $eqPoolId, __CLASS__, '0000-01-01 00:00:00');
    $expire_dt = strtotime(self::$_ACTUALIZE_TIME_GETPOOLDETAILS . ' ' . $config_getPoolDetails_dt);
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / strtotime(self::now()) >= $expire_dt = *' . (strtotime(self::now()) >= $expire_dt ? 'true' : 'false') . '*');
    if (strtotime(self::now()) >= $expire_dt || $_force || config::byKey('getPoolDetails::' . $eqPoolId, __CLASS__, '') === '') {
      $post_data = array(
        'poolID'  => intval($eqPoolId),
        'lang'    => substr(translate::getLanguage(), 0, 2)
      );
      $curl_setopt_array = array(
        CURLOPT_URL         => self::$_API_ROOT . 'GetPoolDetails.php',
        CURLOPT_POST        => true,
        CURLOPT_HTTPHEADER  => array(
          'User-Agent: ' . self::$_USER_AGENT,
          self::getJwtToken()
        ),
        CURLOPT_POSTFIELDS  => $post_data
      );
      $response = self::curl_request($curl_setopt_array, __FUNCTION__);
      $body = $response[1];
      if (isset($body['response']) && is_array($body['response'])) {
        $getPoolDetails = $body['response'][0];
        config::save('getPoolDetails_dt::' . $eqPoolId, self::now(), __CLASS__);
        config::save('getPoolDetails::' . $eqPoolId, $getPoolDetails, __CLASS__);
        return $getPoolDetails;
      } else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception des détails du bassin.', __FILE__));
    }
    
    return config::byKey('getPoolDetails::' . $eqPoolId, __CLASS__);
  }
  
  function getOutInfo($_index) {
    $names = array(
      0  => __('Eclairage', __FILE__),
      1  => __('Filtration', __FILE__),
      2  => __('Correcteur pH', __FILE__),
      3  => __('Désinfectant', __FILE__),
      4  => __('Chauffage', __FILE__),
      5  => __('Auxiliaire 1', __FILE__),
      6  => __('Auxiliaire 2', __FILE__),
      7  => __('Auxiliaire 3', __FILE__),
      8  => __('Floculant', __FILE__),
      9  => __('Auxiliaire 4', __FILE__),
      10 => __('Auxiliaire 5', __FILE__),
      11 => __('Auxiliaire 6', __FILE__),
      12 => __('Auxiliaire 7', __FILE__),
      13 => __('Auxiliaire 8', __FILE__),
      14 => __('Auxiliaire 9', __FILE__),
      15 => __('Désinfectant hybride', __FILE__)
    );
    
    $details = $this->getPoolDetails();
    if (isset($details['IORename']) && count($details['IORename']) >= 1) {
      foreach ($details['IORename'] as $ioRename) {
        if ($ioRename['ioType'] == 1) // outs
          $name[$ioRename['ioIndex']] = $ioRename['name'];
      }
    }
    
    return array(sprintf('out_%03d', $_index), $names[$_index]);
  }
  
  function getProbesInfos() {
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    if (!array_key_exists($eqPoolId, self::getPools()))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de la réception des mesures du bassin&nbsp;: bassin inconnu.', __FILE__));
    $details = $this->getPoolDetails();
    $sensor_types = self::getSensorTypes();
    
    $ioRename = array();
    if (isset($details['IORename']) && count($details['IORename']) >= 1) {
      foreach ($details['IORename'] as $ioRen) {
        if ($ioRen['ioType'] == 2) { // probe
          $ioRename[$ioRen['ioIndex']] = $ioRen['name'];
        }
      }
    }
    
    $probes = array();
    foreach ($details['probes'] as $probe) {
      $seuilMin = $probe['seuilMin'];
      $seuilMax = $probe['seuilMax'];
      if (in_array($seuilMin, array(-2000, -1000))) { // -2000: Désactivé; -1000: Inconnu
        if (in_array($seuilMax, array(-2000, -1000))) {
          $seuilMin = floor(min($probe['filteredValue'], $probe['directValue']));
          $seuilMax = ceil(max($probe['filteredValue'], $probe['directValue']));
        } else {
          $seuilMin = floor(min($probe['filteredValue'], $probe['directValue']));
        }
      } else {
        if (in_array($seuilMax, array(-2000, -1000))) {
          $seuilMax = ceil(max($probe['filteredValue'], $probe['directValue']));
        }
      }
      $sensor_type = explode(';', $sensor_types[$probe['type']]);
      $description = $sensor_type[0] . ' (' . $probe['index'] . ')';
      if (array_key_exists($probe['index'], $ioRename))
        $description = $ioRename[$probe['index']];
      
      $probes[] = array(
        'logicalId'     => 'probe_' . $probe['index'],
        'minValue'      => floor(min($seuilMin, $seuilMax, $probe['filteredValue'], $probe['directValue'])),
        'maxValue'      => ceil(max($seuilMin, $seuilMax, $probe['filteredValue'], $probe['directValue'])),
        'filteredValue' => $probe['filteredValue'],
        'directValue'   => $probe['directValue'],
        'description'   => $description . ';' . $sensor_type[1]
      );
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
  
  function setOut($_out_index, $_mode, $_state) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_out_index = *' . var_export($_out_index, true) . '*');
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_mode = *' . var_export($_mode, true) . '*');
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_state = *' . var_export($_state, true) . '*');
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    if (!array_key_exists($eqPoolId, self::getPools()))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Ce bassin n\'est pas rataché à votre compte.', __FILE__));
    $details = $this->getPoolDetails();
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
    if (!is_int($_state) || $_state < 0 || $_state > 7)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Etat de la sortie non pris en charge.', __FILE__));
    
    $post_data = array(
      'poolID'    => intval($eqPoolId),
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
    
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $response = *' . var_export($response, true) . '*');
    
    $body = $response[1];
    if (isset($body['status']) && $body['status'] === 'ok' && isset($body['response']) && is_array($body['response'])) {
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $body[\'response\'] = *' . var_export($body['response'], true) . '*');
      $cmdID = $body['response'][0]['cmdID'];
      return $cmdID;
      
    } else
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de l\'envoi de la commande.', __FILE__));
    
  }
  
  function setParam($_param, $_newValue) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_param = *' . var_export($_param, true) . '*');
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_newValue = *' . var_export($_newValue, true) . '*');
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    if (!array_key_exists($eqPoolId, self::getPools()))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Ce bassin n\'est pas rattaché à votre compte.', __FILE__));
    
    $post_data = array(
      'poolID'    => intval($eqPoolId),
      'paramID'   => $_param,
      'newValue'  => $_newValue,
      'comMode'   => 1
    );
    $curl_setopt_array = array(
      CURLOPT_URL         => self::$_API_ROOT . 'SetParam.php',
      CURLOPT_POST        => true,
      CURLOPT_HTTPHEADER  => array(
        'User-Agent: ' . self::$_USER_AGENT,
        self::getJwtToken()
      ),
      CURLOPT_POSTFIELDS  => $post_data
    );
    $response = self::curl_request($curl_setopt_array, __FUNCTION__);
    
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $response = *' . var_export($response, true) . '*');
    
    $body = $response[1];
    if (isset($body['status']) && $body['status'] === 'ok' && isset($body['response']) && is_array($body['response'])) {
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $body[\'response\'] = *' . var_export($body['response'], true) . '*');
      $cmdID = $body['response'][0]['cmdID'];
      return $cmdID;
      
    } else
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de l\'envoi de la commande.', __FILE__));
    
  }
  
  function setAutoOff($_outIdx, $_offDelay) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_outIdx = *' . var_export($_outIdx, true) . '*');
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_newDelay = *' . var_export($_newDelay, true) . '*');
    $eqPoolId = $this->getConfiguration('eqPoolId', '');
    if ($eqPoolId == '')
      return;
    if (!array_key_exists($eqPoolId, self::getPools()))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Ce bassin n\'est pas rattaché à votre compte.', __FILE__));
    
    $post_data = array(
      'poolID'    => intval($eqPoolId),
      'outIdx'    => $_outIdx,
      'offDelay'  => $_offDelay,
      'comMode'   => 1
    );
    $curl_setopt_array = array(
      CURLOPT_URL         => self::$_API_ROOT . 'SetAutoOff.php',
      CURLOPT_POST        => true,
      CURLOPT_HTTPHEADER  => array(
        'User-Agent: ' . self::$_USER_AGENT,
        self::getJwtToken()
      ),
      CURLOPT_POSTFIELDS  => $post_data
    );
    $response = self::curl_request($curl_setopt_array, __FUNCTION__);
    
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $response = *' . var_export($response, true) . '*');
    
    $body = $response[1];
    if (isset($body['status']) && $body['status'] === 'ok' && isset($body['response']) && is_array($body['response'])) {
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $body[\'response\'] = *' . var_export($body['response'], true) . '*');
      $cmdID = $body['response'][0]['cmdID'];
      return $cmdID;
      
    } else
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:</br>' . __('Erreur lors de l\'envoi de la commande.', __FILE__));
    
  }
  
  function waitCommand($_cmd_id) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_cmd_id = *' . var_export($_cmd_id, true) . '*');
    
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
    
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $response = *' . var_export($response, true) . '*');
    
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
  
  static $_OUT_MODE_MAN         = 0;
  static $_OUT_MODE_TIME_SLOTS  = 1;
  static $_OUT_MODE_TIMER       = 2;
  static $_OUT_MODE_REGUL       = 3;
  static $_OUT_MODE_CLONE       = 4;
  static $_OUT_MODE_SPECIAL     = 5;
  static $_OUT_MODE_TEST        = 6;
  static $_OUT_MODE_BAD         = 7;
  static $_OUT_MODE_PULSE       = 8;
  static $_OUT_MODE_AUTO        = 9;
  
  static $_OUT_STATE_OFF        = 0;
  static $_OUT_STATE_ON         = 1;
  static $_OUT_STATE_AUTO       = 2;
  
  static $_HEAT_MODE_STOP       = 0;
  static $_HEAT_MODE_AUTO       = 1;
  static $_HEAT_MODE_COOLING    = 2;
  static $_HEAT_MODE_HEATING    = 3;

  /*   * ***********************Methode static*************************** */

  /*   * *********************Methode d'instance************************* */

  /*
  * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */
  
  public function execute($_option=array()) {
    
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' / $_option: ' . var_export($_option, true));
    
    if ($this->getType() != 'action')
      return;
    
    $eqKlereo = $this->getEqLogic();
    
    $logicalId = $this->getLogicalId();
    log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $logicalId: ' . var_export($logicalId, true));
    
    // *-*-*-*-*-*-*-*-*-*-*-* Commande de raffraichissement *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*
    if ($logicalId === 'refresh') {
      $eqKlereo->getPoolDetails(true);
      klereo::actualizeValues();
      return;
      
    // *-*-*-*-*-*-*-*-*-*-*-* Commande 'out' *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*
    } elseif (substr($logicalId, 0, 4) === 'out_') {
      $outN = substr($logicalId, 0, 7);
      try{
        $outIndex = intval(substr($logicalId, 4, 3));
      }catch(Exception | Error $e) {
        throw new Exception($this->getHumanName() . '&nbsp;:</br>' . __('\'logicalId\' de la commande à exécuter n\'a pas la bonne forme.', __FILE__));
      }
      log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $outIndex: ' . var_export($outIndex, true));
      
      $cmdInfo = $this->getCmdValue();
      if (!is_object($cmdInfo))
        throw new Exception($this->getHumanName() . '&nbsp;:</br>' . __('La commande action à exécuter n\'a pas de commande info liée.', __FILE__));
      
      $details = $eqKlereo->getPoolDetails();
      $pool_id = $details['idSystem'];
      
      $getIndex = klereo::getIndex();
      $pool = null;
      foreach ($getIndex as $pool_info) {
        if ($pool_info['idSystem'] == $pool_id) {
          $pool = $pool_info;
          break;
        }
      }
      
      if ($outIndex == 1) { // *-*-*-*-*-*-*-*-*-*-*-* Filtration
        $isAnalogicPump = $details['PumpMaxSpeed'] > 1;
        
        $execCmd = ''; // Type de commande à exécuter ('OFF', 'ON', 'Setpoint', 'AUTO' ou 'Regulation')
        
        // *** Commande OFF
        $cmdActionOff = $eqKlereo->getCmd('action', $outN . '_off');
        $cmdInfoOff = $cmdActionOff->getCmdValue();
        if ($cmdInfoOff->execCmd() == '') // la commande est juste initialisée
          $eqKlereo->checkAndUpdateCmd($cmdInfoOff, 0);
        if ($logicalId == $cmdActionOff->getLogicalId()) {
          $execCmd = 'OFF';
          $cmdInfoOffValue = $cmdInfoOff->execCmd();
          log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $cmdInfoOffValue: ' . var_export($cmdInfoOffValue, true));
          $eqKlereo->checkAndUpdateCmd($cmdInfoOff, 1 - $cmdInfoOffValue); // La valeur de cette commande est uniquement gérée ici
        }
        
        // *** Commande ON / Consigne
        if ($isAnalogicPump) {
          $cmdActionValue = $eqKlereo->getCmd('action', $outN . '_value');
          $cmdInfoValue = $cmdActionValue->getCmdValue();
          $cmdInfoValueValue = $cmdInfoValue->execCmd();
          if ($logicalId == $cmdActionValue->getLogicalId())
            $execCmd = 'Setpoint';
        } else {
          $cmdActionOn = $eqKlereo->getCmd('action', $outN . '_on');
          $cmdInfoOn = $cmdActionOn->getCmdValue();
          $cmdInfoOnValue = $cmdInfoOn->execCmd();
          if ($logicalId == $cmdActionOn->getLogicalId())
            $execCmd = 'ON';
        }
        
        // *** Commande AUTO
        $cmdActionAuto = $eqKlereo->getCmd('action', $outN . '_auto');
        $cmdInfoAuto = $cmdActionAuto->getCmdValue();
        $cmdInfoAutoValue = $cmdInfoAuto->execCmd();
        if ($logicalId == $cmdActionAuto->getLogicalId())
          $execCmd = 'AUTO';
        
        // *** Commande Régulation
        $cmdActionRegul = $eqKlereo->getCmd('action', $outN . '_regulation');
        $cmdInfoRegul = $cmdActionRegul->getCmdValue();
        $cmdInfoRegulValue = $cmdInfoRegul->execCmd();
        if ($logicalId == $cmdActionRegul->getLogicalId())
          $execCmd = 'Regulation';
        
        // Mode et état courant
        $curMode = self::$_OUT_STATE_OFF;
        foreach ($details['outs'] as $out) {
          if ($out['index'] == $outIndex) {
            $curMode = $out['mode'];
            break;
          }
        }
        $curState = $isAnalogicPump ? $cmdInfoValueValue : $cmdInfoOnValue;
        
        // Nouvelle valeur de la commande
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $execCmd: ' . var_export($execCmd, true));
        $newOffValue = $cmdInfoOff->execCmd();
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newOffValue: ' . var_export($newOffValue, true));
        if ($isAnalogicPump) {
          $newSetpointValue = $execCmd == 'Setpoint' ? $_option['slider'] : $cmdInfoValueValue;
          log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newSetpointValue: ' . var_export($newSetpointValue, true));
        } else {
          $newOnValue = $execCmd == 'ON' ? 1 - $cmdInfoOnValue : $cmdInfoOnValue;
          log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newOnValue: ' . var_export($newOnValue, true));
        }
        $newAutoValue = $execCmd == 'AUTO' ? 1 - $cmdInfoAutoValue : $cmdInfoAutoValue;
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newAutoValue: ' . var_export($newAutoValue, true));
        $newRegulValue = $execCmd == 'Regulation' ? 1 - $cmdInfoRegulValue : $cmdInfoRegulValue;
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newRegulValue: ' . var_export($newRegulValue, true));
        
        // Nouveaux mode et état
        $newMode = self::$_OUT_MODE_MAN;
        $newState = self::$_OUT_STATE_OFF;
        if ($newOffValue == 1) {
          $newMode = self::$_OUT_MODE_MAN;
          $newState = self::$_OUT_STATE_OFF;
        } elseif ($isAnalogicPump && $execCmd == 'Setpoint') {
          $newMode = self::$_OUT_MODE_MAN;
          $newState = intval($newSetpointValue);
        } elseif (!$isAnalogicPump && $newOnValue == 1) {
          $newMode = self::$_OUT_MODE_MAN;
          $newState = self::$_OUT_STATE_ON;
        } elseif ($newAutoValue == 1) {
          $newMode = self::$_OUT_MODE_TIME_SLOTS;
          $newState = self::$_OUT_STATE_AUTO;
        } elseif ($newRegulValue == 1) {
          $newMode = self::$_OUT_MODE_REGUL;
          $newState = self::$_OUT_STATE_AUTO;
        }
        
      } else if ($outIndex == 4) { // *-*-*-*-*-*-*-*-*-*-*-* Chauffage
        $execCmd = ''; // Type de commande à exécuter ('OFF' ou 'Regulation')
        
        // *** Commande OFF
        $cmdActionOff = $eqKlereo->getCmd('action', $outN . '_off');
        $cmdInfoOff = $cmdActionOff->getCmdValue();
        if ($cmdInfoOff->execCmd() == '') // la commande est juste initialisée
          $eqKlereo->checkAndUpdateCmd($cmdInfoOff, 0);
        if ($logicalId == $cmdActionOff->getLogicalId()) {
          $execCmd = 'OFF';
          $cmdInfoOffValue = $cmdInfoOff->execCmd();
          log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $cmdInfoOffValue: ' . var_export($cmdInfoOffValue, true));
          $eqKlereo->checkAndUpdateCmd($cmdInfoOff, 1 - $cmdInfoOffValue); // La valeur de cette commande est uniquement gérée ici
        }
        
        // *** Commande Régulation
        $cmdActionRegul = $eqKlereo->getCmd('action', $outN . '_regulation');
        $cmdInfoRegul = $cmdActionRegul->getCmdValue();
        $cmdInfoRegulValue = $cmdInfoRegul->execCmd();
        if ($logicalId == $cmdActionRegul->getLogicalId())
          $execCmd = 'Regulation';
        
        // Mode et état courant
        $curMode = self::$_HEAT_MODE_STOP;
        $curState = self::$_OUT_STATE_AUTO;
        foreach ($details['outs'] as $out) {
          if ($out['index'] == $outIndex) {
            $curMode = $out['mode'];
            $curState = $out['status'];
            break;
          }
        }
        
        // Nouvelle valeur de la commande
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $execCmd: ' . var_export($execCmd, true));
        $newOffValue = $cmdInfoOff->execCmd();
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newOffValue: ' . var_export($newOffValue, true));
        $newRegulValue = $execCmd == 'Regulation' ? $_option['select'] : $cmdInfoRegulValue;
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newRegulValue: ' . var_export($newRegulValue, true));
        
        // Nouveaux mode et état
        $newMode = $curMode;
        $newState = self::$_OUT_STATE_OFF;
        if ($newOffValue == 1) {
          $newMode = self::$_HEAT_MODE_STOP;
          $newState = self::$_OUT_STATE_OFF;
        } elseif ($execCmd == 'Regulation') {
          $newMode = intval($newRegulValue);
          $newState = $newMode > 0 ? self::$_OUT_STATE_AUTO : self::$_OUT_STATE_OFF;
        }
        
      } else { // *-*-*-*-*-*-*-*-*-*-*-* Commande 'nomale' (éclairage ou AuxN)
        $execCmd = ''; // Type de commande à exécuter ('OFF', 'ON', 'Timer' ou 'TimeSlots')
        
        // *** Commande OFF
        $cmdActionOff = $eqKlereo->getCmd('action', $outN . '_off');
        $cmdInfoOff = $cmdActionOff->getCmdValue();
        if ($cmdInfoOff->execCmd() == '') // la commande est juste initialisée
          $eqKlereo->checkAndUpdateCmd($cmdInfoOff, 0);
        if ($logicalId == $cmdActionOff->getLogicalId()) {
          $execCmd = 'OFF';
          $cmdInfoOffValue = $cmdInfoOff->execCmd();
          log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $cmdInfoOffValue: ' . var_export($cmdInfoOffValue, true));
          $eqKlereo->checkAndUpdateCmd($cmdInfoOff, 1 - $cmdInfoOffValue); // La valeur de cette commande est uniquement gérée ici
        }
        
        // *** Commande ON
        $cmdActionOn = $eqKlereo->getCmd('action', $outN . '_on');
        $cmdInfoOn = $cmdActionOn->getCmdValue();
        $cmdInfoOnValue = $cmdInfoOn->execCmd();
        if ($logicalId == $cmdActionOn->getLogicalId())
          $execCmd = 'ON';
        
        // *** Commande minuterie
        $cmdActionTimer = $eqKlereo->getCmd('action', $outN . '_timer');
        $cmdInfoTimer = $cmdActionTimer->getCmdValue();
        $cmdInfoTimerValue = $cmdInfoTimer->execCmd();
        if ($logicalId == $cmdActionTimer->getLogicalId())
          $execCmd = 'Timer';
        
        // *** Commande plage
        $cmdActionTimeSlots = $eqKlereo->getCmd('action', $outN . '_timeSlots');
        $cmdInfoTimeSlots = $cmdActionTimeSlots->getCmdValue();
        $cmdInfoTimeSlotsValue = $cmdInfoTimeSlots->execCmd();
        if ($logicalId == $cmdActionTimeSlots->getLogicalId())
          $execCmd = 'TimeSlots';
        
        // Mode et état courant
        $curMode = self::$_OUT_MODE_MAN;
        $curState = self::$_OUT_STATE_OFF;
        foreach ($details['outs'] as $out) {
          if ($out['index'] == $outIndex) {
            $curMode = $out['mode'];
            $curState = $out['status'];
            break;
          }
        }
        
        // Nouvelle valeur de la commande
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $execCmd: ' . var_export($execCmd, true));
        $newOffValue = $cmdInfoOff->execCmd();
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newOffValue: ' . var_export($newOffValue, true));
        $newOnValue = $execCmd == 'ON' ? 1 - $cmdInfoOnValue : $cmdInfoOnValue;
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newOnValue: ' . var_export($newOnValue, true));
        $newTimerValue = $execCmd == 'Timer' ? 1 - $cmdInfoTimerValue : $cmdInfoTimerValue;
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newTimerValue: ' . var_export($newTimerValue, true));
        $newTimeSlotsValue = $execCmd == 'TimeSlots' ? 1 - $cmdInfoTimeSlotsValue : $cmdInfoTimeSlotsValue;
        log::add('klereo', 'debug', __CLASS__ . '::' . __FUNCTION__ . ' $newTimeSlotsValue: ' . var_export($newTimeSlotsValue, true));
        
        // Nouveaux mode et état
        $newMode = self::$_OUT_MODE_MAN;
        $newState = self::$_OUT_STATE_OFF;
        if ($newOffValue == 1) {
          $newMode = self::$_OUT_MODE_MAN;
          $newState = self::$_OUT_STATE_OFF;
        } elseif ($newOnValue == 1) {
          $newMode = self::$_OUT_MODE_MAN;
          $newState = self::$_OUT_STATE_ON;
        } elseif ($newTimerValue == 1) {
          $newMode = self::$_OUT_MODE_TIMER;
          $newState = self::$_OUT_STATE_AUTO;
        } elseif ($newTimeSlotsValue == 1) {
          $newMode = self::$_OUT_MODE_TIME_SLOTS;
          $newState = self::$_OUT_STATE_AUTO;
        }
        
      }
      
      // En cas de changement de mode ou d'état -> setOut
      if ($newMode != $curMode || $newState != $curState) {
        $cmdID = $eqKlereo->setOut($outIndex, $newMode, $newState);
        $status = $eqKlereo->waitCommand($cmdID);
        if ($status == 9) {
          $eqKlereo->getPoolDetails(true);
          klereo::actualizeValues();
        }
      }
      
    // *-*-*-*-*-*-*-*-*-*-*-* Commande 'Consigne' *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*
    } elseif (substr($logicalId, 0, 8) === 'Consigne') {
      $cmdID = $eqKlereo->setParam($logicalId, floatval($_option['slider']));
      $status = $eqKlereo->waitCommand($cmdID);
      if ($status == 9) {
        $eqKlereo->getPoolDetails(true);
        klereo::actualizeValues();
      }
      
    // *-*-*-*-*-*-*-*-*-*-*-* Commande 'offDelay' *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*
    } elseif (substr($logicalId, 0, 8) === 'offDelay') {
      $outIndex = intval(substr($logicalId, -3));
      $cmdID = $eqKlereo->setAutoOff($outIndex, floatval($_option['slider']));
      $status = $eqKlereo->waitCommand($cmdID);
      if ($status == 9) {
        $eqKlereo->getPoolDetails(true);
        klereo::actualizeValues();
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