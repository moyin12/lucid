<?php
use Ziki\Http\Router;

session_start();
$directory = "./storage/contents/";
$ziki = new Ziki\Core\Document($directory);
$posts = $ziki->get();
if (empty($posts)) {
    $posts = [];
}
Router::get('/', function ($request) {
    $user = new Ziki\Core\Auth();
    if ($user::isInstalled() == true) {
        return $user->redirect('/install');
    } else {
        $directory = "./storage/contents/";
        $ziki = new Ziki\Core\Document($directory);
        $feed = $ziki->fetchRss();
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $host = $user->hash($url);
        // Render our view
        //print_r($feed);
        $count = new Ziki\Core\Subscribe();
        $setting = new Ziki\Core\Setting();
        $settings = $setting->getSetting();
        $fcount = $count->fcount();
        $count = $count->count();
        return $this->template->render('index.html', ['posts' => $feed, 'host' => $host, 'count' => $count, 'fcount' => $fcount]);
    }
});
foreach ($posts as $post) {
    if (empty($post['post_title'])) {
        Router::get('/post/{post_id}', function ($request, $post_id) {

            $directory = "./storage/contents/";
            $ziki = new Ziki\Core\Document($directory);
            $setting = new Ziki\Core\Setting();
            $settings = $setting->getSetting();
            $data = $request->getBody();
            //echo $data;
            $result = $ziki->getEach($post_id);
            $count = new Ziki\Core\Subscribe();
            $fcount = $count->fcount();
            $count = $count->count();
            if (isset($_GET['d'])) {

                $url = isset($_GET['d']) ? $_GET['d'] : '';
                //echo $url;
                $url = isset($_GET['d']) ? trim(base64_decode($_GET['d'])) : "";
                //echo $url;
                $url = $url . "/storage/rss/rss.xml";
                $rss = Ziki\Core\Subscribe::subc($url);
                //echo $url;
            }

            $post_details = $ziki->getPost($post_id);
            $tags = [];
            if (isset($post_details['tags'])) {
                foreach ($post_details['tags'] as $tag) {
                    $tags[] = '#' . $tag;
                }
            }
            $siteUrl = SITE_URL;
            $relatedPosts = $ziki->getRelatedPost(4, $tags, $post_id);
            return $this->template->render('blog-details.html', ['result' => $result, 'count' => $count, 'fcount' => $fcount, 'post' => $post_details, 'relatedPosts' => $relatedPosts, 'siteUrl' => $siteUrl]);
        });
    } else {
        Router::get('/post/{post_id}/{post_title}', function ($request, $post_id) {

            $directory = "./storage/contents/";
            $ziki = new Ziki\Core\Document($directory);
            $setting = new Ziki\Core\Setting();
            $settings = $setting->getSetting();
            $data = $request->getBody();
            //echo $data;
            $result = $ziki->getEach($post_id);
            $count = new Ziki\Core\Subscribe();
            $fcount = $count->fcount();
            $count = $count->count();
            if (isset($_GET['d'])) {

                $url = isset($_GET['d']) ? $_GET['d'] : '';
                //echo $url;
                $url = isset($_GET['d']) ? trim(base64_decode($_GET['d'])) : "";
                //echo $url;
                $url = $url . "/storage/rss/rss.xml";
                $rss = Ziki\Core\Subscribe::subc($url);
                //echo $url;
            }
            $post_id = explode(',', $post_id);
            $post_details = $ziki->getPost($post_id[0]);
            $tags = [];
            if (isset($post_details['tags'])) {
                foreach ($post_details['tags'] as $tag) {
                    $tags[] = '#' . $tag;
                }
            }
            $siteUrl = SITE_URL;
            $relatedPosts = $ziki->getRelatedPost(4, $tags, $post_id[0]);
            return $this->template->render('blog-details.html', ['result' => $result, 'count' => $count, 'fcount' => $fcount, 'post' => $post_details, 'relatedPosts' => $relatedPosts, 'siteUrl' => $siteUrl]);
        });
    }
}


