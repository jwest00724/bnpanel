<?php
/* For licensing terms, see /license.txt */

define("PAGE", "Admin Area");
require_once '../includes/compiler.php';


/**
 * 
 * @todo Important TODO message
 * 
 * 
 * This acp function should be change so everytime we called an URL like this:
 * 
 * ?page=servers&sub=show&do=1 
 * 
 * the page::show() function should be called
 * 
 * Then we can add URL friendly so we should load this page: 
 * server/show/1 
 * 
 * when in fact we are loading this:
 * 
 * page=servers&sub=show&do=1
 * 
 * That means changing everything in the page class and while loading every controller
 * 
 * example
 * page::add
 * page::show
 * page::update
 * page::delete
 * page::list
 * 
 * This is more like the Akelos controller class. See the example I already did with the Billing Cycle page:
 *  
 * admin/pages/billing.php, 
 * includes/class_billing.php 
 * includes/tpl/billing 
 * 
 * 
 * */

//Main ACP Function - Creates the ACP basically
function acp() {
	global $main, $db, $style, $type, $email, $user;
	
	if (!isset($main->getvar['page'])) { 
		$main->getvar['page'] = 'home';
	}
	
	$admin_navigation = $main->getAdminNavigation();
	$admin_nave_item = false;
	
	if (isset($admin_navigation[$main->getvar['page']]) && !empty($admin_navigation[$main->getvar['page']])) {
		$admin_nave_item = $admin_navigation[$main->getvar['page']];
	}
		
	$link 	= 'pages/home.php';
	$header = null;
	
	if (isset($admin_nave_item) && !empty($admin_nave_item)) {
		if ($admin_nave_item['link'] != 'home') {
			$header = $admin_nave_item['visual'];
		}		
		$link 	= 'pages/'. $admin_nave_item['link'].'.php';
	}
	
	// Left menu	
	$array['LINKS'] = '';
	
	foreach ($admin_navigation as $row) {
		if ($main->checkPerms($row['link'])) {
			$array_item['IMGURL'] 	= $row['icon'];			
			$array_item['LINK'] 	= "?page=".$row['link'];
			$array_item['VISUAL']	= $row['visual'];
			
			/*if ($row['link'] == $admin_nave_item['link']) {
				$array_item['ACTIVE'] 	= 'active';
			} else {
				$array_item['ACTIVE'] 	=	 ' ';
			}*/

			$array['LINKS'] 	   .= $style->replaceVar("menu/leftmenu_link.tpl", $array_item);
		}
	}
	//Adding the logout link
	$array_item['IMGURL'] = "logout.png";
	$array_item['LINK'] = "?page=logout";
	$array_item['VISUAL'] = "Logout";
	$array['LINKS'] .= $style->replaceVar("menu/leftmenu_link.tpl", $array_item);	
		
	$sidebar = $style->replaceVar("menu/leftmenu_main.tpl", $array);		

	$user_permission = true;
	if (!file_exists($link)) {	
		$html = "<strong>Fatal Error:</strong> Seems like the .php is non existant. Is it deleted?";	
	} elseif(!$main->checkPerms($admin_nave_item['link'])) {
		$user_permission = false;		
		$html = "You don't have access to the {$admin_nave_item['visual']} page";	
	} else {	
		//If deleting something
		//&& $main->linkAdminMenuExists($main->getvar['page']) == true
		if (preg_match("/[\.*]/", $main->getvar['page']) == 0  ) {	
			require $link;
			$content = new page();
			
			//Page Sidebar
			
			$sidebar_link_link 	= "menu/leftmenu_link.tpl";
			$sidebar_link 		= "menu/leftmenu_main.tpl";	
								
			if (isset($main->getvar['sub']) && $main->getvar['sub'] && $admin_nave_item['link'] != "type") {				
				if (is_array($content->navlist)) {
					foreach($content->navlist as $key => $value) {
						if($value[2] == $main->getvar['sub']) {
							if (!$value[0]) {
								define("SUB", $admin_nave_item['link']);	
								$header = $admin_nave_item['link'];
							} else {
								define("SUB", $value[0]);
								$header = $value[0];
							}
						}
					}
				}
			}		
			$array['HIDDEN'] = '';
			if (isset($main->getvar['sub']) && $main->getvar['sub'] == 'delete' && isset($main->getvar['do']) && !$_POST && !isset($main->getvar['confirm'])) {
				if (!empty($main->postvar)) {				
					foreach($main->postvar as $key => $value) {
						$array['HIDDEN'] .= '<input name="'.$key.'" type="hidden" value="'.$value.'" />';
					}					
				}			
				$array['HIDDEN'] .= " ";				
				$html = $style->replaceVar("tpl/warning.tpl", $array);				
			} elseif(isset($main->getvar['sub']) && $main->getvar['sub'] == "delete" && isset($main->getvar['do']) && $_POST && !isset($main->getvar['confirm'])) {
				if ($main->postvar['yes']) {	
					foreach($main->getvar as $key => $value) {
					  if($i) {
						  $i = "&";	
					  }
					  else {
						  $i = "?";	
					  }
					  $url .= $i . $key . "=" . $value;
					}
					$url .= "&confirm=1";
					$main->redirect($url);
				} elseif($main->postvar['no']) {
					$main->done();	
				}
			} else {
				$html = '';
										
					/** 
					 * 	Experimental changes only applied to the billing cycle objects otherwise work as usual
					 * 	 */
					if (isset($content->pagename)) {
						$method_list = array('add', 'edit', 'delete', 'show', 'listing');
						$sub = $main->get_variable('sub');
						if (in_array($sub, $method_list)) {
							$content->$sub();
						} else {
							$content->listing();
						}
					} else {														
						$content->content();						
					}				
			}
		} else {
			$html = "You trying to hack me? You've been warned. An email has been sent.. May I say, Owned?";
			$email->staff("Possible Hacking Attempt", "A user has been logged trying to hack your copy of BNPanel, their IP is: ". $main->removeXSS($_SERVER['REMOTE_ADDR']));
		}
	}
	
	$staffuser = $db->staff($main->getCurrentStaffId());
	
	define("INFO", '<b>Welcome back, '. strip_tags($staffuser['name']) .'</b><br />');
	$style->assign('sidebar',  $sidebar);
	$style->assign('sub_menu', $content->get_submenu());
	
	if (!empty($content->content)) {		
		$style->assign('content', $content->content);
	}	
}

