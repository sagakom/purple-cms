<?php
namespace App\Controller\Purple;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Http\Exception\NotFoundException;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Utility\Text;
use App\Form\Purple\PageAddForm;
use App\Form\Purple\PageEditForm;
use App\Form\Purple\PageStatusForm;
use App\Form\Purple\PageBlogEditForm;
use App\Form\Purple\PageSaveForm;
use App\Form\Purple\PageCustomSaveForm;
use App\Form\Purple\PageDeleteForm;
use App\Form\Purple\BlogDeleteForm;
use App\Form\Purple\SearchForm;
use App\Form\PageContactForm;
use App\Purple\PurpleProjectGlobal;
use App\Purple\PurpleProjectFroalaBlocks;
use \Gumlet\ImageResize;

class PagesController extends AppController
{
    public $imagesLimit = 30;
    
	public function beforeFilter(Event $event)
	{
	    parent::beforeFilter($event);
	    $purpleGlobal = new PurpleProjectGlobal();
		$databaseInfo   = $purpleGlobal->databaseInfo();
		if ($databaseInfo == 'default') {
			return $this->redirect(
	            ['prefix' => false, 'controller' => 'Setup', 'action' => 'index']
	        );
		}
	}
	public function initialize()
	{
		parent::initialize();
        $this->loadComponent('RequestHandler');
		$session = $this->getRequest()->getSession();
		$sessionHost     = $session->read('Admin.host');
		$sessionID       = $session->read('Admin.id');
		$sessionPassword = $session->read('Admin.password');

		if ($this->request->getEnv('HTTP_HOST') != $sessionHost || !$session->check('Admin.id')) {
			return $this->redirect(
	            ['controller' => 'Authenticate', 'action' => 'login']
	        );
		}
		else {
	    	$this->viewBuilder()->setLayout('dashboard');
            $this->loadModel('Admins');
            $this->loadModel('Settings');

            if (Configure::read('debug') || $this->request->getEnv('HTTP_HOST') == 'localhost') {
                $cakeDebug = 'on';
            } 
            else {
                $cakeDebug = 'off';
            }

            $queryAdmin      = $this->Admins->find()->where(['id' => $sessionID, 'password' => $sessionPassword])->limit(1);
            $queryFavicon    = $this->Settings->find()->where(['name' => 'favicon'])->first();
            $queryDateFormat = $this->Settings->find()->where(['name' => 'dateformat'])->first();
            $queryTimeFormat = $this->Settings->find()->where(['name' => 'timeformat'])->first();

            $browseMedias  = TableRegistry::get('Medias')->find('all', [
                'order' => ['Medias.id' => 'DESC']])->contain('Admins');

			$rowCount = $queryAdmin->count();
            if ($rowCount > 0) {
                $adminData = $queryAdmin->first();

                $dashboardSearch = new SearchForm();
                
                $data = [
                    'sessionHost'        => $sessionHost,
                    'sessionID'          => $sessionID,
                    'sessionPassword'    => $sessionPassword,
                    'cakeDebug'          => $cakeDebug,
                    'adminName'          => ucwords($adminData->display_name),
                    'adminLevel'         => $adminData->level,
                    'adminEmail'         => $adminData->email,
                    'adminPhoto'         => $adminData->photo,
                    'greeting'           => '',
                    'dashboardSearch'    => $dashboardSearch,
                    'title'              => 'Pages | Purple CMS',
                    'pageTitle'          => 'Pages',
                    'pageTitleIcon'      => 'mdi-file-multiple',
                    'pageBreadcrumb'     => 'Pages',
                    'appearanceFavicon'  => $queryFavicon,
                    'settingsDateFormat' => $queryDateFormat->value,
                    'settingsTimeFormat' => $queryTimeFormat->value,
                    'mediaImageTotal'    => $browseMedias->count(),
                    'mediaImageLimit'    => $this->imagesLimit
		    	];
	        	$this->set($data);
                $this->set(compact('browseMedias'));
			}
			else {
				return $this->redirect(
		            ['controller' => 'Authenticate', 'action' => 'login']
		        );
			}
	    }
	}
    public function index()
    {
        $pageAdd    = new PageAddForm();
        $pageEdit   = new PageEditForm();
		$pageStatus = new PageStatusForm();
        $pageDelete = new PageDeleteForm();

		$data = [
			'pageTitle'         => 'Pages',
			'pageBreadcrumb'    => 'Pages',
			'pageAdd'           => $pageAdd,
            'pageEdit'          => $pageEdit,
            'pageStatus'        => $pageStatus,
            'pageDelete'        => $pageDelete
        ];

        $pageTemplatesTable  = TableRegistry::get('PageTemplates');
        $pageTemplates = $pageTemplatesTable->find('list')->select(['id','name'])->order(['id' => 'ASC'])->toArray();
    	$this->set(compact('pageTemplates'));

		$pages = $this->Pages->find('all', [
            'order' => ['Pages.id' => 'DESC']])->contain('PageTemplates')->contain('Admins');

        $this->set(compact('pages'));

        $this->set($data);
    }
    public function detail($type, $id, $slug) 
    {
        $tmpFile            = $type . '.' . $slug;
        $fileName           = $type . '.' . $slug . '.php';
        $generatedTempFile  = new File(TMP . 'html/' . $fileName, true, 0644);

        if ($type == 'general') {
            $pageSave = new PageSaveForm();

            $purpleFroalaBlocks = new PurpleProjectFroalaBlocks();

            $froalaCallToAction = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/call-to-action');
            $filesCallToAction  = $froalaCallToAction->find('.*\.jpg', true);
            
            $froalaContents     = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/contents');
            $filesContents      = $froalaContents->find('.*\.jpg', true);
            
            $froalaFeatures     = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/features');
            $filesFeatures      = $froalaFeatures->find('.*\.jpg', true);
            
            $froalaTeams        = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/teams');
            $filesTeams         = $froalaTeams->find('.*\.jpg', true);

            $froalaContacts     = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/contacts');
            $filesContacts      = $froalaContacts->find('.*\.jpg', true);
            
            $froalaLightbox     = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/lightbox');
            $filesLightbox      = $froalaLightbox->find('.*\.jpg', true);
            
            $froalaSlider       = new Folder(WWW_ROOT . 'master-assets/plugins/froala-blocks/images/slider');
            $filesSlider        = $froalaSlider->find('.*\.jpg', true);

            $themeBlocks        = $purpleFroalaBlocks->themeBlocks();

            $generals = $this->Pages->find('all')->contain('Generals')->where(['Pages.slug' => $slug])->limit(1);
			if ($generals->count() == 0) {
				$result = NULL;
                $generatedTempFile->write('');
			}
			else {
				$result = $this->Pages->get($id, [
				    'contain' => ['Generals']
				]);
                $generatedTempFile->write(html_entity_decode($result->general->content));
			}

            $froalaBlocks = [
                'fdbCallToAction' => $filesCallToAction,
                'fdbContents'     => $filesContents,
                'fdbFeatures'     => $filesFeatures,
                'fdbTeams'        => $filesTeams,
                'fdbContacts'     => $filesContacts,
                'fdbLightbox'     => $filesLightbox,
                'fdbSlider'       => $filesSlider,
                'themeBlocks'     => $themeBlocks,
                'pageSave'        => $pageSave,
                'query'           => $result,
                'tmpFile'         => $tmpFile
            ];

            $this->set($froalaBlocks);
        }
        elseif ($type == 'blog') {
            $pageBlogEdit = new PageBlogEditForm();
            $blogDelete   = new BlogDeleteForm();

            $this->loadModel('Blogs');
            $this->loadModel('BlogCategories');

            $blogs = $this->Blogs->find('all')->contain('BlogCategories')->contain('Admins')->where(['BlogCategories.page_id' => $id]);

            $this->set(compact('blogs'));
            $data = [
                'pageBlogEdit' => $pageBlogEdit,
                'blogDelete'   => $blogDelete
            ];
            $this->set($data);
        }
        elseif ($type == 'custom') {
            $pageSave = new PageCustomSaveForm();

            $customPages = $this->Pages->find('all')->contain('CustomPages')->where(['Pages.slug' => $slug])->limit(1);
            if ($customPages->count() == 0) {
                $result = NULL;
                $code   = NULL;
            }
            else {
                $result = $this->Pages->get($id, [
                    'contain' => ['CustomPages']
                ]);
                $readFile  = new File(WWW_ROOT . 'uploads' . DS . 'custom-pages' . DS . $customPages->first()->custom_page->file_name);
                $code = $readFile->read();
            }

            $data = [
                'query'    => $result,
                'pageSave' => $pageSave,
                'code'     => $code
            ];

            $this->set($data);
        }

        if ($id == 0 && $slug == 'purple-home-page-builder') {
            $settingsTable  = TableRegistry::get('Settings');
            $queryHomepage  = $settingsTable->find()->where(['name' => 'homepagestyle'])->first();

            $generatedTempFile->write(html_entity_decode($queryHomepage->value));

            $data = [
                'pages'           => NULL,
                'type'            => $type,
                'homePageContent' => html_entity_decode($queryHomepage->value),
                'pageId'          => 0,
                'pageTitle'       => 'Home',
                'pageBreadcrumb'  => 'Pages::Home',
                'tmpFile'         => $tmpFile
            ];
        }
        else {
            $pages = $this->Pages->find('all', [
                'order' => ['Pages.id' => 'DESC']])->contain('PageTemplates')->contain('Admins')->where(['Pages.slug' => $slug])->first();

            $data = [
                'pages'          => $pages,
                'type'           => $type,
                'pageId'         => $pages->id,
                'pageTitle'      => $pages->title,
                'pageBreadcrumb' => 'Pages::'.$pages->title,
            ];
        }

        $this->set($data);
    }
    public function generatedBlocks($file)
    {
        $this->viewBuilder()->setLayout('blocks');

        $filePath = new File(TMP . 'html/' . $file . '.php', false);
        $content  = $filePath->read();
        $this->set('content', $content);
    }
    public function ajaxAdd() 
    {
        $this->viewBuilder()->autoLayout(false);

        $pageAdd = new PageAddForm();
        if ($this->request->is('ajax')) {
            if ($pageAdd->execute($this->request->getData())) {
                $session   = $this->getRequest()->getSession();
                $sessionID = $session->read('Admin.id');

                $reservedText = ['purple', 'setup', 'posts', 'tag'];

                $slug = Text::slug(strtolower($this->request->getData('title')));
                $findDuplicate = $this->Pages->find()->where(['slug' => $slug]);
                if ($findDuplicate->count() >= 1) {
                    $json = json_encode(['status' => 'error', 'error' => "Can't save data due to duplication of data. Please try again with another title."]);
                }
                else {
                    if (in_array($slug, $reservedText)) {
                        $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please use another title, because the title is reserved word by Purple CMS. Please try again."]);
                    }
                    else {
                        $page = $this->Pages->newEntity();
                        $page = $this->Pages->patchEntity($page, $this->request->getData());
                        $page->admin_id = $sessionID;
    				
    	                if ($this->Pages->save($page)) {
    	                    $json = json_encode(['status' => 'ok']);
    	                }
    	                else {
    	                    $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
    	                }
                    }
				}
            }
            else {
            	$errors = $pageAdd->errors();
                $json = json_encode(['status' => 'error', 'error' => "Make sure you don't enter the same title and please fill all field."]);
            }

            $this->set(['json' => $json]);
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
    }
    public function ajaxSave() 
    {
        $this->viewBuilder()->autoLayout(false);

        $pageSave = new PageSaveForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($pageSave->execute($this->request->getData())) {
                $session   = $this->getRequest()->getSession();
                $sessionID = $session->read('Admin.id');

                if ($this->request->getData('id') == 0) {
                    $settingsTable  = TableRegistry::get('Settings');
                    $queryHomepage  = $settingsTable->find()->where(['name' => 'homepagestyle'])->first();
                    $setting        = $settingsTable->get($queryHomepage->id);
                    $setting->value = trim(htmlentities($this->request->getData('content')));

                    if ($settingsTable->save($setting)) {
                        /**
                         * Save user activity to histories table
                         * array $options => title, detail, admin_id
                         */
                        
                        $options = [
                            'title'    => 'Update Home Page Content',
                            'detail'   => ' update home page content of your website.',
                            'admin_id' => $sessionID
                        ];

                        $historiesTable = TableRegistry::get('Histories');
                        $saveActivity   = $historiesTable->saveActivity($options);

                        if ($saveActivity == true) {
                            $json = json_encode(['status' => 'ok', 'activity' => true]);
                        }
                        else {
                            $json = json_encode(['status' => 'ok', 'activity' => false]);
                        }
                    }
                    else {
                        $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
                    }
                }
                else {
                    $slug = Text::slug(strtolower($this->request->getData('title')));

                    $reservedText = ['purple', 'setup', 'posts', 'tag'];

                    if (in_array($slug, $reservedText)) {
                        $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please use another title, because the title is reserved word by Purple CMS. Please try again."]);
                    }
                    else {
                        $generalsTable  = TableRegistry::get('Generals');
                        $generals = $generalsTable->find()->where(['page_id' => $this->request->getData('id')]);

                        if ($generals->count() < 1) {
                            $general = $generalsTable->newEntity();

                            $general->content          = trim($this->request->getData('content'));
                            $general->meta_keywords    = $this->request->getData('meta_keywords');
                            $general->meta_description = $this->request->getData('meta_description');
                            $general->page_id          = $this->request->getData('id');
                            $general->admin_id         = $sessionID;

    						$page = $this->Pages->get($this->request->getData('id'));
    		                $page->title = $this->request->getData('title');

    	                    if ($generalsTable->save($general) && $this->Pages->save($page)) {
                                $record_id = $page->id;
                                $page      = $this->Pages->get($record_id);
                                $title     = $page->title;
                                /**
                                 * Save user activity to histories table
                                 * array $options => title, detail, admin_id
                                 */
                                
                                $options = [
                                    'title'    => 'Content Making of a Page',
                                    'detail'   => ' add content in '.$title.'.',
                                    'admin_id' => $sessionID
                                ];

                                $historiesTable = TableRegistry::get('Histories');
                                $saveActivity   = $historiesTable->saveActivity($options);

                                if ($saveActivity == true) {
                                    $json = json_encode(['status' => 'ok', 'activity' => true]);
                                }
                                else {
                                    $json = json_encode(['status' => 'ok', 'activity' => false]);
                                }
    	                    }
    	                    else {
    	                        $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
    	                    }
                        }
                        else {
                            $generals = $generalsTable->find()->where(['page_id' => $this->request->getData('id')])->first();
                            $general  = $generalsTable->get($generals->id);

                            $general->content          = trim($this->request->getData('content'));
                            $general->meta_keywords    = $this->request->getData('meta_keywords');
                            $general->meta_description = $this->request->getData('meta_description');
                            $general->page_id          = $this->request->getData('id');
                            $general->admin_id         = $sessionID;

        					$findDuplicate = $this->Pages->find('all')->where(['slug' => $slug, 'id <>' => $this->request->getData('id')]);
        					if ($findDuplicate->count() >= 1) {
        						$json = json_encode(['status' => 'error', 'error' => "Can't save data due to duplication of data. Please try again with another title."]);
        					}
        					else {
        						$page = $this->Pages->get($this->request->getData('id'));
        		                $page->title = $this->request->getData('title');

        	                    if ($generalsTable->save($general) && $this->Pages->save($page)) {
        	                        $record_id = $page->id;
                                    $page      = $this->Pages->get($record_id);
                                    $title     = $page->title;
                                    /**
                                     * Save user activity to histories table
                                     * array $options => title, detail, admin_id
                                     */
                                    
                                    $options = [
                                        'title'    => 'Content Update of a Page',
                                        'detail'   => ' update content in '.$title.'.',
                                        'admin_id' => $sessionID
                                    ];

                                    $historiesTable = TableRegistry::get('Histories');
                                    $saveActivity   = $historiesTable->saveActivity($options);

                                    if ($saveActivity == true) {
                                        $json = json_encode(['status' => 'ok', 'activity' => true]);
                                    }
                                    else {
                                        $json = json_encode(['status' => 'ok', 'activity' => false]);
                                    }
        	                    }
        	                    else {
        	                        $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
        	                    }
        					}
                        }
                    }
                }
            }
            else {
            	$errors = $pageSave->errors();
                $json = json_encode(['status' => 'error', 'error' => $errors]);
            }

            $this->set(['json' => $json]);
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
    }
    public function ajaxChangeStatus() 
    {
        $this->viewBuilder()->autoLayout(false);

        $pageStatus = new PageStatusForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($pageStatus->execute($this->request->getData())) {
                $session   = $this->getRequest()->getSession();
                $sessionID = $session->read('Admin.id');

                $page         = $this->Pages->get($this->request->getData('id'));
                $title        = $page->title;
                $page->status = $this->request->getData('status');

                if ($this->request->getData('status') == '0') {
                    $statusText = 'draft';
                }
                else if ($this->request->getData('status') == '1') {
                    $statusText = 'publish';
                }

                if ($this->Pages->save($page)) {
                    /**
                     * Save user activity to histories table
                     * array $options => title, detail, admin_id
                     */
                    
                    $options = [
                        'title'    => 'Change Status of a Page',
                        'detail'   => ' change status '.$title.' in pages to '.$statusText.'.',
                        'admin_id' => $sessionID
                    ];

                    $historiesTable = TableRegistry::get('Histories');
                    $saveActivity   = $historiesTable->saveActivity($options);

                    if ($saveActivity == true) {
                        $json = json_encode(['status' => 'ok', 'activity' => true]);
                    }
                    else {
                        $json = json_encode(['status' => 'ok', 'activity' => false]);
                    }
                }
                else {
                    $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
                }
            }
            else {
                $errors = $pageStatus->errors();
                $json = json_encode(['status' => 'error', 'error' => $errors]);
            }

            $this->set(['json' => $json]);
        }
        else {
            throw new NotFoundException(__('Page not found'));
        }
    }
    public function ajaxSaveBlogPage() 
    {
        $this->viewBuilder()->autoLayout(false);

        $pageBlogEdit = new PageBlogEditForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($pageBlogEdit->execute($this->request->getData())) {
                $session   = $this->getRequest()->getSession();
                $sessionID = $session->read('Admin.id');

                $slug = Text::slug(strtolower($this->request->getData('title')));

                $reservedText = ['purple', 'setup', 'posts', 'tag'];

                if (in_array($slug, $reservedText)) {
                    $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please use another title, because the title is reserved word by Purple CMS. Please try again."]);
                }
                else {
                    $page  = $this->Pages->get($this->request->getData('id'));
                    $title = $page->title;
                    $page  = $this->Pages->patchEntity($page, $this->request->getData());
                    
                    if ($this->Pages->save($page)) {
                        $record_id = $page->id;
                        $page      = $this->Pages->get($record_id);
                        $newTitle  = $page->title;

                        /**
                         * Save user activity to histories table
                         * array $options => title, detail, admin_id
                         */
                        
                        $options = [
                            'title'    => 'Data Change of a Page',
                            'detail'   => ' change page title from '.$title.' to '.$newTitle.'.',
                            'admin_id' => $sessionID
                        ];

                        $historiesTable = TableRegistry::get('Histories');
                        $saveActivity   = $historiesTable->saveActivity($options);

                        if ($saveActivity == true) {
                            $json = json_encode(['status' => 'ok', 'activity' => true]);
                        }
                        else {
                            $json = json_encode(['status' => 'ok', 'activity' => false]);
                        }
                    }
                    else {
                        $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
                    }
                }
            }
            else {
                $errors = $pageBlogEdit->errors();
                $json = json_encode(['status' => 'error', 'error' => $errors]);
            }

            $this->set(['json' => $json]);
        }
        else {
            throw new NotFoundException(__('Page not found'));
        }
    }
    public function ajaxSaveCustomPage() 
    {
        $this->viewBuilder()->autoLayout(false);

        $pageCustomSave = new PageCustomSaveForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($pageCustomSave->execute($this->request->getData())) {
                $session   = $this->getRequest()->getSession();
                $sessionID = $session->read('Admin.id');

                $slug = Text::slug(strtolower($this->request->getData('title')));
                    
                $reservedText = ['purple', 'setup'];

                if (in_array($slug, $reservedText)) {
                    $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please use another title, because the title is reserved word by Purple CMS. Please try again."]);
                }
                else {
                    $customPagesTable = TableRegistry::get('CustomPages');
                    $customPages      = $customPagesTable->find()->where(['page_id' => $this->request->getData('id')]);

                    if ($customPages->count() < 1) {
                        $fileName   = Text::slug(strtolower($this->request->getData('title'))) . '-' . time() . '.php';
                        $customPage = $customPagesTable->newEntity();

                        $customPage->file_name        = $fileName;
                        $customPage->meta_keywords    = $this->request->getData('meta_keywords');
                        $customPage->meta_description = $this->request->getData('meta_description');
                        $customPage->page_id          = $this->request->getData('id');
                        $customPage->admin_id         = $sessionID;

                        // Create php file with code editor content
                        $createFile = new File(WWW_ROOT . 'uploads' . DS . 'custom-pages' . DS . $fileName, true, 0644);
                        $writeFile  = new File(WWW_ROOT . 'uploads' . DS . 'custom-pages' . DS . $fileName);
                        $writeFile->write($this->request->getData('content'));

                        $page = $this->Pages->get($this->request->getData('id'));
                        $page->title = $this->request->getData('title');

                        if ($customPagesTable->save($customPage) && $this->Pages->save($page)) {
                            $record_id = $page->id;
                            $page      = $this->Pages->get($record_id);
                            $title     = $page->title;
                            /**
                             * Save user activity to histories table
                             * array $options => title, detail, admin_id
                             */
                            
                            $options = [
                                'title'    => 'Content Making of a Page',
                                'detail'   => ' add custom code in '.$title.'.',
                                'admin_id' => $sessionID
                            ];

                            $historiesTable = TableRegistry::get('Histories');
                            $saveActivity   = $historiesTable->saveActivity($options);

                            if ($saveActivity == true) {
                                $json = json_encode(['status' => 'ok', 'activity' => true]);
                            }
                            else {
                                $json = json_encode(['status' => 'ok', 'activity' => false]);
                            }
                        }
                        else {
                            $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
                        }
                    }
                    else {
                        $customPages = $customPagesTable->find()->where(['page_id' => $this->request->getData('id')])->first();
                        $customPage  = $customPagesTable->get($customPages->id);

                        $fileName                     = $customPage->file_name;
                        $customPage->meta_keywords    = $this->request->getData('meta_keywords');
                        $customPage->meta_description = $this->request->getData('meta_description');
                        $customPage->page_id          = $this->request->getData('id');
                        $customPage->admin_id         = $sessionID;

                        $slug = Text::slug(strtolower($this->request->getData('title')));
                        $findDuplicate = $this->Pages->find('all')->where(['slug' => $slug, 'id <>' => $this->request->getData('id')]);
                        if ($findDuplicate->count() >= 1) {
                            $json = json_encode(['status' => 'error', 'error' => "Can't save data due to duplication of data. Please try again with another title dsa."]);
                        }
                        else {
                            $writeFile  = new File(WWW_ROOT . 'uploads' . DS . 'custom-pages' . DS . $fileName);
                            $writeFile->write($this->request->getData('content'));

                            $page = $this->Pages->get($this->request->getData('id'));
                            $page->title = $this->request->getData('title');

                            if ($customPagesTable->save($customPage) && $this->Pages->save($page)) {
                                $record_id = $page->id;
                                $page      = $this->Pages->get($record_id);
                                $title     = $page->title;
                                /**
                                 * Save user activity to histories table
                                 * array $options => title, detail, admin_id
                                 */
                                
                                $options = [
                                    'title'    => 'Content Update of a Page',
                                    'detail'   => ' update custom code in '.$title.'.',
                                    'admin_id' => $sessionID
                                ];

                                $historiesTable = TableRegistry::get('Histories');
                                $saveActivity   = $historiesTable->saveActivity($options);

                                if ($saveActivity == true) {
                                    $json = json_encode(['status' => 'ok', 'activity' => true]);
                                }
                                else {
                                    $json = json_encode(['status' => 'ok', 'activity' => false]);
                                }
                            }
                            else {
                                $json = json_encode(['status' => 'error', 'error' => "Can't save data. Please try again."]);
                            }
                        }
                    }
                }
            }
            else {
                $errors = $pageCustomSave->errors();
                $json = json_encode(['status' => 'error', 'error' => $errors]);
            }

            $this->set(['json' => $json]);
        }
        else {
            throw new NotFoundException(__('Page not found'));
        }
    }
    public function ajaxDelete()
	{
		$this->viewBuilder()->autoLayout(false);

        $pageDelete = new PageDeleteForm();
        if ($this->request->is('ajax') || $this->request->is('post')) {
            if ($pageDelete->execute($this->request->getData())) {
                $session   = $this->getRequest()->getSession();
                $sessionID = $session->read('Admin.id');

                $page  = $this->Pages->get($this->request->getData('id'));
                $title = $page->title;

                // Delete custom php file if page type is custom
                if ($this->request->getData('page_type') == 'custom') {
                    $customPagesTable = TableRegistry::get('CustomPages');
                    $checkCustom      = $customPagesTable->find()->where(['page_id' => $this->request->getData('id')])->count();

                    if ($checkCustom > 0) {
                        $customPage = $customPagesTable->find()->where(['page_id' => $this->request->getData('id')])->first();
                        $fileName   = $customPage->file_name;
                        $customFile = WWW_ROOT . 'uploads' . DS .'custom-pages' . DS . $fileName;

                        if ($this->Pages->delete($page)) {
                            if (file_exists($customFile)) {
                                $readFile   = new File($customFile);
                                $readFile->delete();
                            }

                            /**
                             * Save user activity to histories table
                             * array $options => title, detail, admin_id
                             */
                            
                            $options = [
                                'title'    => 'Deletion of a Page',
                                'detail'   => ' delete '.$title.' in pages.',
                                'admin_id' => $sessionID
                            ];

                            $historiesTable = TableRegistry::get('Histories');
                            $saveActivity   = $historiesTable->saveActivity($options);

                            if ($saveActivity == true) {
                                $json = json_encode(['status' => 'ok', 'activity' => true]);
                            }
                            else {
                                $json = json_encode(['status' => 'ok', 'activity' => false]);
                            }
                        }
                        else {
                            $json = json_encode(['status' => 'error', 'error' => "Can't delete data. Please try again."]);
                        }
                    }
                    else {
                        if ($this->Pages->delete($page)) {
                            /**
                             * Save user activity to histories table
                             * array $options => title, detail, admin_id
                             */
                            
                            $options = [
                                'title'    => 'Deletion of a Page',
                                'detail'   => ' delete '.$title.' in pages.',
                                'admin_id' => $sessionID
                            ];

                            $historiesTable = TableRegistry::get('Histories');
                            $saveActivity   = $historiesTable->saveActivity($options);

                            if ($saveActivity == true) {
                                $json = json_encode(['status' => 'ok', 'activity' => true]);
                            }
                            else {
                                $json = json_encode(['status' => 'ok', 'activity' => false]);
                            }
                        }
                        else {
                            $json = json_encode(['status' => 'error', 'error' => "Can't delete data. Please try again."]);
                        }
                    }
                }
                else {
                    if ($this->Pages->delete($page)) {
                        /**
                         * Save user activity to histories table
                         * array $options => title, detail, admin_id
                         */
                        
                        $options = [
                            'title'    => 'Deletion of a Page',
                            'detail'   => ' delete '.$title.' in pages.',
                            'admin_id' => $sessionID
                        ];

                        $historiesTable = TableRegistry::get('Histories');
                        $saveActivity   = $historiesTable->saveActivity($options);

                        if ($saveActivity == true) {
                            $json = json_encode(['status' => 'ok', 'activity' => true]);
                        }
                        else {
                            $json = json_encode(['status' => 'ok', 'activity' => false]);
                        }
                    }
                    else {
                        $json = json_encode(['status' => 'error', 'error' => "Can't delete data. Please try again."]);
                    }
                }
            }
            else {
            	$errors = $pageDelete->errors();
                $json = json_encode(['status' => 'error', 'error' => $errors]);
            }

            $this->set(['json' => $json]);
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
    }
    public function ajaxFroalaBlocks()
    {
        $this->viewBuilder()->autoLayout(false);

        if ($this->request->is('ajax')) {
            $purpleFroalaBlocks = new PurpleProjectFroalaBlocks();

            $number  = $this->request->getData('number');
            $filter  = $this->request->getData('filter');
            $webroot = $this->request->getAttribute('webroot');
            $randomNumber = rand(0, 99999);

            $findBlock = $purpleFroalaBlocks->$filter($number, $webroot, $randomNumber);

            $json = json_encode(['status' => 'ok', 'id' => $randomNumber, 'html' => $findBlock]);
            $this->set(['json' => $json]);
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
    }
    public function ajaxThemeBlocks()
    {
        $this->viewBuilder()->autoLayout(false);

        if ($this->request->is('ajax')) {
            $purpleFroalaBlocks = new PurpleProjectFroalaBlocks();

            $number  = $this->request->getData('number');
            $filter  = $this->request->getData('filter');
            $webroot = $this->request->getAttribute('webroot');
            $randomNumber = rand(0, 99999);

            $findBlock = $purpleFroalaBlocks->themeBlocksJson($filter);

            $json = json_encode(['status' => 'ok', 'id' => $randomNumber, 'html' => $findBlock]);
            $this->set(['json' => $json]);
        }
        else {
            throw new NotFoundException(__('Page not found'));
        }
    }
    public function ajaxFroalaCodeEditor()
    {
        $this->viewBuilder()->autoLayout(false);

        if ($this->request->is('ajax')) {
            $id   = $this->request->getData('id');
            $url  = $this->request->getData('url');
            $redirect = $this->request->getData('redirect');
            $html = $this->request->getData('html');
            $json = json_encode(['status' => 'ok', 'id' => $id, 'url' => $url, 'redirect' => $redirect, 'html' => $html]);
            $this->set(['json' => $json]);
            $this->render();
        }
        else {
	        throw new NotFoundException(__('Page not found'));
	    }
    }
}