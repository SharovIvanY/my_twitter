<?php
include_once "config.php";

function debug($var, $stop = false){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
	if ($stop) die;
}

function get_url($page = ''){
	return HOST . "/$page";
}

function get_page_title($title = ''){
	if (!empty($title))
		return SITE_NAME . " - $title";
	else
		return HOST . "/$page";
}

function db(){
	try {
		return new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8;",
			DB_USER, DB_PASS,
			[
				PDO::ATTR_EMULATE_PREPARES => false,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]
		);
	} catch(PDOException $e){
		die($e>getMessage());
	}
}

function db_query($sql, $exec = false){
	if (empty($sql)) return false;
	
	if ($exec) return db()->exec($sql);

	return db()->query($sql);
}

function get_posts($user_id = 0){
	if ($user_id > 0) return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id WHERE posts.user_id = $user_id")->fetchAll();
	return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id")->fetchAll();
}

function get_user_info($login){
	return db_query("SELECT * FROM `users` WHERE `login` = '$login'")->fetch();
}

function add_user($login, $pass){
	$login = trim($login);
	$name = ucfirst($login);
	$password = password_hash($pass, PASSWORD_DEFAULT);

	return db_query("INSERT INTO `users` (`id`, `login`, `pass`, `name`) VALUES (NULL, '$login', '$password', '$name');");
}

function register_user($auth_date){
	if (empty($auth_date)) return false;

	debug($auth_date, true);
}

function login(){

}
?>