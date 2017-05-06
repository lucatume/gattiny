<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('activate the plugin on site that does not support it');

$I->haveOptionInDatabase('active_plugins', []);
$I->haveOptionInDatabase('gattiny_supported', 0);

$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->activatePlugin('gattiny');

$I->seeElement('.gattiny_Notice--unsupported');
$I->seePluginDeactivated('gattiny');
