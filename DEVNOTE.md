Intro
=====

In general: opnsense-core is based on Phalcon 3 PHP framework. Some basic documentation can be found here: https://docs.opnsense.org/develop.html


MVC structure
=============

Models
------

src/opnsense/mvc/app/models/OPNsense/[module] - model directory

./[model_name].xml - description of data (fields and types) for a model
./[model_name].php - base class for model, in most cases it's just empty declaration
./Menu/Menu.xml - definition of menu entries provided by the module (https://docs.opnsense.org/development/components/menusystem.html, DFF provides extra svgIcon attribute)
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

Configd is a Python-based daemon acting as interface between user and system services (https://docs.opnsense.org/development/backend/configd.html).

To run a configd command use configctl command in DFF shell:

```
configctl [service] [action] [optional_arguments]
```

For example:

```
configctl filter reload
configctl ipfw stats
configctl dfconag whoami
```


Running configd actions programmatically
----------------------------------------

To trigger action from a PHP script (f.e. some ServiceController) do:

```php
use \OPNsense\Core\Backend;

$backend = new Backend();
$returned_string = $backend->configdRun('filter reload')
$returned_string = $backend->configdRun('ipfw stats')
$returned_string = $backend->configdRun('dfconag whoami')
```


Defining configd actions
------------------------

Actions are defined in dedicated files:

src/opnsense/service/conf/actions.d/actions_[service].conf

Example entry:

```
[command_name]
command: /path/to/executable
parameters: %s %s <- means that command takes two text arguments
type: script_output <- means that script prints some text, if not - use just "script"
message: text printed after command execution
```

Convention requires to keep executables in module-dedicated directories:

src/opnsense/scripts/[module]/*



Legacy stuff
============

Not all opnsense-core has been migrated to Phalcon yet, some parts are still the old system inherited from pfSense.

opnsense-core/src/www - legacy code still used for some functionalities (less with every new OPNsense version)
src/etc/inc - most important include files, user authentication, filter rules generation etc. are placed there



Plugins
=======

To add a plugin to DFF:
- create its MVC structure
- provide the plugin definition file.

Plugin definition files are placed in:

src/etc/inc/plugins.inc.d/[plugin].inc

And should include at least the following code:

```php
function [plugin]_services() {
  // return the array of configd services provided by the plugin, see plugin sources for the format
}

function [plugin]_configure() {
  // return the dict with pairs of 'started_system_element' => 'plugin function to run when the element starts/restarts', f.e.:
  return array(
    'webgui' => array('[plugin]_init') // run [plugin]_init() on WebGUI start
  );
}
```


Updating opnsense-core for DFF
==============================

Standard procedure of updating DFFs goes:

* create a new branch (don't do this on master)

* pull new opnsense code from their repo ("opnsense" repo is already defined in .git/config):

    git pull --no-tags opnsense [version_tag]

* resolve conflicts (there are always some)

* review all Menu.xml files and ensure menu ordering is OK and svgIcon attributes are provided

* review all Buttons.xml files and add/fix button definitions where needed

* check if any plugin needs updating, used plugins are listed in "plugins" file and a script can be used here:

    ./check_plugin_updates.sh

* if there are plugins for updating - copy their newer code from opnsense-plugins repo to DFF and tune Menu.xml and Buttons.xm files

* update plist file

* install updated opnsense-core on a DFF instance and do extensive testing and fixing where needed

* after all - merge to master, go on with the release.