Router::post('/edit-post', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $request = $request->getBody();
    $title = isset($request['title']) ? $request['title'] : '';
    $body = $request['postVal'];
    $tags = $request['tags'];
    $postSlug = explode('-', $request['postId']);
    $post_id = end($postSlug);
    // filter out non-image data
    $initial_images = array_filter($request, function ($key) {
        return preg_match('/^img-\w*$/', $key);
    }, ARRAY_FILTER_USE_KEY);
    // PHP automatically converts the '.' of the extension to an underscore
    // undo this

    $images = [];
    foreach ($initial_images as $key => $value) {
        $newKey = preg_replace('/_/', '.', $key);
        $images[$newKey] = $value;
    }
    $extra = "";
    //return json_encode([$images]);
    $ziki = new Ziki\Core\Document($directory);
    $result = $ziki->update_Post($title, $body, $tags, $images, $extra, $post_id);
    return json_encode($result);
});

Router::get('/timeline', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $post = $ziki->fetchAllRss();
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('timeline.html', ['posts' => $post, 'count' => $count, 'fcount' => $fcount]);
});

Router::get('/tags/{id}', function ($request, $id) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $result = $ziki->tagPosts($id);
    $twig_vars = ['posts' => $result, 'tag' => $id];
    return $this->template->render('tags.html', $twig_vars);
});
Router::post('/publish', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }

    $directory = "./storage/contents/";
    $data = $request->getBody();
    $title = isset($data['title']) ? $data['title'] : '';
    $body = $data['postVal'];
    $tags = $data['tags'];
    // filter out non-image data
    $initial_images = array_filter($data, function ($key) {
        return preg_match('/^img-\w*$/', $key);
    }, ARRAY_FILTER_USE_KEY);
    // PHP automatically converts the '.' of the extension to an underscore
    // undo this
    $images = [];
    foreach ($initial_images as $key => $value) {
        $newKey = preg_replace('/_/', '.', $key);
        $images[$newKey] = $value;
    }
    //return json_encode([$images]);
    $extra = "";
    $ziki = new Ziki\Core\Document($directory);
    $result = $ziki->create($title, $body, $tags, $images, $extra);
    return json_encode($result);
});
//this are some stupid working code written by porh please don't edit
//without notifying me
Router::get('/about', function ($request) {
    include ZIKI_BASE_PATH . "/src/core/SendMail.php";
    $checkifOwnersMailIsprovided = new  SendContactMail();
    $checkifOwnersMailIsprovided->getOwnerEmail();
    $aboutContent = $checkifOwnersMailIsprovided->getPage();
    $message = [];
    if (empty($checkifOwnersMailIsprovided->getOwnerEmail())) {
        $message['ownerEmailNotProvided'] = true;
    }
    if (isset($_SESSION['messages'])) {
        $message = $_SESSION['messages'];
        unset($_SESSION['messages']);
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('about.html', ['message' => $message, 'about' => $aboutContent, 'count' => $count, 'fcount' => $fcount]);
});
Router::post('/send', function ($request) {
    include ZIKI_BASE_PATH . "/src/core/SendMail.php";
    $request = $request->getBody();
    $SendMail = new SendContactMail();
    $SendMail->mailBody = $this->template->render('mail-template.html', ['guestName' => $request['guestName'], 'guestEmail' => $request['guestEmail'], 'guestMsg' => $request['guestMsg']]);
    $response = $SendMail->sendMail($request);
    
    return json_encode($response);
});
Router::post('/setcontactemail', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    include ZIKI_BASE_PATH . "/src/core/SendMail.php";
    $request = $request->getBody();
    $SetContactEmail = new SendContactMail();
    $SetContactEmail->setContactEmail($request);
    $SetContactEmail->clientMessage();
    return $SetContactEmail->redirect('/profile');
});
Router::post('/updateabout', function ($request) {
    $user = new Ziki\Core\Auth();
    $update = new Ziki\Core\Profile();
    $request = $request->getBody();
    $profile = $update->updateProfile($request);
    $_SESSION['alert']=$profile;
    return $user->redirect('/profile');
});
Router::post('/edit-about', function ($request) {
    $request = $request->getBody();
    $page = new Ziki\Core\Page();
    $response = $page->setAboutPage($request);
    return json_encode($response);
});
Router::get('/deletepost/{postId}', function ($request, $postId) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $postid = explode('-', $postId);
    $post = end($postid);
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $ziki->deletePost($post);
    return $user->redirect('/published-posts');
});
Router::get('/deletedraft/{postId}', function ($request, $postId) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $postid = explode('-', $postId);
    $post = end($postid);
    $directory = "./storage/drafts/";
    $ziki = new Ziki\Core\Document($directory);
    $ziki->deletePost($post);
    return $user->redirect('/drafts');
});
//the stupid codes ends here
Router::get('delete/{id}', function ($request, $id) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return new RedirectResponse("/");
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $result = $ziki->delete($id);
    return $this->template->render('timeline.html', ['delete' => $result]);
});
Router::get('/published-posts', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $posts = $ziki->get();
    return $this->template->render('published-posts.html', ['posts' => $posts]);
});

