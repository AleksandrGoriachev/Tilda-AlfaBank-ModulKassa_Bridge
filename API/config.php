<?php
//  DB connect initial parameters
define("DB_SERVER", "db_server_name");
define("DB_USER", "db_user_name");
define("DB_PASSWORD", "db_access_password");
define("DB_NAME", "db_name");


//   Connection to Alfa Bank
define("GET_ORDER_STATUS", "getOrderStatus.do");
define("GET_EXT_ORDER_STATUS", "getOrderStatusExtended.do");

define("ALFA_SERVER", "alfa_server_name");
define("ALFA_USER", "alfa_user_name");
define("ALFA_PASSWORD", "alfa_access_password");


//    Connection to ModulKassa
define("MODUL_SERVER", "production_or_development_modul_server"); //Production or development server
define("MODUL_USER", "modul_user_name");  // UserName for the production or  development
define("MODUL_PASSWORD", "modul_access_password");   // Password for the production or development
define("MODUL_TERMINAL", "modul_terminal");  // This parameter is being used only once per terminal connection to the Modul system

//   Processing parameters
define("TIME_LIMIT", 90); //Period of time while money can be refunded to a customer in days. Assigned in accordance with company rules
define("SUCCESS_URL", "success_page_on_your_site"); //   Enter full URL of your success page

//   Directory for logs storage
define("LOG_DIR", __DIR__."/logs/");