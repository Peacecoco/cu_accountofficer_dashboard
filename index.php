<?php
declare(strict_types=1);
require __DIR__.'/include/config.php'; require __DIR__.'/include/session.php'; require __DIR__.'/class/AccountOfficer.php';
$method=$_SERVER['REQUEST_METHOD']??'GET';$action=strtolower(trim((string)($_GET['action']??'')));$body=in_array($method,['POST','PUT'],true)?(json_decode(file_get_contents('php://input'),true)?:$_POST):[];
try{$actor=currentAccountOfficer();}catch(DomainException $e){http_response_code(str_contains($e->getMessage(),'authorized')?403:401);header('Content-Type: application/json');echo json_encode(['success'=>false,'message'=>$e->getMessage()]);exit;}catch(Throwable){http_response_code(500);header('Content-Type: application/json');echo json_encode(['success'=>false,'message'=>'Account Officer service is temporarily unavailable.']);exit;}
if($method==='GET'&&$action==='session'){header('Content-Type: application/json');echo json_encode(['success'=>true,'data'=>['actor'=>$actor->id,'role'=>'Account Officer','csrf_token'=>accountCsrfToken($actor)]]);exit;}
$module=new AccountOfficer($con,accountLifecycle($con),$actor);
if($method==='GET'&&in_array($action,['refunds','queue'],true))$module->refunds($_GET['search']??null,$_GET['filter']??'approved');
if($method==='GET'&&$action==='details')$module->details((string)($_GET['ref']??''));
if($method==='POST'&&in_array($action,['creditrefund','markcredited'],true)){requireAccountCsrf($actor);$module->credit((string)($body['referencenumber']??$body['ref']??''));}
http_response_code(400);header('Content-Type: application/json');echo json_encode(['success'=>false,'message'=>'Invalid request.']);