// Kuforiji' codes start here

// Start- Portfolio_expanded page
Router::get('/portfolio-expanded', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('portfolio-expanded.html');
});
// End- Portfolio_expanded

// logic for creating a new portfolio
Router::post('/newportfolio', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/portfolio/";
    $data = $request->getBody();
    $title = $data['title'];
    $body = $data['postVal'];
    // filter out non-image data
    $initial_images = array_filter($data, function ($key) {
        return preg_match('/^img-\w*$/', $key);
    }, ARRAY_FILTER_USE_KEY);
    // PHP automatically converts the '.' of the extension to an underscore
    // undo this
    $images = [];
    foreach ($initial_images as $key => $value) {
        $newKey = preg_replace('/_/', '.', $key);
        $images[$newKey] = $value;
    }
    //return json_encode([$images]);
    $portfolio = new Ziki\Core\Portfolio($directory);
    $result = $portfolio->createportfolio($title, $body, $images);
    return $this->template->render('portfolio.html');
});

// route to create-portfolio page
Router::get('/portfolio', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    $directory = "./storage/portfolio/";
    $portfolio = new Ziki\Core\Portfolio($directory);
    $portfolio = $portfolio->getportfolio();
    return $this->template->render('portfolio.html', ['portf' => $portfolio]);
});

// get portfolio expanded details
Router::get('/portfolio/{post_id}', function ($request, $port_id) {

    $directory = "./storage/portfolio/";
    $portfolio = new Ziki\Core\Portfolio($directory);

    //echo $data;
    $result = $portfolio->getEachPortfolio($port_id);
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    if (isset($_GET['d'])) {

        $url = isset($_GET['d']) ? $_GET['d'] : '';
        //echo $url;
        $url = isset($_GET['d']) ? trim(base64_decode($_GET['d'])) : "";
        //echo $url;
        $url = $url . "storage/rss/rss.xml";
        $rss = Ziki\Core\Subscribe::subc($url);
        //echo $url;
    }
    $port_id = explode('-', $port_id);
    $post = end($port_id);
    $portfolio_details = $portfolio->getOnePortfolio($post);

    return $this->template->render('portfolio-expanded.html', ['result' => $result, 'count' => $count, 'fcount' => $fcount, 'post' => $portfolio_details]);
});

Router::get('/deleteportfolio/{portfolioId}', function ($request, $portfolioId) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $portfolioId = explode('-', $portfolioId);
    $portf = end($portfolioId);
    $directory = "./storage/portfolio/";
    $portfolio = new Ziki\Core\Portfolio($directory);
    $portfolio->deletePortfolio($portf);
    return $user->redirect('/portfolio');
});

Router::get('delete/{id}', function ($request, $id) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return new RedirectResponse("/");
    }
    $directory = "./storage/portfolio/";
    $portfolio = new Ziki\Core\Portfolio($directory);
    $result = $portfolio->delete($id);
    return $this->template->render('portfolio.html', ['delete' => $result]);
});

// Kuforiji' codes end here


// ahmzyjazzy add this (^_^) : setting page
Router::get('/settings', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('settings.html');
});

// ahmzyjazzy add this (^_^) : setting api
Router::post('/appsetting', function ($request) {

    $data = $request->getBody();
    $field = $data['field']; //field to update in  app.json
    $value = $data['value']; //value for setting field in app.json

    $setting = new Ziki\Core\Setting();

    try {
        $result = $setting->updateSetting($field, $value);
        if ($result) {
            echo json_encode(array("msg" => "Setting updated successfully", "status" => "success", "data" => $result));
        } else {
            if ($field === 'THEME') {
                echo json_encode(array("msg" => "Theme does not exist", "status" => "error", "data" => null));
            } else {
                echo json_encode(array("msg" => "Unable to update setting, please try again", "status" => "error", "data" => null));
            }
        }
    } catch (Exception $e) {
        echo json_encode(array("msg" => "Caught exception: ",  $e->getMessage(), "\n", "status" => "error", "data" => null));
    }
});


