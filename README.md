<h1 align="center">⚠️⚠️⚠️⚠️  NOTICE   ⚠️⚠️⚠️⚠️</h1>
<p align="center"> 👇🏾👇🏾 This has been bundled up using the recommended OpenCart Event System. And moved into here 👇🏾👇🏾 </p>
<p align="center"> https://github.com/aldabil21/opencart-easywebpush </p>
<div align="center">
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
    <p>.</p>
</div>


# Webpush notifications / PWA in OpenCart 3.0.3.2

Base structure to add webpush, serviceworker, PWA to an OpenCart website, used in Opencart 3.0.3.2

### Libraries used
1) [minishlink/web-push](https://github.com/web-push-libs/web-push-php).
2) [Bootsrap toggle](https://www.bootstraptoggle.com/).

## How to setup

### 1. Customer Subscription

In your project terminal:

1) Install `guzzlehttp/guzzle`. 
```
$ composer require guzzlehttp/guzzle
```
You may face issues asking you to update `cardinity/cardinity-sdk-php` and `klarna/kco_rest` first. Do that. Or remove them and reinstall them after installing `guzzlehttp/guzzle`.

2) Install `minishlink/web-push`.
```
$ composer require minishlink/web-push
```
Be sure to follow requirements in [`minishlink/web-push`](https://github.com/web-push-libs/web-push-php) repo page. (php version, gmp, etc...).

3) Add the code in [`home.twig`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/catalog/view/theme/default/template/common/home.twig) to your home page in opencart, or any page you desire, I used `Bootstrap Toggle` here, you could use anything you like to listen to user subscription (Passive approach to listen to subscription as recommend by [Google docs](https://developers.google.com/web/fundamentals/push-notifications/permission-ux). However you can use any approach you like).

4) Add [`serviceworker.js`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/catalog/view/javascript/serviceworker.js) in `catalog/view/javascript` path. Then import `serviceworker.js` and `manifest.webmanifest` in your header. (see [`catalog/view/theme/default/template/common/header.twig`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/catalog/view/theme/default/template/common/header.twig)).

5) Add [`sw.js`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/sw.js) and [`manifest.webmanifest`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/manifest.webmanifest) in root path.

6) Add [`webpush.php`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/catalog/controller/api/webpush.php) controller in `catalog/controller/api`.

7) Add [`webpush.php`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/catalog/model/account/webpush.php) model in `catalog/model/account`.

8) Import the `user_push.sql` table to your database.

9) Change your Vapid keys in `serviceworker.js` (public key) and `config.php` (public & private). You may get a pair [here](https://web-push-codelab.glitch.me/). Also change your paths accordingly (DIRs, DB, etc...) in catalog & admin folders.

10) You are good to go. Try subscribe and you will receive a push confirmation.

![Subscribe Example](https://i.ibb.co/ng0hRfN/Screenshot-from-2020-05-27-01-47-24.png)

###### Note: Its will not work if you don't have an SSL certificate. You can setup SSL certification locally if you work with ampps or xampp etc...

##### Customer push
Now you can send push notification to customers from anywhere by calling the `notify` method in `catalog/api/webpush` like:

```
$this->load->controller(api/webpush/notify', $pushData);
```

The `$pushData` is an array with all options of push notification, example:

```
$pushData = array(
    'id' => $this->customer->getId(), //customer id (required)
    'title' => "Hello Customer", //(optional: see fallback in webpush controller)
    'msg' => "Push body for customer push", //(required)
    'icon' => 'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
    'badge' => 'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
    'vibrate' => [100, 50, 100], //(optional: see fallback in webpush controller)
    'data' => 'https://domain.com/somelink', //(optional: see fallback in webpush controller)
    'dir' => 'ltr', //(optional: see fallback in webpush controller)
    'image' =>'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
    'action' => array('action'=> 'action', 'title'=>'See Details')
);

```
You may change the fallback optional values in `webpush` controller in the `notify` method, in case you send a webpush without specifying some optionals.

###### Note: all webpush notifications have a “close” action, so the other action is up to you, you may specify the “title” and the “data” if you want the action to be clickable and lead to a page url. See `sw.js`, in the `notificationclick` listener.

##### Admin push

The same as customer notification, you can send push notification to Admin from anywhere by calling the `notifyAdmin` method in `catalog/api/webpush` like:
```
$this->load->controller('api/webpush/notifyAdmin, $pushData);
```

With the same `pushData` array structure, fallbacks of optionals can be modified in `webpush` controller in the `notifyAdmin` method. So admin can receive notification when user trigger something, like make order, register etc... you can trigger `notifyAdmin` method with the Email notification along may be useful, since all data you need will be there.

However, to let the admin receive notification, admin needs to subscribe, so see below to put a subscription mechanism for admin as well.

### 2. Admin Subscription

1) Add the switch button code and scripts to the admin [`dashboard.twig`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/admin/view/template/common/dashboard.twig) in (admin/view/template/common/dashboard.twig).

2) Add the admin [`webpush`](https://github.com/aldabil21/opencart-webpush-pwa/tree/master/admin/controller/webpush) folder to admin controller (admin/controller/webpush/webpush.php).

3) Go to setting -> user group permission and tick on the `webpush/webpush` permissions, otherwise you will get permission error.

4) Add admin [`serviceworker.js`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/admin/view/javascript/serviceworker.js) file to `admin/view/javascript/serviceworker.js`.

5) Same as customer notification, here I used `Bootstrap Toggle` (admin/view/javascript/bs-toggle), you are free to choose something else.

Now you can send push notification to customers from anywhere in your admin panel, like when change order status or ship etc... by calling the `notify` method in `admin/catalog/webpush/webpush` like:
```
$this->load->controller(webpush/webpush/notify', $pushData);
```

With the same values applied to `$pushData` array as mentioned in customer push above.

![Admin subscription](https://i.ibb.co/4Y0WYVF/Screenshot-from-2020-05-27-02-00-49.png)


### Cache and PWA

After adding `manifest.webmanifest` and `sw.js` in your root bath, you suppose to see a browser suggesting to download. In `sw.js`, you can add the page urls you want to cache for offline browsing, add your pages, switch off your network, and give it a try.

The code in [`home.twig`](https://github.com/aldabil21/opencart-webpush-pwa/blob/master/catalog/view/theme/default/template/common/home.twig) for app download will hold the app download suggestion prompt for better UX suggestions, add this code to specific button or image to prompt the download app after user choose to. (again, passive approach. you are free to prompt it immediately if you like, to do so, just remove this code)


###### Why made this?
I had to work on a project with opencart, I looked into the available extentions for webpush, it's either paid, or it connects you to a service provider that need you to upgrade in some point and pay, or have some limitations. With this, you can create your own system, events, triggers, and master your push as you like. Perhaps with another project I may create a special view page in admin panel to make it easier for non-coders to control the push events visually 
