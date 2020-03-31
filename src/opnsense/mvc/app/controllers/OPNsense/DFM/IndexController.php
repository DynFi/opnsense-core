<?php
namespace OPNsense\DFM;
class IndexController extends \OPNsense\Base\IndexController
{
    public function indexAction()
    {
        // pick the template to serve to our users.
        $this->view->pick('OPNsense/DFM/index');
    }
}
