<?php

add_filter('json_api_controllers', 'add_Kidzou_controller');

function add_Kidzou_controller($controllers) {

  $controllers[] = 'Vote';
  $controllers[] = 'Connections';
  $controllers[] = 'Auth';
  $controllers[] = 'Users';
  // $controllers[] = 'Taxo';

  return $controllers;
}


function set_vote_controller_path() {
  return plugin_dir_path( __FILE__ ) ."vote.php";
}
function set_connections_controller_path() {
  return plugin_dir_path( __FILE__ ) ."connections.php";
}
function set_auth_controller_path() {
  return plugin_dir_path( __FILE__ ) ."auth.php";
}
function set_users_controller_path() {
  return plugin_dir_path( __FILE__ ) ."users.php";
}
// function set_clients_controller_path() {
//   return plugin_dir_path( __FILE__ ) ."../kidzou-clients/api/clients.php";
// }
// function set_taxo_controller_path() {
//   return plugin_dir_path( __FILE__ ) ."taxonomy.php";
// }


add_filter('json_api_vote_controller_path', 			      'set_vote_controller_path');
add_filter('json_api_connections_controller_path', 		  'set_connections_controller_path');
add_filter('json_api_auth_controller_path', 			      'set_auth_controller_path');
add_filter('json_api_users_controller_path',            'set_users_controller_path');
// add_filter('json_api_clients_controller_path',          'set_clients_controller_path');
// add_filter('json_api_taxo_controller_path',         'set_taxo_controller_path');


?>