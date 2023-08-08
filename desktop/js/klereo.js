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

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
});

$('.eqLogicAction[data-action=bt_docSpecific]').on('click', function () {
  window.open('https://bebel27a.github.io/jeedom-mymobdus.github.io/fr_FR/');
});
$('.bt_showExpressionTest').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Testeur d'expression}}"});
  $("#md_modal").load('index.php?v=d&modal=expression.test').dialog('open');
});

function printEqLogic(_eqLogic) {
  // load values
  $('#eqLogic').setValues(_eqLogic, '.eqLogicAttr');
}


/*
 * Fonction pour l'ajout de commandes, appelée automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
  // Minimal structure for _cmd
  if (!isset(_cmd))
    var _cmd = {configuration: {}};
  if (!isset(_cmd.configuration))
    _cmd.configuration = {};
  
  //console.log('CMD - ' + init(JSON.stringify(_cmd)));
  
  // id
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += ' <td class="hidden-xs">'
  tr += '   <span class="cmdAttr" data-l1key="id" disabled></span>'
  tr += '   <span class="cmdAttr" data-l1key="logicalId" hidden></span>'
  tr += '   <span class="type" id="' + init(_cmd.type) + '" type="' + init(_cmd.type) + '" hidden>' + jeedom.cmd.availableType() + '</span>';
  tr += '   <span class="subType" subType="' + init(_cmd.subType) + '" hidden></span>';
  tr += ' </td>'
  // Nom
  tr += ' <td class="name">';
  tr += '   <input class="cmdAttr form-control input-sm" data-l1key="name">';
  tr += ' </td>';
  // Valeur
  tr += ' <td>';
  if (init(_cmd.type) == 'info') {
    tr += '   <span class="cmdAttr" data-l1key="htmlstate"></span>';
  } else if (_cmd.logicalId.substr(0, 10) == 'filtration') {
    tr += '   cmdValue = <span class="cmdAttr" data-l1key="configuration" data-l2key="cmdValue"></span>';
  }
  tr += ' </td>';
  // Options
  tr += ' <td>';
  if (is_numeric(_cmd.id)) {
    tr += '   <a class="btn btn-default btn-xs cmdAction" data-action="configure" title="{{Configuration de la commande}}""><i class="fas fa-cogs"></i></a>';
    tr += '   <a class="btn btn-default btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fas fa-rss"></i></a>';
  }
  tr += '   <label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label>';
  tr += '   <label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" data-size="mini"/>{{Historiser}}</label>';
  if (init(_cmd.subType) == 'numeric') {
    tr += '   <div class="input-group" style="margin-top:7px;">';
    tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:120px;display:inline-block;margin-right:2px;"/>';
    tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:120px;display:inline-block;margin-right:2px;"/>';
    tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;max-width:120px;display:inline-block;margin-right:2px;"/>';
    tr += '   </div>';
  }
  tr += ' </td>';
  tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  
  var tr = $('#table_cmd tbody tr:last');
  jeedom.eqLogic.buildSelectCmd({
    id:  $('.eqLogicAttr[data-l1key=id]').value(),
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'});
    },
    success: function (result) {
      tr.setValues(_cmd, '.cmdAttr');
      jeedom.cmd.changeType(tr, init(_cmd.subType));
    }
  });
}