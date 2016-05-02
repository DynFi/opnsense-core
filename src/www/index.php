<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2004-2012 Scott Ullrich
    Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    oR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

// Turn on buffering to speed up rendering
ini_set('output_buffering', 'true');

// Start buffering with a cache size of 100000
ob_start(null, "1000");

// Load Essential Includes
require_once('guiconfig.inc');

// closing should be $_POST, but the whole notice handling needs more attention. Leave it as is for now.
if (isset($_REQUEST['closenotice'])) {
    close_notice($_REQUEST['closenotice']);
    echo get_menu_messages();
    exit;
}

##if no config entry found, initialize config entry
if (empty($config['widgets']) || !is_array($config['widgets'])) {
    $config['widgets'] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pconfig = $config['widgets'];
    if (empty($pconfig['sequence'])) {
        // set default dashboard view
        $pconfig['sequence'] = 'system_information-container:col1:show,interface_list-container:col1:show,traffic_graphs-container:col1:show';
    }
    // default 2 column grid layout
    $pconfig['column_count'] = !empty($pconfig['column_count']) ? $pconfig['column_count'] : 2;
    // build list of widgets
    $widgetCollection = array();
    $widgetSeqParts = explode(",", $pconfig['sequence']);
    foreach (glob('/usr/local/www/widgets/widgets/*.widget.php') as $php_file) {
        $widgetItem = array();
        $widgetItem['name'] = basename($php_file, '.widget.php');
        $widgetItem['display_name'] = ucwords(str_replace("_", " ", $widgetItem['name']));
        $widgetItem['filename'] = $php_file;
        $widgetItem['state'] = "none";
        /// default sort order
        $widgetItem['sortKey'] = $widgetItem['name'] == 'system_information' ? "00000000" : "99999999" . $widgetItem['name'];
        foreach ($widgetSeqParts as $seqPart) {
            $tmp = explode(':', $seqPart);
            if (count($tmp) == 3 && explode('-', $tmp[0])[0] == $widgetItem['name']) {
                $widgetItem['state'] = $tmp[2];
                if (is_numeric($tmp[1])) {
                    $widgetItem['sortKey'] = $tmp[1];
                }
            }
        }
        $widgetCollection[] = $widgetItem;
    }
    // sort widgets
    usort($widgetCollection, function ($item1, $item2) {
      return strcmp(strtolower($item1['sortKey']), strtolower($item2['sortKey']));
    });
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['sequence'])) {
        $config['widgets']['sequence'] = $_POST['sequence'];
        if (!empty($_POST['column_count'])) {
            $config['widgets']['column_count'] = $_POST['column_count'];
        }
        write_config(gettext("Widget configuration has been changed."));
    }
    header("Location: index.php");
    exit;
}

// handle widget includes
foreach (glob("/usr/local/www/widgets/include/*.inc") as $filename) {
    include($filename);
}

include("head.inc");
?>
<body>
<?php
include("fbegin.inc");?>

<?php
  ## If it is the first time webConfigurator has been
  ## accessed since initial install show this stuff.
  if (isset($config['trigger_initial_wizard'])) :?>
  <script type="text/javascript">
      $( document ).ready(function() {
        $(".page-content-head:first").hide();
      });
  </script>
  <header class="page-content-head">
    <div class="container-fluid">
      <h1><?= gettext("Starting initial configuration!") ?></h1>
    </div>
  </header>
  <section class="page-content-main">
    <div class="container-fluid col-xs-12 col-sm-10 col-md-9">
      <div class="row">
        <section class="col-xs-12">
          <div class="content-box" style="padding: 20px;">
            <div class="table-responsive">
              <img src="/ui/themes/<?=$themename;?>/build/images/default-logo.png" border="0" alt="logo" />
              <br />
              <div class="content-box-main">
                <?php
                    echo sprintf(gettext("Welcome to %s!"), $g['product_name']) . "<p>\n";
                    echo gettext("One moment while we start the initial setup wizard.") . "<p>\n";
                    echo gettext("Embedded platform users: Please be patient, the wizard takes a little longer to run than the normal GUI.") . "<p>\n";
                    echo gettext("To bypass the wizard, click on the logo in the upper left corner.") . "\n";
                ?>
              </div>
            <div>
          </div>
        </section>
      </div>
    </div>
  </section>
  <meta http-equiv="refresh" content="3;url=wizard.php">