//profile fullname and short bio update
Router::post('/sidebar ', function ($request) {
    include ZIKI_BASE_PATH . "/src/core/profile.php";
    $user = new Ziki\Core\Auth();
    $instantiateClass = new Ziki\Core\profileUpdate();
    $getUserInfo = $instantiateClass->getPage();
    //  $fullName = $this->fullname;
    //  $shortBio = $this->shortbio;
    //this gets the page content
    //   $getAboutPageContent = $userSiteDetails->getPage();
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    //this for error and successs messages
    $message = [];
    if (isset($_SESSION['messages'])) {
        $message = $_SESSION['messages'];
        unset($_SESSION['messages']);
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();

    return $this->template->render('sidebar.html', ['fullName' => $getUserInfo, 'shortBio' => $getUserInfo]);
});

// profile page
Router::get('/profile', function ($request) {
    ///please don't remove or change the included path
    include ZIKI_BASE_PATH . "/src/core/SendMail.php";
    //please don't rename the variables
    //$userSiteDetails = new  SendContactMail();
    //this  gets the owners email address
    // $userEmailAddr = $userSiteDetails->getOwnerEmail();
    //this gets the page content
    //$getAboutPageContent = $userSiteDetails->getPage();
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    //this for error and successs messages
    $alert = [];
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('profile.html',['alert'=>$alert]);
});

// following page
Router::get('/following', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $list = $ziki->subscription();
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();

    return $this->template->render('following.html', ['sub' => $list, 'count' => $count, 'fcount' => $fcount]);
});

// followers page
Router::get('/followers', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $list = $ziki->subscriber();
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();

    return $this->template->render('followers.html', ['sub' => $list, 'count' => $count, 'fcount' => $fcount]);
});

// Subscription page
Router::post('/subscriptions', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $list = $ziki->subscription();

    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();


    return $this->template->render('subscriptions.html', ['sub' => $list, 'count' => $count, 'fcount' => $fcount]);
});

// Subscribers page
Router::get('/subscribers', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $list = $ziki->subscriber();
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();

    return $this->template->render('subscribers.html', ['sub' => $list, 'count' => $count, 'fcount' => $fcount]);
});
Router::get('/unsubscribe', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }

    $id = $_GET['n'];
    $ziki = new Ziki\Core\Subscribe();
    $list = $ziki->unfollow($id);
    return $user->redirect('/subscriptions');
});

//stupid code by problemSolved

foreach ($posts as $post) {
    if (empty($post['post_title'])) {
        Router::get('/editor/{post_id}', function ($request, $post_id) {
            $user = new Ziki\Core\Auth();
            if (!$user->is_logged_in() || !$user->is_admin()) {
                return $user->redirect('/');
            }
            $directory = "./storage/contents/";
            $ziki = new Ziki\Core\Document($directory);
            $post_details = $ziki->getPost($post_id);
            return $this->template->render('editor.html', ['post' => $post_details]);
        });
    } else {
        Router::get('/editor/{post_id}/{post_title}', function ($request, $post_id) {
            $user = new Ziki\Core\Auth();
            if (!$user->is_logged_in() || !$user->is_admin()) {
                return $user->redirect('/');
            }
            $directory = "./storage/contents/";
            $ziki = new Ziki\Core\Document($directory);
            $getId = explode(',', $post_id);
            $post_details = $ziki->getPost($getId[0]);
            return $this->template->render('editor.html', ['post' => $post_details]);
        });
    }
}


//ends here again;
// 404 page
Router::get('/404', function ($request) {
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('404.html', ['count' => $count, 'fcount' => $fcount]);
});

//blog-details
Router::get('/blog-details', function ($request) {
    $setting = new Ziki\Core\Setting();
    $settings = $setting->getSetting();
    return $this->template->render('blog-details.html', $settings);
});

// Start- followers page

Router::get('/followers', function ($request) {

    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('followers.html',  ['count' => $count, 'fcount' => $fcount]);
});
// End- followers page

// Start- following page

Router::get('/following', function ($request) {

    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/contents/";
    $ziki = new Ziki\Core\Document($directory);
    $list = $ziki->subscription();
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('following.html', ['sub' => $list, 'count' => $count, 'fcount' => $fcount]);
});
// End- following page


