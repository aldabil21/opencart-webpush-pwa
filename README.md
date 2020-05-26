Using Web push in OpenCart 3.0.3.2

In project terminal
1)Install guzzlehttp/guzzle.
$ composer require guzzlehttp/guzzle
You may face issues asking you to update cardinity/cardinity-sdk-php and klarna/kco_rest first. Do that. Or remove them and reinstall them after guzzlehttp/guzzle.
2)Install minishlink/web-push.
$ composer require minishlink/web-push
Be sure to follow requirements in minishlink/web-push page. (php version with gmp).

3)Add the code in home.twig to your home page, or any page you desire, I used Bootstrap Toggle here, you could use anything you like to listen to user subscription (Passive approach to listen to subscription as recommend by Google docs. However you can use any approach you like).
4)Add serviceworker.js in catalog/javascript path. Then add serviceworker.js and manifest in your header. (see catalog/view/template/common/header.twig)
5)Add sw.js in root path.
6)Add webpush.php controller in catalog/controller/api
7)Add webpush.php model in catalog/model/account
8)Add the user_push.sql table to your database
9)Change your Vapid keys in serviceworker.js (public key) and config.php (public & private). You may get a pair here. Also change your paths accordingly (DIRs, DB, etc...).
10)You are good to go. Try subscribe and you will receive a push confirmation.

Now you can send push notification to customers from anywhere by calling the notify method in catalog/api/webpush like:

$this->load->controller(api/webpush/notify', $pushData);

The \$pushData is an array with all options of push notification, example:

$pushData = array(
            'id' => $this->customer->getId(), //customer id (required)
'title' => "Hello Customer", //(optional: see fallback in webpush controller)
'msg' => "Push body for customer push", //(required)
'icon' => 'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
'badge' => 'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
'vibrate' => [100, 50, 100], //(optional: see fallback in webpush controller)
'data' => 'https://twitter.com/aldabil21', //(optional: see fallback in webpush controller)
'dir' => 'ltr', //(optional: see fallback in webpush controller)
'image' =>'image/example.png', //(optional: see fallback in webpush controller)
'action' => array('action'=> 'action', 'title'=>'My Twitter')
);

You may change the fallback optional values in webpush controller in the notify method, in case you send a webpush without specifying all the options.

Note: all webpush notifications have a “close” action, so the other action is up to you, you may specify the “title” and the “data” if you want the action to be clickable and lead to a page url. See sw.js, in the “notificationclick” listener.

The same as customer notification, you can send push notification to Admin from anywhere by calling the notifyAdmin method in catalog/api/webpush like:

$this->load->controller('api/webpush/notifyAdmin, $pushData);

With the same pushData array structure, fallbacks of optionals can be modified in webpush controller in the notifyAdmin method. So admin can receive notification when user trigger something, like make order, register etc... you can trigger notifyAdmin method with the Email notification along too.

However, to let the admin receive notification, admin needs to subscribe, so see below to put a subscription mechanism for admin as well.

Admin notifications
1)Add the switch button code and scripts to the dashboard (admin/view/template/common/dashboard.twig).
2)Add the controller folder to admin controller (admin/controller/webpush/webpush.php).
3)Go to user group permission and tick on the webpush/webpush permissions, otherwise you will get permission error.
4)Add admin serviceworker.js file to admin/view/javascript/serviceworker.js.
5)Same as customer notification, here I used Bootstrap Toggle (admin/view/javascript/bs-toggle), you are free to choose something else.

Now you can send push notification to customers from anywhere in your admin panel, like when change order status or ship etc... by calling the notify method in admin/catalog/webpush/webpush like:

$this->load->controller(webpush/webpush/notify', $pushData);

With the same value applied to \$pushData array as mentioned above.

Cache and PWA
After adding manifest.webmanifest and sw.js in your root bath, you suppose to see a browser suggesting to download. In sw.js, you can add the pages urls you want to cache for offline browsing, you may add you pages, switch off your network, and give it a try.
The code in home.twig for app download will hold the app download suggestion in mobile view, for best UX suggestions, add the code to specific button or image to prompt the download app after user choose to. (again, passive approach. you are free to prompt it immediate if you like, remove the relevant code in home.twig )