<?php
  // normal dashboard
  else:?>

<script src='/javascript/index/ajax.js'></script>
<script src='/ui/js/jquery-sortable.js'></script>
<script type="text/javascript">
  function addWidget(selectedDiv) {
      $('#'+selectedDiv).show();
      $('#'+selectedDiv+'-config').val('show');
      showSave();
  }

  function configureWidget(selectedDiv) {
      selectIntLink = '#' + selectedDiv + "-settings";
      if ($(selectIntLink).css('display') == "none") {
          $(selectIntLink).show();
      } else {
          $(selectIntLink).hide();
      }
  }

  function showWidget(selectedDiv,swapButtons) {
      $('#'+selectedDiv+'-container').show();
      $('#'+selectedDiv+'-min').show();
      $('#'+selectedDiv+'-max').hide();
      $('#'+selectedDiv+'-config').val('show');
      showSave();
  }

  function minimizeWidget(selectedDiv, swapButtons) {
      $('#'+selectedDiv+'-container').hide();
      $('#'+selectedDiv+'-min').hide();
      $('#'+selectedDiv+'-max').show();
      $('#'+selectedDiv+'-config').val('hide');
      showSave();
  }

  function closeWidget(selectedDiv) {
      $('#'+selectedDiv).hide();
      $('#'+selectedDiv+'-config').val('close');
      showSave();
  }

  function showSave() {
      $('#updatepref').show();
  }

  function updatePref() {
      var widgetInfo = [];
      var index = 0;
      $('.widgetdiv').each(function(key) {
          if ($(this).is(':visible')) {
              // only capture visible widgets
              var index_str = "0000000" + index;
              index_str = index_str.substr(index_str.length-8);
              widgetInfo.push($(this).attr('id')+'-container:'+index_str+':'+$('input[name='+$(this).attr('id')+'-config]').val());
              index++;
          }
      });
      $("#sequence").val(widgetInfo.join(','));
      $("#iform").submit();
      return false;
  }
</script>

<script type="text/javascript">
  $( document ).ready(function() {
      // sortable widgets
      $(".sortable").sortable({
        handle: '.content-box-head',
        itemSelector: '.widgetdiv',
        containerSelector: '.sortable',
        placeholder: '<div class="placeholder"><i class="fa fa-hand-o-right" aria-hidden="true"></i></div>',
        afterMove: function (placeholder, container, closestItemOrContainer) {
            showSave();
        }
      });
      $("#column_count").change(function(){
          if ($("#column_count_input").val() != $("#column_count").val()) {
              showSave();
          }
         $("#column_count_input").val($("#column_count").val());
         $(".widgetdiv").each(function(){
             var widget = $(this);
             $.each(widget.attr("class").split(' '), function(index, classname) {
                if (classname.indexOf('col-md') > -1) {
                    widget.removeClass(classname);
                }
             });;
             widget.addClass('col-md-'+(12 / $("#column_count_input").val()));
         });
      });
      $("#column_count").change();
  });
</script>

<section class="page-content-main">
  <form method="post" id="iform">
    <input type="hidden" value="" name="sequence" id="sequence" />
    <input type="hidden" value="<?= $pconfig['column_count'];?>" name="column_count" id="column_count_input" />
    <div class="container-fluid">
      <div class="row sortable">