//If user is NOT log in 
if (!isset($_SESSION['logged'])) {	
	if (isset($main->getvar['page']) && $main->getvar['page'] == "forgotpass") {
		define("SUB", "Reset Password");
		define("INFO", SUB);
	
		$array = array();
		if ($_POST && $main->checkToken()) {
			if (!empty($main->postvar['user']) && !empty($main->postvar['email']) ) {
				$username 		= $main->postvar['user'];
				$useremail		= $main->postvar['email'];			
				$staff_info 	= $staff->getStaffUserByUserName($username);
				
				if (!empty($staff_info)) {
					$password = $main->generatePassword();
					$params['password'] = $password;
					$staff->edit($staff_info['id'], $params);
					
					$main->errors("Password reset, please check your email");
					$array['PASS'] = $password;
					$emaildata = $db->emailTemplate("areset");
					$email->send($staff_info['email'], $emaildata['subject'], $emaildata['content'], $array);
					$main->generateToken();
				} else {
					$main->errors("That account doesn't exist");
				}
			}
		}
		$content['content'] =  $style->replaceVar("tpl/login/reset.tpl", $array);		
		echo $style->replaceVar("layout/one-col/index.tpl", $content);		
		
	} else { 		
		define("SUB", "Login");
		define("INFO", " ");		
		if ($_POST) {
			if ($main->checkToken()) {
				if($main->staffLogin($main->postvar['user'], $main->postvar['pass'])) {
					$main->redirect("?page=home");	
				} else {
					$main->errors("Incorrect username or password!");					
					$main->generateToken();
				}
			}
		}		
		$login = $style->fetch("login/alogin.tpl");
		$style->assign('content', $login);
		echo $style->display("layout/one-col/index.tpl");		
	}
} elseif(isset($_SESSION['logged'])) {	
	//Ok user is already in 
	if(!isset($main->getvar['page'])) {
		$main->getvar['page'] = "home";
	} elseif($main->getvar['page'] == "logout") {
		$main->logout('admin');		
		$main->redirect("?page=home");
	}
	
	acp();	
	echo $style->display("layout/two-col/index.tpl");
}