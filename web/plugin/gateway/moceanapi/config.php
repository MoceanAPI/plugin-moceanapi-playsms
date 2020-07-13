<?php
defined('_SECURE_') or die('Forbidden');

$data = registry_search(0, 'gateway', 'moceanapi');
$plugin_config['moceanapi'] = $data['gateway']['moceanapi'];
$plugin_config['moceanapi']['name'] = 'MoceanAPI';
$plugin_config['moceanapi']['url'] = 'https://rest.moceanapi.com/rest/2/sms';

// smsc configuration
$plugin_config['moceanapi']['_smsc_config_'] = array(
	'APIKey' => _('API Key'),
	'APISecret'=>_('API Secret'),
	'module_sender' => _('Module sender ID'),
	'datetime_timezone' => _('Module timezone')
);
