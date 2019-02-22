<?php 

	namespace Atiksoftware\Yandex;

	class Connect
	{
		private $opt_domain = "https://pddimp.yandex.ru";
		private $opt_PddToken = "";

		/**
		 * Get request from yandex connect api server
		 *
		 * @param string $path ex: /api2/admin/domain/domains
		 * @param array $query ex: ["page" => 1, "on_page" => 20]
		 * @param array $post ex: ["login" : "mansur"]
		 *
		 * @return array
		 */
		function getData($path = "", $query = [], $post = []){
			$options = [ ];
			$requestMethod = "GET";
			if(count($post)){
				$requestMethod = "POST";
				$options["form_params"] = $post;
			}
			if(count($query)){ 
				$options["query"] = $query;
			}

			$client = new \GuzzleHttp\Client([
				"headers" => [
					"PddToken" => $this->opt_PddToken 
				]
			]);
			$response = $client->request(
				$requestMethod, 
				$this->opt_domain . $path,
				$options
			);

			$body = $response->getBody();
			return json_decode($body,true) ;
		}



		/**
		 * Save data to json file for debug
		 *
		 * @param array $data
		 * @param string $filename
		 *
		 * @return void
		 */
		function saveDataToFile($data , $filename = "response.json"){
			file_put_contents($filename, json_encode($data,JSON_PRETTY_PRINT));
		}



		/**
		 * Set PddToken
		 * You can find about how can you get token in 
		 * https://tech.yandex.com.tr/kurum/doc/concepts/access-docpage/ this url
		 *
		 * @return this
		 */
		function setToken($token = ""){
			$this->opt_PddToken = $token;
			return $this;
		}

		
		function newPassword($length = 9, $add_dashes = false, $available_sets = 'luds'){
			$sets = array();
			if(strpos($available_sets, 'l') !== false)
				$sets[] = 'abcdefghjkmnpqrstuvwxyz';
			if(strpos($available_sets, 'u') !== false)
				$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
			if(strpos($available_sets, 'd') !== false)
				$sets[] = '23456789';
			if(strpos($available_sets, 's') !== false)
				$sets[] = '!@#$%&*?';
			$all = '';
			$password = '';
			foreach($sets as $set)
			{
				$password .= $set[array_rand(str_split($set))];
				$all .= $set;
			}
			$all = str_split($all);
			for($i = 0; $i < $length - count($sets); $i++)
				$password .= $all[array_rand($all)];
			$password = str_shuffle($password);
			if(!$add_dashes)
				return $password;
			$dash_len = floor(sqrt($length));
			$dash_str = '';
			while(strlen($password) > $dash_len)
			{
				$dash_str .= substr($password, 0, $dash_len) . '-';
				$password = substr($password, $dash_len);
			}
			$dash_str .= $password;
			return $dash_str;
		}



		/** Get Domain list in your account
		 * 
		 * @return array
		 * 
		 * @see in examples getDomains.php
		 */
		function getDomains(){
			$still = true;
			$page = 1;
			$count = 0;
			$list = [];
			while($still){
				$data = $this->getData("/api2/admin/domain/domains",["page" => $page, "on_page" => 20]);
				if($data["success"] == "ok" && isset($data["domains"]) && $data["found"] > 0){
					foreach($data["domains"] as $item){
						$list[] = $item;
					}
					$count += $data["found"];
					if($count >= $data["total"]){
						$still = false;
					}
					$page++;
				}
				else{
					$still = false;
				}
			}
			// $this->saveDataToFile($list,"getDomains.json");
			return $list;
		}


		/**
		 * Register a new domain to your Yandex connect account
		 *
		 * @param string $domain
		 *
		 * @return array
		 */
		function domainRegister($domain = ""){ 
			$data = $this->getData("/api2/admin/domain/register",[ ],[
				"domain" => $domain
			]);
			// $this->saveDataToFile($data,"domainRegister.json");

			return $data;
		}

		
		function domainRegisterStatus($domain = ""){ 
			$data = $this->getData("/api2/admin/domain/registration_status",[
				"domain" => $domain
			],[ ]);
			// $this->saveDataToFile($data,"domainRegisterStatus.json");

			return $data;
		}


		/**
		 * Set domain user interface Language
		 *
		 * @param string $domain Ex: ajans360.com
		 * @param string $country Ex: tr
		 * 
		 * @see https://tech.yandex.com.tr/kurum/doc/reference/domain-settings-set-country-docpage/
		 *
		 * @return void
		 */
		function setDomainCountry($domain = "", $country = "tr"){
			return $this->getData("/api2/admin/domain/settings/set_country",[],[
				"domain" => $domain,
				"country" => $country,
			]);
		}





		function getEmailsInDomain($domain = ""){
			$still = true;
			$page = 1;
			$count = 0;
			$list = [];
			while($still){
				$data = $this->getData("/api2/admin/email/list",["domain" => $domain, "page" => $page, "on_page" => 20]);
				if($data["success"] == "ok" && isset($data["accounts"]) && $data["found"] > 0){
					foreach($data["accounts"] as $item){
						$list[] = $item;
					}
					$count += $data["found"];
					if($count >= $data["total"]){
						$still = false;
					}
					$page++;
				}
				else{
					$still = false;
				}
			}
			// $this->saveDataToFile($list,"getEmainsInDomain.json");
			return $list;
		}



		/**
		 * Add new Email to domain
		 *
		 * @param string $domain Ex: ajans360.com
		 * @param string $login Ex: mansur
		 * @param string $password contain from 6 to 20 characters — 
		 * Latin letters, numbers, and the symbols 
		 * “!”, “@”, “#”, “$”, “%”, “^”, “&”, “*”, “(”, “)”, “_”, “-”, “+”, “:”, “;”, “,”, “.”
		 * be different from the username. 
		 * 
		 * @return array
		 */
		function addEmainToDomain($domain = "", $login = "", $password = ""){
			$data = $this->getData("/api2/admin/email/add",[],[
				"domain"   => $domain,
				"login"    => $login,
				"password" => $password,
			]);
			// $this->saveDataToFile($data,"addEMainToDomain.json");
			// $this->saveDataToFile([$password],"addEMainToDomainPassword.json");
			return $data;
		}


		/** Edit an email informations in domain 
		 *
		 * @param string $domain Ex: gift14.com
		 * @param string $login Ex:info
		 * @param array $params
		 * 	domain=<domain name>
			&(login=<email address or username for the mailbox>|uid=<mailbox ID>)
			[&password=<new password>]
			[&iname=<first name>]
			[&fname=<last name>]
			[&enabled=<mailbox status>]
			[&birth_date=<date of birth>]
			[&sex=<gender>]
			[&hintq=<secret question>]
			[&hinta=<answer to secret question>]
		 * @param bool $autofill -> if its TRUE, fill default params iname,fname,sex bla bla bla
		 *
		 * @see https://tech.yandex.com.tr/kurum/doc/reference/email-edit-docpage/
		 * 
		 * @return void
		 */
		function editEmailInDomain($domain = "", $login = "", $params = [], $autofill = true){
			$params["domain"] = $domain;
			$params["login"]  = $login;
			if($autofill){
				$params["iname"] = isset($params["iname"]) ? $params["iname"] : "Firstname";
				$params["fname"] = isset($params["fname"]) ? $params["fname"] : "Lastname";
				$params["enabled"] = isset($params["enabled"]) ? $params["enabled"] : "yes";
				$params["birth_date"] = isset($params["birth_date"]) ? $params["birth_date"] : "1980-08-08";
				$params["sex"] = isset($params["sex"]) ? $params["sex"] : "1";
				$params["hintq"] = isset($params["hintq"]) ? $params["hintq"] : "Hiroşima ve Nagazaki den sorumlu unsurların toplamı";
				$params["hinta"] = isset($params["hinta"]) ? $params["hinta"] : "3_".$this->newPassword();
			}
			$data = $this->getData("/api2/admin/email/edit",[],$params);
			// $this->saveDataToFile($data,"editEMailInDomain.json");
			// $this->saveDataToFile($params,"editEMailInDomainPrams.json");
			return $data;
		}

		/**
		 * Remove Mail account from domain
		 *
		 * @param string $domain Ex: ajans360.com
		 * @param string $login Ex: mansur
		 * 
		 * @return array
		 */
		function removeEmailInDomain($domain = "", $login = ""){
			$data = $this->getData("/api2/admin/email/del",[],[
				"domain"   => $domain,
				"login"    => $login,
			]);
			// $this->saveDataToFile($data,"removeEmailInDomain.json"); 
			return $data;
		}





		function getEmailListFromDomain($domain = ""){
			$data = $this->getData("/api2/admin/email/ml/list",[
				"domain"    => $domain
			],[ ]);
			// $this->saveDataToFile($data,"getEmailListFromDomain.json"); 
			return $data;
		}
		function addEmailListToDomain($domain = "", $maillist = ""){
			$data = $this->getData("/api2/admin/email/ml/add",[],[
				"domain"    => $domain,
				"maillist"  => $maillist,
			]);
			return $data;
		}
		function removeEmailListFromDomain($domain = "", $maillist = ""){
			$data = $this->getData("/api2/admin/email/ml/del",[],[
				"domain"    => $domain,
				"maillist"  => $maillist,
			]);
			return $data;
		}

		function getSubscribesFromEmailListInDomain($domain = "", $maillist = ""){
			$data = $this->getData("/api2/admin/email/ml/subscribers",[
				"domain"    => $domain,
				"maillist"    => $maillist
			],[ ]);
			return $data;
		}
		function addSubscribeToEmailListInDomain($domain = "", $maillist = "", $subscriber = "", $can_send_on_behalf = "yes"){
			$data = $this->getData("/api2/admin/email/ml/subscribe",[],[
				"domain"    => $domain,
				"maillist"  => $maillist,
				"subscriber"     => $subscriber,
				"can_send_on_behalf"     => $can_send_on_behalf,
			]);
			return $data;
		}
		function removeSubscribeFromEmailListInDomain($domain = "", $maillist = "", $subscriber = "" ){
			$data = $this->getData("/api2/admin/email/ml/unsubscribe",[],[
				"domain"    => $domain,
				"maillist"  => $maillist,
				"subscriber"     => $subscriber, 
			]);
			return $data;
		}

	}