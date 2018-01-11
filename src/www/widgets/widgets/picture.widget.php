<?php

/*
        Copyright (C) 2014 Deciso B.V.
        Copyright (C) 2009 Scott Ullrich <sullrich@gmail.com>

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
        OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
        SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
        INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
        CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
        ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
        POSSIBILITY OF SUCH DAMAGE.
*/

require_once("guiconfig.inc");

if ($_GET['getpic']=="true") {
    $pic_type_s = explode(".", $config['widgets']['picturewidget_filename']);
    $pic_type = $pic_type_s[1];
    if ($config['widgets']['picturewidget']) {
        $data = base64_decode($config['widgets']['picturewidget']);
    }
    header("Content-Disposition: inline; filename=\"{$config['widgets']['picturewidget_filename']}\"");
    header("Content-Type: image/{$pic_type}");
    header("Content-Length: " . strlen($data));
    echo $data;
    exit;
}

if ($_POST) {
    if (is_uploaded_file($_FILES['pictfile']['tmp_name'])) {
        /* read the file contents */
        $fd_pic = fopen($_FILES['pictfile']['tmp_name'], "rb");
        while (($buf=fread($fd_pic, 8192)) != '') {
            // Here, $buf is guaranteed to contain data
            $data .= $buf;
        }
        fclose($fd_pic);
        if (!$data) {
            log_error("Warning, could not read file " . $_FILES['pictfile']['tmp_name']);
            die("Could not read temporary file");
        } else {
            $picname = basename($_FILES['uploadedfile']['name']);
            $config['widgets']['picturewidget'] = base64_encode($data);
            $config['widgets']['picturewidget_filename'] = $_FILES['pictfile']['name'];
            write_config("Picture widget saved via Dashboard.");
            header(url_safe('Location: /index.php'));
            exit;
        }
    }
}

?>

<div id="picture-settings" class="widgetconfigdiv" style="display:none;">
  <form action="/widgets/widgets/picture.widget.php" method="post" name="iforma" enctype="multipart/form-data">
    <table class="table table-striped">
      <tr>
        <td>
          <input name="pictfile" type="file" class="btn btn-primary formbtn" id="pictfile" size="20" />
        </td>
      </tr>
      <tr>
        <td>
          <input id="submit_pictfile_widget" name="submit_pictfile_widget" type="submit" class="btn btn-primary formbtn" value="<?= gettext('Upload') ?>" />
        </td>
      </tr>
    </table>
  </form>
</div>

<!-- hide picture if none is defined in the configuration  -->
<?php
if ($config['widgets']['picturewidget_filename'] != "") :?>
  <div id="picture-widgets" style="padding: 5px">
    <a href='/widgets/widgets/picture.widget.php?getpic=true' target='_blank'>
      <img style="border:0px solid; width:100%; height:100%" src="/widgets/widgets/picture.widget.php?getpic=true" alt="picture" />
    </a>
  </div>
<?php
endif ?>
<!-- needed to show the settings widget icon -->
<script>
//<![CDATA[
  $("#picture-configure").removeClass("disabled");
//]]>
</script>