<?php
      $crash_report = get_crash_report();
      if ($crash_report != '') {
          print_info_box($crash_report);
      }

      foreach ($widgetCollection as $widgetItem):
          $widgettitle = $widgetItem['name'] . "_title";
          $widgettitlelink = $widgetItem['name'] . "_title_link";
          switch ($widgetItem['state']) {
              case "show":
                  $divdisplay = "block";
                  $display = "block";
                  $inputdisplay = "show";
                  $mindiv = "inline";
                  break;
              case "hide":
                  $divdisplay = "block";
                  $display = "none";
                  $inputdisplay = "hide";
                  $mindiv = "none";
                  break;
              case "close":
                  $divdisplay = "none";
                  $display = "block";
                  $inputdisplay = "close";
                  $mindiv = "inline";
                  break;
              default:
                  $divdisplay = "none";
                  $display = "block";
                  $inputdisplay = "none";
                  $mindiv = "inline";
                  break;
          }?>
          <section class="col-xs-12 col-md-6 widgetdiv" id="<?= $widgetItem['name'] ?>"  style="display:<?= $divdisplay ?>;">
            <div class="content-box">
              <header class="content-box-head container-fluid">
                <ul class="list-inline __nomb">
                  <li><h3>
<?php
                    if (isset($$widgettitlelink)):?>
                        <u><span onclick="location.href='/<?= $$widgettitlelink ?>'" style="cursor:pointer">
<?php
                    endif;
                        echo empty($$widgettitle) ?   $widgetItem['display_name'] : $$widgettitle;
                    if (isset($$widgettitlelink)):?>
                        </span></u>
<?php
                    endif;?>
                  </h3></li>
                  <li class="pull-right">
                    <div class="btn-group">
                      <button type="button" class="btn btn-default btn-xs" title="minimize" id="<?= $widgetItem['name'] ?>-min" onclick='return minimizeWidget("<?= $widgetItem['name'] ?>",true)' style="display:<?= $mindiv ?>;"><span class="glyphicon glyphicon-minus"></span></button>
                      <button type="button" class="btn btn-default btn-xs" title="maximize" id="<?= $widgetItem['name'] ?>-max" onclick='return showWidget("<?= $widgetItem['name'] ?>",true)' style="display:<?= $mindiv == 'none' ? 'inline' : 'none' ?>;"><span class="glyphicon glyphicon-plus"></span></button>
                      <button type="button" class="btn btn-default btn-xs" title="remove widget" onclick='return closeWidget("<?= $widgetItem['name'] ?>",true)'><span class="glyphicon glyphicon-remove"></span></button>
                      <button type="button" class="btn btn-default btn-xs" id="<?= $widgetItem['name'] ?>-configure" onclick='return configureWidget("<?=  $widgetItem['name'] ?>")' style="display:none; cursor:pointer" ><span class="glyphicon glyphicon-pencil"></span></button>
                    </div>
                  </li>
                </ul>
              </header>
              <div class="content-box-main collapse in" id="<?= $widgetItem['name'] ?>-container" style="display:<?= $mindiv ?>">
                <input type="hidden" value="<?= $inputdisplay ?>" id="<?= $widgetItem['name'] ?>-config" name="<?= $widgetItem['name'] ?>-config" />
<?php
                if ($divdisplay != "block"):?>
                  <div id="<?= $widgetItem['name'] ?>-loader" style="display:<?= $display ?>;" align="center">
                    <br />
                      <span class="glyphicon glyphicon-refresh"></span> <?= gettext("Loading selected widget") ?>
                    <br />
                  </div>
<?php
                else:
                    include($widgetItem['filename']);
                endif;
?>
              </div>
            </div>
          </section>
<?php
          endforeach;?>
      </div>
    </div>
  </form>
</section>
<?php
    // include widget javascripts
    foreach (glob("/usr/local/www/widgets/javascript/*.js") as $filename):?>
      <script src="/widgets/javascript/<?=basename($filename);?>" type="text/javascript"></script>
<?php
    endforeach;?>
<?php
  endif;
include("foot.inc");?>
