<?php

class Session
{

	public static function init()
	{
		// すでにセッションが開始済みなら二重起動しない（多重呼び出し時の警告対策）
		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}
		ini_set('session.gc_maxlifetime', 7200); //2hour
		session_start();
	}
	public static function regenerateID()
	{
		session_regenerate_id(true);
	}
	public static function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public static function get($key)
	{
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}
	}
	public static function destroy()
	{
		// セッションが開始されていない場合は開始する
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			// セッションcookieを破棄します
			setcookie(session_name(), '', time() - 42000, '/');
		}
		// 最終的にセッションを破棄します
		session_destroy();
	}
	public static function getSessionID()
	{
		$sessionId = session_id();
		return $sessionId;
	}
	public static function getUserInfo($key)
	{
		$user_info = self::get('user_info');
		if (!empty($user_info)) {
			if (isset($key)) {
				return $user_info[$key];
			} else {
				return $user_info;
			}
		}
	}
	// Session.php 修正
	public static function setToken($action)
	{
		// すでにトークンがあるなら、新しく作らずに既存のものを返す
		$existingToken = self::getToken($action);
		if ($existingToken) {
			return $existingToken;
		}

		$token = bin2hex(random_bytes(32));
		self::set('token/' . $action, $token);
		return $token;
	}
	public static function getToken($action)
	{
		$key = 'token/' . $action;
		$token = self::get($key);
		return $token;
	}
	public static function checkToken($action, $token)
	{
		$sessionToken = self::getToken($action);
		if (empty($sessionToken)) return false;
		// タイミング攻撃を防止する安全な比較
		return hash_equals($sessionToken, $token);
	}
}
