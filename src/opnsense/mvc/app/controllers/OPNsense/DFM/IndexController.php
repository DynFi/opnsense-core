<?php
namespace OPNsense\DFM;
class IndexController extends \OPNsense\Base\IndexController
{
    public function indexAction() {
        $this->view->formSettings = $this->getForm("settings");
        $this->view->pick('OPNsense/DFM/index');
    }
}
