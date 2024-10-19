<?php
echo '<h2>General Settings</h2>';
echo '<form method="POST" action="options.php">';
settings_fields('magento_sync_general_settings');
do_settings_sections('magento_sync_general_settings');
echo '<button id="test_magento_connection" class="button button-primary">Test Connection</button>';
echo '<div id="connection_message" style="margin-top: 10px;"></div>';
submit_button();
echo '</form>';