/* Devmohy working on draft */
/* Save draft*/
Router::post('/saveDraft', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/drafts/";
    $data = $request->getBody();
    $title = isset($data['title']) ? $data['title'] : '';
    $body = $data['postVal'];
    $tags = $data['tags'];
    $initial_images = array_filter($data, function ($key) {
        return preg_match('/^img-\w*$/', $key);
    }, ARRAY_FILTER_USE_KEY);
    // PHP automatically converts the '.' of the extension to an underscore
    // undo this
    $images = [];
    foreach ($initial_images as $key => $value) {
        $newKey = preg_replace('/_/', '.', $key);
        $images[$newKey] = $value;
    }
    $ziki = new Ziki\Core\Document($directory);
    $result = $ziki->create($title, $body, $tags, $images, true);
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return json_encode($result);
});

/* Save draft */
/* Get all saved draft */
Router::get('/drafts', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/drafts/";
    $ziki = new Ziki\Core\Document($directory);
    $draft = $ziki->get();
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('drafts.html', ['drafts' => $draft]);
});

//videos page
Router::get('/videos', function ($request) {

    $directory = "./storage/videos/";
    $ziki = new Ziki\Core\Document($directory);
    $Videos = $ziki->getVideo();
    //print_r($Videos);
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('videos.html', ['videos' => $Videos, 'count' => $count, 'fcount' => $fcount]);
});
Router::get('/microblog', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    //print_r($Videos);
    $count = new Ziki\Core\Subscribe();
    $fcount = $count->fcount();
    $count = $count->count();
    return $this->template->render('microblog.html',  ['count' => $count, 'fcount' => $fcount]);
});



// Router::get('/about', function ($request) {
//     return $this->template->render('about-us.html');
// });

//download page
Router::get('/download', function ($request) {
    return $this->template->render('download.html');
});

Router::get('/auth/{provider}/{token}', function ($request, $token) {
    $user = new Ziki\Core\Auth();
    $check = $user->validateAuth($token);
    if ($_SESSION['login_user']['role'] == 'guest') {
        return $user->redirect('/');
    } else {
        return $user->redirect('/timeline');
    }
});

Router::get('/setup/{provider}/{token}', function ($request, $token) {
    $user = new Ziki\Core\Auth();
    $check = $user->validateAuth($token);
    if ($_SESSION['login_user']['role'] == 'guest') {
        return $user->redirect('/');
    } else {
        return $user->redirect('/profile');
    }
});

Router::get('/logout', function ($request) {
    $user = new Ziki\Core\Auth();
    $user->log_out();
    return $user->redirect('/');
});
Router::get('/api/images', function () {
    return (new Ziki\Core\UploadImage)->getAllImages();
});
Router::post('/api/upload-image', function () {
    return (new Ziki\Core\UploadImage)->upload();
});

Router::post('/setup', function ($request) {
    $data = $request->getBody();
    $user = new Ziki\Core\Auth();
    $setup = $user->setup($data);
    if ($setup == true) {
        return $user->redirect('/timeline');
    } else {
        return $user->redirect('/install');
    }
});

Router::post('/setup/email/login/{address}', function ($request) {
    $user = new Ziki\Core\Auth();
    die("good");
});

Router::get('/install', function ($request) {
    $user = new Ziki\Core\Auth();
    $system = new Ziki\Core\System();
    if ($user::isInstalled() == false) {
        return $user->redirect('/');
    } else {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $host = $user->hash($url);
        $checks = $system->checkSystem();
        if ($checks) {
            return $this->installer->render('install.html', ['host' => $host, 'domain' => $url]);
        } else {
            die(json_encode($checks));
        }
    }
});

Router::post('/addrss', function ($request) {
    $r = new Ziki\Core\Auth();
    $data = $request->getBody();
    $url = $_POST['domain'];
    $ziki = new Ziki\Core\Subscribe();
    $result = $ziki->extract($url);
    return $r->redirect('/subscriptions');
});

/* Add Video*/
Router::post('/addvideo', function ($request) {
    $user = new Ziki\Core\Auth();
    if (!$user->is_logged_in() || !$user->is_admin()) {
        return $user->redirect('/');
    }
    $directory = "./storage/videos/";
    $data = $request->getBody();

    //Get youtube url id for embed
    parse_str(parse_url($data['domain'], PHP_URL_QUERY), $YouTubeId);
    $video_url = "https://www.youtube.com/embed/" . $YouTubeId['v'];
    $video_title = $data['title'];
    $video_about = $data['description'];
    $ziki = new Ziki\Core\Document($directory);
    $ziki->addVideo($video_url, $video_title, $video_about);
    return $user->redirect('/videos');
});
