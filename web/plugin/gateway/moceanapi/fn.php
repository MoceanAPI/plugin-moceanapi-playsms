<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_SECURE_') or die('Forbidden');

// hook_sendsms
// called by main sms sender
// return true for success delivery
// $smsc : smsc
// $sms_sender : sender mobile number
// $sms_footer : sender sms footer or sms sender ID
// $sms_to : destination sms number
// $sms_msg : sms message tobe delivered
// $gpid : group phonebook id (optional)
// $uid : sender User ID
// $smslog_id : sms ID
function moceanapi_hook_sendsms($smsc, $sms_sender, $sms_footer, $sms_to, $sms_msg, $uid = '', $gpid = 0, $smslog_id = 0, $sms_type = 'text', $unicode = 0)
{
    global $plugin_config;
    global $core_config;

    _log("enter smsc:" . $smsc . " smslog_id:" . $smslog_id . " uid:" . $uid . " to:" . $sms_to, 3, "MoceanAPI_hook_sendsms");

    // override plugin gateway configuration by smsc configuration
    $plugin_config = gateway_apply_smsc_config($smsc, $plugin_config);

    $sms_sender = stripslashes($sms_sender);
    if ($plugin_config['moceanapi']['module_sender']) {
        $sms_sender = $plugin_config['moceanapi']['module_sender'];
    } else {
        $sms_sender = "63001";
    }

    $sms_footer = stripslashes($sms_footer);
    $sms_msg = stripslashes($sms_msg);
    $ok = false;

    if ($sms_footer) {
        $sms_msg = $sms_msg . $sms_footer;
    }

    // no sender config yet
    if ($sms_to && $sms_msg) {

        if ($unicode) {
            if (function_exists('mb_convert_encoding')) {
                $sms_msg = mb_convert_encoding($sms_msg, "UTF-8", "auto");
            }
        }

        $ch = curl_init($plugin_config['moceanapi']['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        $body = array(
            "mocean-api-key" => $plugin_config['moceanapi']['APIKey'],
            "mocean-api-secret" => $plugin_config['moceanapi']['APISecret'],
            "mocean-resp-format" => "JSON",
            "mocean-medium" => "playsms",
            "mocean-dlr-url" => $core_config['main']['main_website_url'] . '/plugin/gateway/moceanapi/callback.php?smslog_id=' . $smslog_id,
            "mocean-from" => "$sms_sender",
            "mocean-to" => $sms_to,
            "mocean-text" => $sms_msg,
        );

        if ($sms_type === "flash") {
            $body["mocean-mclass"] = 1;
            $body["mocean-alt-dcs"] = 1;
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body,"","&"));
        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        _log("send url:[" . $plugin_config['moceanapi']['url'] . "], body:[" . json_encode($body) . "]", 3, "moceanapi_hook_sendsms");

        if ($http_code >= 200 && $http_code <= 299) {
            _log("sent smslog_id:" . $smslog_id . " message_id:" . $response["messages"][0]["msgid"] . " smsc:" . $smsc, 2, "MoceanAPI_hook_sendsms");
            $ok = true;
            $p_status = 1;
            dlr($smslog_id, $uid, $p_status);

        } else {
            _log("invalid smslog_id:" . $smslog_id . " resp:[" . $response . "] smsc:" . $smsc, 2, "MoceanAPI_hook_sendsms");

        }
        curl_close($ch);
    }
    if (!$ok) {
        $p_status = 2;
        dlr($smslog_id, $uid, $p_status);
    }

    return $ok;
}
