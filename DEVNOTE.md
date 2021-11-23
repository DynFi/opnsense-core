MVC structure
=============

Opnsense-core is based on Phalcon 3 PHP framework.

Models
------

src/opnsense/mvc/app/models/OPNsense/[module] - model directory

./[model_name].xml - description of data (fields and types) for a model
./[model_name].php - base class for model, in most cases it's just empty declaration
./Menu/Menu.xml - definition of menu entries provided by the module
./Menu/Buttons.xml - definition of widget buttons (only in DFF, not present in OPNsense)
./ACL/ACL.xml - definition of access permissions

There may be more then one model definition (xml + php file pairs), f.e. see src/opnsense/mvc/app/models/OPNsense/ClamAV 

Controllers
-----------

src/opnsense/mvc/app/controllers/OPNsense/[module] - controllers directory

./IndexController.php - required class containing indexAction - base logic for index page
./[url]Controller.php - controller for a given URL (f.e. .../module/HelpController.php will handle .../module/help URL)

Note that these controllers provide only static pages, interactions are defined as API:

./Api/[endpoint]Controller.php - controllers for API actions, (f.e. .../module/Api/ServiceController.php handles .../module/api/service).

In most cases there are two:
./Api/SettingsController.php - provides module configuration form
./Api/ServiceController.php - provides various actions

./forms/[name].xml - definitions of formulas, f.e. settings.xml form served by SettingsController.php.

Views
-----

src/opnsense/mvc/app/views/OPNsense/[module] - views directory

Views are basically Phalcon's Volt templates (https://docs.phalcon.io/3.4/pl-pl/volt) and opnsense-core uses them like this:
- HTML page is rendered statically as served by an URL controller,
- data is populated using ajax and Api controllers,
- actions are triggered with Javascript and Api controllers.

A good example of understanding how MVC works is DynFi Connection Agent plugin module:
src/opnsense/mvc/app/models/OPNsense/DFConAg
src/opnsense/mvc/app/controllers/OPNsense/DFConAg
src/opnsense/mvc/app/views/OPNsense/DFConAg


How GUI communicates with a system
==================================

Configd daemon
--------------

TODO
