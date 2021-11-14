<?php
include_once "config.php";

function debug($var, $stop = false){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
	if ($stop) die;
}

function redirect($link = HOST){
	header("Location: $link");
	die;
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

function get_posts($user_id = 0, $sort = false){
	$sorting = 'DESC';

	if ($sort) $sorting = 'ASC';

	if ($user_id > 0) return db_query("SELECT posts.*, users.name, users.login,
	  		users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id WHERE posts.user_id = $user_id
			ORDER BY `posts`.`date` $sorting;")->fetchAll();

	return db_query("SELECT posts.*, users.name, users.login,
		users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id 
		ORDER BY `posts`.`date` $sorting;")->fetchAll();
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
	if (empty($auth_date) 
		|| !isset($auth_date['login'])|| empty($auth_date['login']) 
		|| !isset($auth_date['pass']) || empty($auth_date['pass'])
		|| !isset($auth_date['pass2']) || empty($auth_date['pass2'])) 
		return false;

	$user = get_user_info($auth_date['login']);

	if (!empty($user)){
		$_SESSION['error'] = 'Пользователь ' . $auth_date['login'] . ' уже существует';
		redirect(get_url('register.php'));
	}

	if ($auth_date['pass'] !== $auth_date['pass2']){
		$_SESSION['error'] = 'Пароли не совпадают';
		redirect(get_url('register.php'));
	}

	if (add_user($auth_date['login'], $auth_date['pass'])){
		redirect(get_url());
	}
}

function login($auth_date){
	
	if (empty($auth_date) 
		|| !isset($auth_date['login'])|| empty($auth_date['login']) 
		|| !isset($auth_date['pass']) || empty($auth_date['pass'])) 
		return false;

	$user = get_user_info($auth_date['login']);

	if (empty($user)){
		$_SESSION['error'] = 'Пользователь ' . $auth_date['login'] . ' не найден';
		redirect(get_url());
	}

	if (password_verify($auth_date['pass'], $user['pass'])){
		$_SESSION['user'] = $user;
		$_SESSION['error'] = '';
		redirect(get_url('user_posts.php'));
	} else {
		$_SESSION['error'] = 'Пароль неверный';
		redirect(get_url());
	}
}

function get_error_message(){
	$error = '';
	if (isset($_SESSION['error']) && !empty($_SESSION['error'])){
		$error = $_SESSION['error'];
		$_SESSION['error'] = '';
	}
	return $error;
}

function logged_in(){
	return isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
}

function add_post($text, $image){
	$text = trim($text);
	if (mb_strlen($text) > 255){
		$text = mb_substr($text, 0, 250) . ' ...';
	}

	if (str_word_count($text) > 50){
		$text = mb_substr($text, 0, 250) . ' ...';
	}

	$text = preg_replace('/\s+/', ' ', $text);

	$user_id = $_SESSION['user']['id'];
	$sql = "INSERT INTO `posts` (`id`, `user_id`, `text`, `image`, `date`) 
	VALUES (NULL, '$user_id', '$text', '$image', CURRENT_TIMESTAMP)";
	return db_query($sql, true);
}

function delete_post($id){
	if (!is_numeric($id)) return false;
	$user_id = $_SESSION['user']['id'];
	return db_query("DELETE FROM `posts` WHERE `id` = $id AND `user_id` = $user_id;", true);
}

?>