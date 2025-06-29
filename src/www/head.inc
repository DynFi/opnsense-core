<?php

// link menu system
$menu = new OPNsense\Base\Menu\MenuSystem();
/* XXX generating breadcrumbs requires getItems() call */
$menuSystem = $menu->getItems($_SERVER['REQUEST_URI']);
$menuBreadcrumbs = $menu->getBreadcrumbs($_SERVER['REQUEST_URI']);
$headerButtons = $menu->getHeaderButtons($menuBreadcrumbs);

if (isset($extraBreadcrumb)) {
    $menuBreadcrumbs[] = array('name' => $extraBreadcrumb);
}

$pagetitle = html_safe(gentitle(array_reverse($menuBreadcrumbs), ' | '));
$pagetitle .= html_safe(sprintf(' | %s.%s', $config['system']['hostname'], $config['system']['domain']));

?><!doctype html>
<html lang="<?= get_current_lang() ?>" class="no-js">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="robots" content="noindex, nofollow" />
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <meta name="copyright" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <title><?= $pagetitle ?></title>

    <!-- include (theme) style -->
    <link rel="stylesheet" type="text/css" href="<?= cache_safe(get_themed_filename('/css/main.css')) ?>">

    <!-- TODO: move to theme style -->
    <style>
      .menu-level-3-item {
        font-size: 90%;
        padding-left: 54px !important;
      }

      .typeahead {
        overflow: hidden;
      }

      /** jquery-sortable styles **/
      body.dragging, body.dragging * {
        cursor: move !important;
      }

      .dragged {
        position: absolute;
        opacity: 0.5;
        z-index: 2000;
      }

      ol.example li.placeholder {
        position: relative;
      }
      ol.example li.placeholder:before {
        position: absolute;
      }
    </style>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= cache_safe(get_themed_filename('/images/favicon.png')) ?>">

    <!-- legacy browser functions -->
    <script src="<?= cache_safe("/ui/js/polyfills.js") ?>"></script>

    <!-- Stylesheet for fancy select/dropdown -->
    <link rel="stylesheet" type="text/css" href="<?= cache_safe(get_themed_filename("/css/bootstrap-select.css")) ?>">

    <!-- bootstrap dialog -->
    <link rel="stylesheet" type="text/css" href="<?= cache_safe(get_themed_filename("/css/bootstrap-dialog.css")) ?>">

    <!-- Font awesome -->
    <link rel="stylesheet" type="text/css" href="<?= cache_safe("/ui/css/font-awesome.min.css") ?>">

    <!-- nvd3 -->
    <link rel="stylesheet" type="text/css" href="<?= cache_safe(get_themed_filename("/css/nv.d3.css")) ?>">

    <!-- datepicker css -->
    <link rel="stylesheet" type="text/css" href="<?= cache_safe(get_themed_filename("/css/bootstrap-datepicker3.min.css")) ?>">

    <!-- JQuery -->
    <script src="/ui/js/jquery-3.5.1.min.js"></script>

    <!-- datepicker -->
    <script src="<?= cache_safe("/ui/js/bootstrap-datepicker.min.js") ?>"></script>

    <!-- d3 -->
    <script src="<?= cache_safe("/ui/js/d3.min.js") ?>"></script>

    <!-- nvd3 -->
    <script src="<?= cache_safe("/ui/js/nv.d3.min.js") ?>"></script>

    <!-- append some helper functions to integrate into the legacy code -->
    <script src="<?= cache_safe("/javascript/opnsense_legacy.js") ?>"></script>

    <!-- opnsense non legacy helper functions -->
    <script src="<?= cache_safe("/ui/js/opnsense.js") ?>"></script>

    <!-- opnsense non legacy theme support functions -->
    <script src="<?= cache_safe("/ui/js/opnsense_theme.js") ?>"></script>

    <!-- Bootstrap type ahead -->
    <script src="<?= cache_safe("/ui/js/bootstrap3-typeahead.min.js") ?>"></script>

    <script src="<?= cache_safe("/ui/js/opnsense_status.js") ?>"></script>

    <script>
    //<![CDATA[
    $( document ).ready(function() {
      $('[data-toggle="tooltip"]').tooltip();
      $("input").not("[autocomplete]").attr("autocomplete","off");

      // hide empty menu items
      $('#mainmenu > div > .collapse').each(function(){
        // cleanup empty second level menu containers
        $(this).find("div.collapse").each(function(){
          if ($(this).children().length == 0 ) {
              $("#mainmenu").find('[href="#'+$(this).attr('id')+'"]').remove();
              $(this).remove();
          }
        });

        // cleanup empty first level menu items
        if ($(this).children().length == 0) {
          $("#mainmenu").find('[href="#'+$(this).attr('id')+'"]').remove();
        }
      });

      // link showhelp class behavior
      $("a[class='showhelp']").click(function (event) {
        $("*[data-for='" + $(this).attr('id') + "']").toggleClass("hidden show");
        event.preventDefault();
      });

      // handle all help messages show/hide
      $('[id*="show_all_help"]').click(function(event) {
        $('[id*="show_all_help"]').toggleClass("fa-toggle-on fa-toggle-off");
        $('[id*="show_all_help"]').toggleClass("text-success text-danger");
        if ($('[id*="show_all_help"]').hasClass("fa-toggle-on")) {
          if (window.sessionStorage) {
            sessionStorage.setItem('all_help_preset', 1);
          }
          $('[data-for*="help_for"]').addClass("show");
          $('[data-for*="help_for"]').removeClass("hidden");
        } else {
          if (window.sessionStorage) {
            sessionStorage.setItem('all_help_preset', 0);
          }
          $('[data-for*="help_for"]').addClass("hidden");
          $('[data-for*="help_for"]').removeClass("show");
        }
        event.preventDefault();
      });
      if (window.sessionStorage && sessionStorage.getItem('all_help_preset') == 1) {
          // show all help messages when preset was stored
          $('[id*="show_all_help"]').toggleClass("fa-toggle-on fa-toggle-off");
          $('[id*="show_all_help"]').toggleClass("text-success text-danger");
          $('[data-for*="help_for"]').addClass("show");
          $('[data-for*="help_for"]').removeClass("hidden");
      }

      // hide submenu items
      $('#mainmenu .list-group-item').click(function(){
        if($(this).attr('href').substring(0,1) == '#') {
            $('#mainmenu .list-group-item').each(function(){
                if ($(this).attr('aria-expanded') == 'true' && $(this).data('parent') != '#mainmenu') {
                  $("#"+$(this).attr('href').substring(1,999)).collapse('hide');
                }
            });
        }
      });

      $(".list-group-item.active").each(function(){
          var navbar_center = ($( window ).height() - $(".collapse.navbar-collapse").height())/2;
          $('html,aside').scrollTop(($(this).offset().top - navbar_center));
      });

      // Create status dialog instance
      let dialog = new BootstrapDialog({
          title: "<?= html_safe(gettext('System Status')) ?>",
          buttons: [{
              label: "<?= html_safe(gettext('Close')) ?>",
              action: function(dialogRef) {
                  dialogRef.close();
              }
          }],
      });

      setTimeout(function () {
          updateSystemStatus().then((data) => {
              let status = parseStatus(data);
              registerStatusDelegate(dialog, status);
          });
      }, 500);

      // hook in live menu search
      $.ajax("/api/core/menu/search/", {
          type: 'get',
          cache: false,
          dataType: "json",
          data: {},
          error : function (jqXHR, textStatus, errorThrown) {
              console.log('menu.search : ' +errorThrown);
          },
          success: function (data) {
              var menusearch_items = [];
              $.each(data,function(idx, menu_item){
                  if (menu_item.Url != "") {
                      menusearch_items.push({id:$('<div/>').html(menu_item.Url).text(), name:menu_item.breadcrumb});
                  }
              });
              $("#menu_search_box").typeahead({
                  source: menusearch_items,
                  matcher: function (item) {
                      var ar = this.query.trim();
                      if (ar == "") {
                          return false;
                      }
                      ar = ar.toLowerCase().split(/\s+/);
                      if (ar.length == 0) {
                          return false;
                      }
                      var it = this.displayText(item).toLowerCase();
                      for (var i = 0; i < ar.length; i++) {
                          if (it.indexOf(ar[i]) == -1) {
                              return false;
                          }
                      }
                      return true;
                  },
                  afterSelect: function(item){
                      // (re)load page
                      if (window.location.href.split("#")[0].indexOf(item.id.split("#")[0]) > -1 ) {
                          // same url, different hash marker
                          window.location.href = item.id;
                          window.location.reload();
                      } else {
                          window.location.href = item.id;
                      }
                  }
              });
          }
      });

      //change search input size on focus() to fit results
      $("#menu_search_box").focus(function(){
          $("#menu_search_box").css('width', '450px');
          $("#system_status").hide();
      });
      $("#menu_search_box").focusout(function(){
          $("#menu_search_box").css('width', '250px');
          $("#system_status").show();
      });
      // convert input form tables for better mobile experience
      hook_stacked_form_tables(".opnsense_standard_table_form");
    });
    //]]>
    </script>
<?php if (get_themed_filename('/js/theme.js', true)): ?>
    <script src="<?= cache_safe(get_themed_filename('/js/theme.js')) ?>"></script>
<?php endif ?>
</head>
