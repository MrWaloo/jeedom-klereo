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

$('.eqLogicAttr[data-l1key=configuration][data-l2key=eqPoolId]').off().on('change', function () {
  if ($(this).val() != '') {
    
  }
});

/*
 * Fonction pour l'ajout de commande, appelée automatiquement par plugin.template
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
  tr += ' </td>'
  // Nom
  tr += ' <td class="name">';
  tr += '   <input class="cmdAttr form-control input-sm" data-l1key="name">';
  tr += ' </td>';
  // Valeur
  tr += ' <td>';
  tr += '   <span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += ' </td>';  
  // Type
  tr += ' <td>';
  tr += '   <div class="input-group">';
  tr += '     <span class="type" id="' + init(_cmd.type) + '" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '     <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '   </div>';
  tr += ' </td>';
  // Adresse esclave
  tr += ' <td><input class="cmdAttr form-control input-sm withSlave" data-l1key="configuration" data-l2key="cmdSlave"></td>';
  // Modbus function / Data format
  tr += ' <td>';
  tr += '   <div class="input-group" style="margin-bottom:5px;">';
  tr += '     <select class="cmdAttr form-control input-sm" style="width:230px;" data-l1key="configuration" data-l2key="cmdFctModbus">';
  tr += '       <option class="readBin" value="1">[0x01] Read coils</option>';
  tr += '       <option class="readBin" value="2">[0x02] Read discrete inputs</option>';
  tr += '       <option class="readNum" value="3">[0x03] Read holding registers</option>';
  tr += '       <option class="readNum" value="4">[0x04] Read input registers</option>';
  tr += '       <option class="writeFunction" value="5">[0x05] Write single coil</option>';
  tr += '       <option class="writeFunction" value="15">[0x0F] Write coils</option>';
  tr += '       <option class="writeFunction" value="6">[0x06] Write register</option>';
  tr += '       <option class="writeFunction" value="16">[0x10] Write registers</option>';
  tr += '       <option class="readFunction" value="fromBlob">{{Depuis une plage de registres}}</option>';
  tr += '     </select>';
  tr += '   </div>';
  tr += '   <div class="input-group">';
  tr += '     <select class="cmdAttr form-control input-sm" style="width:230px;" data-l1key="configuration" data-l2key="cmdFormat">';
  tr += '       <option class="formatBin" value="bit">bit (0 .. 1)</option>';
  tr += '       <option class="formatBin" value="bit-inv">{{bit inversé}} (1 .. 0)</option>';
  tr += '       <optgroup class="formatNum" label="8 bits">';
  tr += '         <option class="formatNum" value="int8-lsb">int8 LSB (-128 ... 127)</option>';
  tr += '         <option class="formatNum" value="int8-msb">int8 MSB (-128 ... 127)</option>';
  tr += '         <option class="formatNum" value="uint8-lsb">uint8 LSB (0 ... 255)</option>';
  tr += '         <option class="formatNum" value="uint8-msb">uint8 MSB (0 ... 255)</option>';
  tr += '       </optgroup>';
  tr += '       <optgroup class="formatNum" label="16 bits">';
  tr += '         <option class="formatNum" value="int16">int16 (-32 768 ... 32 768)</option>';
  tr += '         <option class="formatNum" value="uint16">uint16 (0 ... 65 535)</option>';
  tr += '         <option class="formatNum" value="float16">float16 (Real 16bit)</option>';
  tr += '       </optgroup>';
  tr += '       <optgroup class="formatNum" label="32 bits ({{2 registres}})">';
  tr += '         <option class="formatNum" value="int32">int32 (-2 147 483 648 ... 2 147 483 647)</option>';
  tr += '         <option class="formatNum" value="uint32">uint32 (0 ... 4 294 967 296)</option>';
  tr += '         <option class="formatNum" value="float32">float32 (Real 32bit)</option>';
  tr += '       </optgroup>';
  tr += '       <optgroup class="formatNum" label="64 bits ({{4 registres}})">';
  tr += '         <option class="formatNum" value="int64">int64 (-9e18 ... 9e18)</option>';
  tr += '         <option class="formatNum" value="uint64">uint64 (0 ... 18e18)</option>';
  tr += '         <option class="formatNum" value="float64">float64 (Real 64bit)</option>';
  tr += '       </optgroup>';
  tr += '       <option class="formatNum" value="string">{{Chaine de caractères}}</option>';
  tr += '       <option class="notFctBlob" value="blob">{{Plage de registres}}</option>';
  tr += '       <optgroup class="formatNum" label="{{Spécial}}">';
  tr += '         <option class="formatNum" value="int16sp-sf">{{SunSpec scale factor int16}}</option>';
  tr += '         <option class="formatNum" value="uint16sp-sf">{{SunSpec scale factor uint16}}</option>';
  tr += '         <option class="formatNum" value="uint32sp-sf">{{SunSpec scale factor uint32}}</option>';
  tr += '       </optgroup>';
  tr += '     </select>';
  tr += '   </div>';
  tr += ' </td>';
  // Adresse Modbus
  tr += ' <td>';
  tr += '   <div class="input-group" style="margin-bottom:5px;">';
  tr += '     <select class="cmdAttr form-control input-sm FctBlobBin" style="width:100%;" data-l1key="configuration" data-l2key="cmdSourceBlobBin">';
  tr += '     </select>';
  tr += '     <select class="cmdAttr form-control input-sm FctBlobNum" style="width:100%;" data-l1key="configuration" data-l2key="cmdSourceBlobNum">';
  tr += '     </select>';
  tr += '   <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="cmdAddress"/>';
  tr += '   <label class="checkbox-inline notFormatBlob">';
  tr += '     <input type="checkbox" class="cmdAttr checkbox-inline tooltips" title="{{\'Little endian\' si coché}}" data-l1key="configuration" data-l2key="cmdInvertBytes"/>{{Inverser octets}}';
  tr += '   </label></br>';
  tr += '   <label class="checkbox-inline notFormatBlob">';
  tr += '     <input type="checkbox" class="cmdAttr checkbox-inline tooltips" title="{{\'Little endian\' si coché}}" data-l1key="configuration" data-l2key="cmdInvertWords"/>{{Inverser mots}}</label></br>';
  tr += '   </label></br>';
  tr += '   </div>';
  tr += ' </td>';
  // Paramètre
  tr += ' <td>';
  tr += '   <div class="input-group">';
  tr += '     <input class="cmdAttr form-control input-sm roundedLeft readFunction" data-l1key="configuration" data-l2key="cmdOption" placeholder="{{Option}}"/>';
  tr += '     <span class="input-group-btn">';
  tr += '       <a class="btn btn-default btn-sm cursor paramFiltre roundedRight readFunction" data-input="configuration"><i class="fa fa-list-alt"></i></a>';
  tr += '     </span>';
  tr += '   </div>';
  tr += '   <div class="input-group notFctBlob">';
  tr += '     <label class="label">{{Lecture 1x sur&nbsp;:}}&nbsp;';
  tr += '       <input class="cmdAttr form-inline input-sm" style="width:70px;" data-l1key="configuration" data-l2key="cmdFrequency" placeholder="{{1 par défaut}}"/>';
  tr += '     </label>';
  tr += '   </div>';
  tr += '   <div class="input-group" style="width:100%;">';
  tr += '     <input class="cmdAttr form-control input-sm roundedLeft writeFunction" data-l1key="configuration" data-l2key="cmdWriteValue" placeholder="{{Valeur}}"/>';
  tr += '     <span class="input-group-btn">'
  tr += '       <a class="btn btn-default btn-sm listEquipementInfo roundedRight writeFunction" data-input="cmdWriteValue"><i class="fas fa-list-alt"></i></a>'
  tr += '     </span>'
  tr += '   </div>';
  tr += ' </td>';    
  // Options
  tr += ' <td>';
  if (is_numeric(_cmd.id)) {
    tr += '   <a class="btn btn-default btn-xs cmdAction" data-action="configure" title="{{Configuration de la commande}}""><i class="fas fa-cogs"></i></a>';
    tr += '   <a class="btn btn-default btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fas fa-rss"></i></a>';
    tr += '   <a class="btn btn-default btn-xs cmdAction" data-action="copy" title="{{Dupliquer}}"><i class="far fa-clone"></i></a>';
  }
  tr += '   <label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label>';
  tr += '   <label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" data-size="mini"/>{{Historiser}}</label>';
  tr += '   <div class="input-group" style="margin-top:7px;">';
  tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:100px;display:inline-block;margin-right:2px;"/>';
  tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:100px;display:inline-block;margin-right:2px;"/>';
  tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;max-width:100px;display:inline-block;margin-right:2px;"/>';
  tr += '   </div>';
  tr += ' </td>';
  // Delete button
  tr += ' <td>';
  tr += '   <div class="input-group">';
  tr += '     <i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer}}"></i>';
  tr += '   </div>';
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