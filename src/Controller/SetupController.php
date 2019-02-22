<?php
namespace App\Controller;

use App\Form\SetupDatabaseForm;
use App\Form\SetupAdministrativeForm;
use App\Purple\PurpleProjectGlobal;
use App\Purple\PurpleProjectSetup;
use App\Purple\PurpleProjectSettings;
use App\Purple\PurpleProjectApi;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Http\Exception\NotFoundException;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Carbon\Carbon;

class SetupController extends AppController
{
	public function initialize()
	{
	    parent::initialize();
	    // Temporary images folder
	    $dir = new Folder(TMP . 'uploads', true, 0777);
		$dir = new Folder(TMP . 'uploads' . DS . 'images', true, 0777);
		$dir = new Folder(TMP . 'uploads' . DS . 'themes', true, 0777);

		// Storing images and thumbnails
		$dir = new Folder(WWW_ROOT . 'uploads', true, 0777);
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'images', true, 0777);
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'images' . DS .'thumbnails', true, 0777);
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'images' . DS .'thumbnails' . DS . '300x300', true, 0777);
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'images' . DS .'thumbnails' . DS . '480x270', true, 0777);

		// Storing documents
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'documents', true, 0777);

		// Storing videos
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'videos', true, 0777);

		// Storing custom pages file
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'custom-pages', true, 0777);

		// Storing themes
		$dir = new Folder(WWW_ROOT . 'uploads' . DS . 'themes', true, 0777);

	}
	public function index()
	{	    
		$purpleGlobal = new PurpleProjectGlobal();
		$databaseInfo   = $purpleGlobal->databaseInfo();
		if ($databaseInfo == 'default') {
			$setupDatabase = new SetupDatabaseForm();

	        if ($this->request->is('get')) {
		    	$this->viewBuilder()->setLayout('setup');
	        	$this->set('setupDatabase', $setupDatabase);
	        }
		}
		else {
			return $this->redirect('/');
	    }
	}
	public function administrative()
	{
		$connection = ConnectionManager::get('default');
        $connected = $connection->connect();

        if ($connected) {
			$purpleGlobal = new PurpleProjectGlobal();
			$databaseInfo   = $purpleGlobal->databaseInfo();
			if ($databaseInfo == 'default') {
				return $this->redirect(
		            ['controller' => 'Setup', 'action' => 'index']
		        );
			}
			else {
				$setupAdministrative = new SetupAdministrativeForm();
                $purpleSetup         = new PurpleProjectSetup();
                $timezoneList        = $purpleSetup->generateTimezoneList();
                
                $data = [
                    'setupAdministrative' => $setupAdministrative,
                    'timezoneList'        => $timezoneList
                ];

		        if ($this->request->is('get')) {
			    	$this->viewBuilder()->setLayout('setup');
		        	$this->set($data);
		        }
			}
		}
		else {
			return $this->redirect(
	            ['controller' => 'Setup', 'action' => 'index']
	        );
		}
	}
	public function finish()
	{
		$connection = ConnectionManager::get('default');
        $connected = $connection->connect();

        if ($connected) {
			$purpleGlobal = new PurpleProjectGlobal();
			$databaseInfo   = $purpleGlobal->databaseInfo();
			if ($databaseInfo == 'default') {
			    return $this->redirect(
		            ['controller' => 'Setup', 'action' => 'index']
		        );
			}
			else {
			    $this->viewBuilder()->setLayout('setup');
			    $results = $connection
				    ->execute('SELECT * FROM admins WHERE id = :id', ['id' => 2])
				    ->fetch('assoc');
	        	$this->set('admin', $results);
			}
		}
		else {
			return $this->redirect(
	            ['controller' => 'Setup', 'action' => 'index']
	        );
		}
	}

	// Ajax Proccessing
	public function ajaxDatabase()
	{
		$setupDatabase = new SetupDatabaseForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($setupDatabase->execute($this->request->getData())) {
				$name     = trim(strtolower($this->request->getData('name')));
				$username = trim($this->request->getData('username'));
				$password = trim($this->request->getData('password'));

				if (strpos($name, ' ') !== false) {
	                $json = json_encode(['status' => 'error', 'error' => "Invalid database name. Please try again."]);
				}
				else {
					$databaseInfo = $name . ',' . $username .',' . $password;

					$file      = new File(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'database.php');
					$encrypted = \Dcrypt\Aes::encrypt($databaseInfo, CIPHER);

	            	if ($file->write($encrypted)) {
		                $json = json_encode(['status' => 'ok']);
		            }
		            else {
		                $json = json_encode(['status' => 'error', 'error' => "Can't save database configuration."]);
		            }
		        }
            } else {
            	$errors = $setupDatabase->errors();
                $json = json_encode(['status' => 'error', 'error' => $errors, 'error_type' => 'form']);
            }
            $this->set(['json' => $json]);
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
	}
	public function ajaxAdministrative()
	{
		$setupAdministrative = new SetupAdministrativeForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($setupAdministrative->execute($this->request->getData())) {
            	$purpleApi = new PurpleProjectApi();
                $verifyEmail = $purpleApi->verifyEmail($this->request->getData('email'));

                if ($verifyEmail == true) {
	            	$connection = ConnectionManager::get('default');
	                
	                $purpleSetup = new PurpleProjectSetup();
	                $createTable   = $purpleSetup->createTable();

					$admin = TableRegistry::get('Admins')->newEntity();
	                
	                $purpleSettings = new PurpleProjectSettings();
				    $timezone       = $purpleSettings->timezone();
	                
	                $hasher        = new DefaultPasswordHasher();
	                $passwordInput = trim($this->request->getData('password'));
	                $hashPassword  = $hasher->hash(trim($this->request->getData('password')));

					$admin->username     = trim($this->request->getData('username'));
					$admin->password     = $passwordInput;
					$admin->email        = trim($this->request->getData('email'));
					$admin->level        = '1';
					$admin->created      = Carbon::now($timezone);
					$admin->display_name = trim($this->request->getData('username'));
					$admin->first_login  = 'yes';

					$updateSitename = $connection->update('settings', ['value' => trim($this->request->getData('sitename'))], ['name' => 'sitename']);
					$updateSiteurl  = $connection->update('settings', ['value' => $this->request->getData('siteurl')], ['name' => 'siteurl']);
					$updateFolder   = $connection->update('settings', ['value' => $this->request->getData('foldername')], ['name' => 'foldername']);
					$updateEmail    = $connection->update('settings', ['value' => $this->request->getData('email')], ['name' => 'email']);
	                $updateTimezone = $connection->update('settings', ['value' => $this->request->getData('timezone')], ['name' => 'timezone']);

					if (TableRegistry::get('Admins')->save($admin) && $updateSitename && $updateSiteurl && $updateFolder && $updateEmail && $updateTimezone) {
                        $record_id = $admin->id;
                        $admin     = TableRegistry::get('Admins')->get($record_id);

                        $blogCategory = TableRegistry::get('BlogCategories')->newEntity();
						$blogCategory->name     = 'Uncategorised';
						$blogCategory->slug     = 'uncategorised';
						$blogCategory->created  = Carbon::now($timezone);
						$blogCategory->ordering = '1';
						$blogCategory->admin_id = $record_id;

						TableRegistry::get('BlogCategories')->save($blogCategory);

						// Send Email to User to Notify user
                        $key    = TableRegistry::get('Settings')->settingsPublicApiKey();
                        $dashboardLink = $this->request->getData('ds');
                        $userData      = array(
                            'sitename'    => 'Purple CMS',
                            'username'    => $admin->username,
                            'password'    => trim($this->request->getData('password')),
                            'email'       => $admin->email,
                            'displayName' => $admin->display_name,
                            'level'       => $admin->level
                        );
                        $senderData   = array(
                            'domain' => $this->request->domain()
                        );
                        $notifyUser = $purpleApi->sendEmailAdministrativeSetup($key, $dashboardLink, json_encode($userData), json_encode($senderData));

                        if ($notifyUser == true) {
                            $emailNotification = true;
                        }
                        else {
                            $emailNotification = false;
                        }

						$json = json_encode(['status' => 'ok', 'email' => [$admin->email => $emailNotification]]);
		            }
		            else {
		            	$json = json_encode(['status' => 'error', 'error' => "Can't save administrative configuration to database."]);
		            }
		        }
		        else {
                    $json = json_encode(['status' => 'error', 'error' => "Email is not valid. Please use a real email."]);
		        }
            }
            else {
            	$errors = $setupAdministrative->errors();
                $json   = json_encode(['status' => 'error', 'error' => $errors, 'error_type' => 'form']);
            }
            $this->set(['json' => $json]);
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
	}
	
}