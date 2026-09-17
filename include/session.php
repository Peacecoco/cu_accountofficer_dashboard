<?php
declare(strict_types=1);
require_once __DIR__.'/../../idcard-system/shared/lifecycle/bootstrap.php';
use CU\IdCard\{Identity,PortalSessionAdapter,Lifecycle,LocalPaymentSimulator};
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','cookie_secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
function currentAccountOfficer(): Identity {
    $session=$_SESSION??[]; $path=getenv('CU_ACCOUNTOFFICER_IDENTITY_RESOLVER'); $resolver=static fn($login)=>null;
    $bypass=getenv('CU_AUTH_BYPASS')==='1' && (PHP_SAPI==='cli'||in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true));
    if ($bypass) { $session['loginid']='auth-bypass'; $id=getenv('CU_AUTH_BYPASS_ACCOUNT_ACTOR') ?: 'DEV_ACCOUNT_OFFICER'; $resolver=static fn($login)=>new Identity($id,['account_officer']); }
    elseif ($path) { $resolver=require $path; if (!$resolver instanceof Closure) throw new RuntimeException('Invalid Account Officer resolver configuration.'); }
    elseif (!isset($session['loginid']) && getenv('CU_ACCOUNTOFFICER_DEV_MODE')==='1' && (PHP_SAPI==='cli'||in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true))) { $session['loginid']='local-development'; $id=getenv('CU_ACCOUNTOFFICER_DEV_ACTOR') ?: 'dev-account-officer'; $resolver=static fn($login)=>new Identity($id,['account_officer']); }
    if (PHP_SAPI==='cli' && ($login=getenv('CU_ACCOUNTOFFICER_CLI_LOGIN'))) $session['loginid']=$login;
    $actor=(new PortalSessionAdapter($session,$resolver))->current(); $actor->requireRole('account_officer'); return $actor;
}
function accountCsrfToken(Identity $actor): string { $owner=$actor->id.'|account'; if (($_SESSION['account_csrf_owner']??null)!==$owner || empty($_SESSION['account_csrf'])) { $_SESSION['account_csrf_owner']=$owner; $_SESSION['account_csrf']=bin2hex(random_bytes(32)); } return $_SESSION['account_csrf']; }
function requireAccountCsrf(Identity $actor): void { $token=$_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['csrf_token']??''; if (!is_string($token)||!hash_equals(accountCsrfToken($actor),$token)) { http_response_code(403);header('Content-Type: application/json');echo json_encode(['success'=>false,'message'=>'Your session token is invalid. Refresh the page and try again.']);exit; } }
function accountLifecycle(PDO $con): Lifecycle { return new Lifecycle($con,new LocalPaymentSimulator(false)); }
