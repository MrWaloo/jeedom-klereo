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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <legend><i class="icon loisir-darth"></i>{{Informations de connexion au site klereo.fr}}</legend>
    <div class="form-group expertModeVisible">
      <label class="col-lg-4 control-label">{{Utilisateur}}&nbsp;:</label>
      <div class="col-lg-2">
        <input class="configKey form-control" data-l1key="login"/>
      </div>
    </div>
    <div class="form-group expertModeVisible">
      <label class="col-lg-4 control-label">{{Mot de passe}}&nbsp;:</label>
      <div class="col-lg-2">
        <input class="configKey form-control" data-l1key="password" type="password"/>
      </div>
    </div>
  </fieldset>
</form>

<script>
  $('#bt_savePluginConfig').on('click', function() { // bouton sauvegarde de la configuration du plugin
    $.ajax({// fonction permettant de faire de l'ajax
      type: "POST", // methode de transmission des données au fichier php
      url: "plugins/klereo/core/ajax/klereo.ajax.php", // url du fichier php
      data: {
        action: "reinit"
      },
      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error);
      },
      success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
          $.fn.showAlert({message: data.result, level: 'danger'});
          return;
        }
      }
    });
  });
</script>