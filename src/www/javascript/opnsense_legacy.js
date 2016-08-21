/**
 *    Copyright (C) 2015 Deciso B.V.
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *    shared components to use with legacy pages
 */

/**
 * hook on change events to network inputs, to maximize the subnet to 24 on ipv4 addresses
 * @param classname: classname to hook on to, select list of netmasks
 * @param data_id: data field reference to network input field
 */
function hook_ipv4v6(classname, data_id) {
  $("."+classname).each(function(){
      var selectlist_id = $(this).attr('id');
      if ($(this).data(data_id) != undefined) {
        $("#"+$(this).data(data_id)).change(function(){
          var itemValue = $(this).val();
          $("#"+selectlist_id+" > option").each(function() {
              if (parseInt($(this).val()) > 32 && itemValue.indexOf(":") == -1 ) {
                  $(this).addClass('hidden');
              } else {
                  $(this).removeClass('hidden');
              }
          });
          // select highest visible option
          if (parseInt($("#"+selectlist_id).val()) > 32 && itemValue.indexOf(":") == -1) {
            $("#"+selectlist_id+' option[value=32]').attr('selected','selected');
          }
          // when select list uses selectpicker, refresh
          if ($("#"+selectlist_id).hasClass('selectpicker')) {
            $("#"+selectlist_id).selectpicker('refresh');
          }
        });
      }
      // trigger initial onChange event
      $("#"+$(this).data(data_id)).change();
  });
}

/**
 * transform input forms for better mobile experience (stack description on top)
 * @param match: query pattern to match tables
 */
function hook_stacked_form_tables(match)
{
  $(match).each(function(){
      var root_node = $(this);
      if (root_node.is('table')) {
          row_number = 0;
          // traverse all <tr> tags
          root_node.find('tr').each(function(){
              // only evaluate children under this table or in <thead|tbody|..> element
              if (root_node.is($(this).parent()) || root_node.is($(this).parent().parent())) {
                  var children = $(this).children();
                  // copy zebra color on striped table
                  if (root_node.hasClass('table-striped')) {
                      if ( $(this).children(0).css("background-color") != 'transparent') {
                          root_node.data('stripe-color', $(this).children(0).css("background-color"));
                      }
                  }
                  if (children.length == 1) {
                      // simple seperator line, colspan = 2
                      $(this).before($(this).clone().removeAttr("id").attr('colspan', 1).addClass('hidden-sm hidden-md hidden-lg'));
                      $(this).addClass('hidden-xs');
                  } else if (children.length == 2) {
                      // form input row, create new <tr> for mobile header containing first <td> content
                      var mobile_header = $(this).clone().removeAttr("id").html("").addClass('hidden-sm hidden-md hidden-lg');
                      mobile_header.append($('<td/>').append(children.first().clone(true, true)));
                      // hide "all help" on mobile
                      if (row_number == 0 && $(this).find('td:eq(1) > i').length == 1) {
                          $(this).addClass('hidden-xs');
                      } else {
                          // annotate mobile header with a classname
                          mobile_header.addClass('opnsense-table-mobile-header');
                      }
                      $(this).before(mobile_header);
                      children.first().addClass('hidden-xs');
                  }
                  row_number++;
              }
          });
          // hook in re-apply zebra when table-striped was selected.. (on window resize and initial load)
          if (root_node.data('stripe-color') != undefined) {
              root_node.do_resize = function() {
                  var index = 0;
                  root_node.find('tr:visible').each(function () {
                      $(this).css("background-color", "inherit");
                      $(this).children().css("background-color", "inherit");
                      if ( index % 2 != 0) {
                          $(this).css("background-color", root_node.data('stripe-color'));
                      }
                      if (index == 0) {
                          // hide first visible table grid line
                          $(this).find('td, th').css('border-top-width', '0px');
                      }

                      // skip generated mobile headers (group header+content on mobile)
                      if (!$(this).hasClass('opnsense-table-mobile-header')) {
                          ++index;
                      }
                  });
              }
              $( window ).resize(root_node.do_resize);
              root_node.do_resize();
          }
      }
  });
}
