<?php 

declare(strict_types=1);

use megaraid\route;

/* LOGIN & TOKEN */
route::add("api/user/login", "user@login", null, array("POST"));
route::add("api/user/logout", "user@logout", array("auth"), array("POST"));

route::add("api/version", "version@getversion", array("auth"), array("GET"));
route::add("api/adapter/count", "adapter@count", array("auth"), array("GET"));
route::add("api/adapter/{id}/info", "adapter@info", array("auth"), array("GET"));
route::add("api/adapter/{id}/overview", "adapter@getAdapterOverview", array("auth"), array("GET"));
route::add("api/adapter/{id}/virtualdrives", "virtualdrive@get", array("auth"), array("GET"));
route::add("api/adapter/{id}/config", "adapter@getConfig", array("auth"), array("GET"));
route::add("api/adapter/{id}/bootdrive", "adapter@getBootDrive", array("auth"), array("GET"));
route::add("api/adapter/{id}/patrol", "patrol@info", array("auth"), array("GET"));

route::add("api/adapter/{id}/physicaldrives", "physicaldrive@get", array("auth"), array("GET"));
route::add("api/adapter/{id}/physicaldrives/command", "physicaldrive@executeCommand", array("auth"), array("POST"));

route::add("api/sata", "sata_physicaldrive@getPhysicalDrives", array("auth"), array("GET"));


// /* TOKEN */
// pharmacademy_API::addRoute("token/refresh", "jwt_user@refreshToken", null, array("POST"));
// pharmacademy_API::addRoute("token/refresh_fromcallback", "jwt_user@refreshTokenFromCallBack", null, array("POST"));				

/* USER */
// pharmacademy_API::addRoute("user/updatepassword", "jwt_user@updatepassword", null, array("POST"));
// pharmacademy_API::addRoute("user/forgotpassword", "jwt_mailing@sendforgotpassword", null, array("POST"));

// route::add("api/user/{userid}", "jwt_user@getAuthenticatedUser", array("auth"), array("GET"));
// pharmacademy_API::addRoute("user/getme", "jwt_user@getAuthenticatedUser", array("Auth"), array("GET"));
// pharmacademy_API::addRoute("user/update", "jwt_user@updateuserinfo", array("Auth"), array("POST"));
// pharmacademy_API::addRoute("user/setpicture", "jwt_user@updatepicture", array("Auth"), array("POST"));

// //pharmacademy_API::addRoute("users/get/{userid}/delete/{now}", "users@getUsers", array("Auth"), array("POST", "GET"));

// /* PROGRAM */
// pharmacademy_API::addRoute("programs", "jwt_programs@getPrograms", array("Auth"), array("GET"));
// pharmacademy_API::addRoute("module/addwatchlist", "jwt_programs@addWatchListModule", array("Auth"), array("POST"));
// pharmacademy_API::addRoute("module/removewatchlist", "jwt_programs@removeWatchListModule", array("Auth"), array("POST"));

?>
